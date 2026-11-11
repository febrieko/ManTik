<?php

namespace App\Services;

use App\Models\Billing;
use App\Models\PppoeUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillingService
{
    public function __construct(
        protected CustomerBalanceService $balanceService,
        protected WhatsAppService $waService,
    ) {
    }

    /**
     * Proses pembayaran tagihan (tunai, transfer, atau potong saldo deposit pelanggan).
     *
     * @throws \InvalidArgumentException
     */
    public function payInvoice(
        Billing $billing,
        float $amount,
        string $paymentMethod,
        ?int $actorUserId = null,
        ?string $notes = null,
        bool $sendReceipt = true
    ): Billing {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Nominal pembayaran harus lebih besar dari Rp 0.');
        }

        $paidBilling = DB::transaction(function () use ($billing, $amount, $paymentMethod, $actorUserId, $notes) {
            /** @var Billing $lockedBilling */
            $lockedBilling = Billing::with(['customer', 'package'])
                ->lockForUpdate()
                ->findOrFail($billing->id);

            if ($lockedBilling->status === 'paid') {
                throw new \InvalidArgumentException('Tagihan ini sudah berstatus lunas.');
            }

            if ($lockedBilling->status === 'cancelled') {
                throw new \InvalidArgumentException('Tagihan yang sudah dibatalkan tidak dapat dibayar.');
            }

            $remaining = max(0.0, (float) $lockedBilling->amount - (float) $lockedBilling->paid_amount);
            if ($remaining <= 0) {
                throw new \InvalidArgumentException('Tidak ada sisa tagihan yang perlu dibayar.');
            }

            if ($amount > $remaining + 0.01) {
                throw new \InvalidArgumentException(
                    'Nominal pembayaran (Rp ' . number_format($amount, 0, ',', '.') . ') melebihi sisa tagihan (Rp ' . number_format($remaining, 0, ',', '.') . ').'
                );
            }

            $payAmount = min($amount, $remaining);

            if ($paymentMethod === 'balance') {
                $this->balanceService->debit(
                    $lockedBilling->customer,
                    $payAmount,
                    'billing_payment',
                    $notes ?: "Pembayaran tagihan {$lockedBilling->invoice_number}",
                    Billing::class,
                    $lockedBilling->id,
                    $actorUserId
                );
            }

            $newPaidAmount = (float) $lockedBilling->paid_amount + $payAmount;
            $isFullyPaid   = ($newPaidAmount + 0.01) >= (float) $lockedBilling->amount;

            if ($isFullyPaid) {
                $newPaidAmount = (float) $lockedBilling->amount;
            }

            $updatedNotes = $lockedBilling->notes;
            if ($notes) {
                $updatedNotes = trim(($updatedNotes ? $updatedNotes . "\n" : '') . $notes);
            }

            $lockedBilling->update([
                'paid_amount'    => $newPaidAmount,
                'status'         => $isFullyPaid ? 'paid' : 'partial',
                'paid_at'        => $isFullyPaid ? now() : $lockedBilling->paid_at,
                'payment_method' => $paymentMethod,
                'notes'          => $updatedNotes,
            ]);

            return $lockedBilling->fresh(['customer', 'package', 'createdBy']);
        });

        if ($sendReceipt && $paidBilling->customer?->phone) {
            $this->sendPaymentReceipt($paidBilling);
        }

        return $paidBilling;
    }

    /**
     * Generate tagihan bulanan otomatis untuk seluruh layanan PPPoE aktif
     * yang belum memiliki tagihan pada bulan periode tersebut.
     *
     * @return array{created: int, skipped: int, overdue_updated: int}
     */
    public function generateMonthlyBillings(?Carbon $periodDate = null, ?int $actorUserId = null): array
    {
        $date        = $periodDate ?: now();
        $periodStart = $date->copy()->startOfMonth()->toDateString();
        $periodEnd   = $date->copy()->endOfMonth()->toDateString();
        $dueDate     = $date->copy()->startOfMonth()->addDays(9)->toDateString();

        // Tandai tagihan bulan sebelumnya yang melewati jatuh tempo sebagai overdue
        $overdueUpdated = Billing::whereIn('status', ['unpaid', 'partial'])
            ->whereDate('due_date', '<', now()->toDateString())
            ->update(['status' => 'overdue']);

        $activePppoeUsers = PppoeUser::with(['customer', 'package'])
            ->where('is_disabled', false)
            ->whereNotNull('customer_id')
            ->whereNotNull('package_id')
            ->get();

        $created = 0;
        $skipped = 0;

        foreach ($activePppoeUsers as $pppoe) {
            $customer = $pppoe->customer;
            $package  = $pppoe->package;

            if (!$customer || !$package || $customer->status !== 'active' || (float) $package->price <= 0) {
                $skipped++;
                continue;
            }

            $alreadyExists = Billing::where('customer_id', $customer->id)
                ->where('package_id', $package->id)
                ->whereDate('period_start', $periodStart)
                ->whereDate('period_end', $periodEnd)
                ->where('status', '!=', 'cancelled')
                ->exists();

            if ($alreadyExists) {
                $skipped++;
                continue;
            }

            Billing::create([
                'customer_id'  => $customer->id,
                'package_id'   => $package->id,
                'amount'       => $package->price,
                'paid_amount'  => 0,
                'period_start' => $periodStart,
                'period_end'   => $periodEnd,
                'due_date'     => $dueDate,
                'status'       => 'unpaid',
                'notes'        => 'Tagihan otomatis bulanan (' . $date->translatedFormat('F Y') . ') - ' . $pppoe->username,
                'created_by'   => $actorUserId,
            ]);

            $created++;
        }

        return [
            'created'         => $created,
            'skipped'         => $skipped,
            'overdue_updated' => $overdueUpdated,
        ];
    }

    /**
     * Kirim pengingat tagihan via WhatsApp ke pelanggan.
     */
    public function sendReminder(Billing $billing): array
    {
        $customer = $billing->customer;
        if (!$customer || empty($customer->phone)) {
            return ['success' => false, 'error' => 'Nomor WhatsApp pelanggan belum terdaftar.'];
        }

        $packageName = $billing->package->name ?? 'Layanan Internet';
        $remaining   = number_format($billing->remaining_amount, 0, ',', '.');
        $dueDate     = $billing->due_date->format('d M Y');
        $portalUrl   = route('portal.login');

        $message = "🔔 *PENGINGAT TAGIHAN INTERNET*\n\n"
            . "Yth. *{$customer->name}*,\n"
            . "Berikut detail tagihan layanan Anda:\n\n"
            . "📄 No. Invoice: `{$billing->invoice_number}`\n"
            . "📦 Paket: {$packageName}\n"
            . "📅 Periode: {$billing->period_start->format('d M Y')} - {$billing->period_end->format('d M Y')}\n"
            . "⏰ Jatuh Tempo: *{$dueDate}*\n"
            . "💰 Sisa Tagihan: *Rp {$remaining}*\n\n"
            . "Anda dapat melakukan pembayaran tunai, transfer, atau langsung melalui Portal Pelanggan:\n"
            . "{$portalUrl}\n\n"
            . "Terima kasih atas kepercayaan Anda.\n_MANTIK Billing System_";

        return $this->waService->sendMessage($customer->phone, $message);
    }

    /**
     * Kirim struk pembayaran tagihan via WhatsApp.
     */
    public function sendPaymentReceipt(Billing $billing): void
    {
        try {
            $customer = $billing->customer;
            if (!$customer || empty($customer->phone)) {
                return;
            }

            $methodLabel = match ($billing->payment_method) {
                'balance'  => 'Saldo Deposit Pelanggan',
                'transfer' => 'Transfer Bank',
                'cash'     => 'Tunai',
                default    => strtoupper((string) $billing->payment_method),
            };

            $statusText = $billing->status === 'paid' ? 'LUNAS ✅' : 'TERBAYAR SEBAGIAN';
            $paidFmt    = number_format((float) $billing->paid_amount, 0, ',', '.');
            $remFmt     = number_format($billing->remaining_amount, 0, ',', '.');

            $message = "🧾 *BUKTI PEMBAYARAN TAGIHAN*\n\n"
                . "Halo *{$customer->name}*,\n"
                . "Pembayaran tagihan Anda telah kami terima.\n\n"
                . "📄 No. Invoice: `{$billing->invoice_number}`\n"
                . "📦 Paket: " . ($billing->package->name ?? 'Layanan Internet') . "\n"
                . "💳 Metode: {$methodLabel}\n"
                . "💵 Total Dibayar: *Rp {$paidFmt}*\n"
                . ($billing->status !== 'paid' ? "📉 Sisa Tagihan: Rp {$remFmt}\n" : '')
                . "📌 Status: *{$statusText}*\n\n"
                . "Terima kasih!\n_MANTIK Billing System_";

            $this->waService->sendMessage($customer->phone, $message);
        } catch (\Throwable $e) {
            Log::warning('[BillingService] Gagal mengirim struk WA: ' . $e->getMessage());
        }
    }
}

