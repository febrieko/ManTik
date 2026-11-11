<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerBalanceTransaction;
use App\Models\TopupTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerBalanceService
{
    public function __construct(protected WhatsAppService $wa)
    {
    }

    /**
     * Tambahkan saldo (credit) secara atomik dengan lockForUpdate dan catat di buku mutasi.
     */
    public function credit(
        Customer $customer,
        float $amount,
        string $category,
        ?string $notes = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $createdBy = null
    ): CustomerBalanceTransaction {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Nominal penambahan saldo harus lebih besar dari 0.');
        }

        return DB::transaction(function () use ($customer, $amount, $category, $notes, $referenceType, $referenceId, $createdBy) {
            $lockedCustomer = Customer::where('id', $customer->id)->lockForUpdate()->firstOrFail();

            $balanceBefore = (float) $lockedCustomer->balance;
            $balanceAfter  = $balanceBefore + $amount;

            $lockedCustomer->update(['balance' => $balanceAfter]);

            return CustomerBalanceTransaction::create([
                'customer_id'    => $lockedCustomer->id,
                'type'           => 'credit',
                'category'       => $category,
                'amount'         => $amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceAfter,
                'reference_type' => $referenceType,
                'reference_id'   => $referenceId,
                'notes'          => $notes,
                'created_by'     => $createdBy,
            ]);
        });
    }

    /**
     * Potong saldo (debit) secara atomik dengan lockForUpdate dan catat di buku mutasi.
     */
    public function debit(
        Customer $customer,
        float $amount,
        string $category,
        ?string $notes = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $createdBy = null,
        bool $allowNegative = false
    ): CustomerBalanceTransaction {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Nominal pemotongan saldo harus lebih besar dari 0.');
        }

        return DB::transaction(function () use ($customer, $amount, $category, $notes, $referenceType, $referenceId, $createdBy, $allowNegative) {
            $lockedCustomer = Customer::where('id', $customer->id)->lockForUpdate()->firstOrFail();

            $balanceBefore = (float) $lockedCustomer->balance;
            if (!$allowNegative && $balanceBefore < $amount) {
                throw new \RuntimeException('Saldo pelanggan tidak mencukupi.');
            }

            $balanceAfter = $balanceBefore - $amount;
            $lockedCustomer->update(['balance' => $balanceAfter]);

            return CustomerBalanceTransaction::create([
                'customer_id'    => $lockedCustomer->id,
                'type'           => 'debit',
                'category'       => $category,
                'amount'         => $amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceAfter,
                'reference_type' => $referenceType,
                'reference_id'   => $referenceId,
                'notes'          => $notes,
                'created_by'     => $createdBy,
            ]);
        });
    }

    /**
     * Top-up manual oleh Admin: membuat TopupTransaction (status=paid), tambah saldo, catat ledger, dan kirim notif WA.
     */
    public function topupManual(
        Customer $customer,
        float $amount,
        string $paymentMethod = 'admin_cash',
        ?string $notes = null,
        ?int $createdBy = null,
        bool $sendWaNotification = true
    ): TopupTransaction {
        $topup = DB::transaction(function () use ($customer, $amount, $paymentMethod, $notes, $createdBy) {
            $topup = TopupTransaction::create([
                'customer_id'    => $customer->id,
                'amount'         => $amount,
                'fee'            => 0,
                'total_amount'   => $amount,
                'gateway'        => 'manual',
                'payment_method' => $paymentMethod,
                'status'         => 'paid',
                'paid_at'        => now(),
                'notes'          => $notes,
                'created_by'     => $createdBy,
            ]);

            $this->credit(
                $customer,
                $amount,
                'topup',
                $notes ?: "Top-up saldo ({$topup->payment_method_label}) #{$topup->reference_code}",
                TopupTransaction::class,
                $topup->id,
                $createdBy
            );

            return $topup;
        });

        $customer->refresh();

        if ($sendWaNotification && !empty($customer->phone)) {
            $this->sendTopupWhatsAppNotification($customer, $topup);
        }

        return $topup;
    }

    /**
     * Selesaikan (mark paid) transaksi top-up yang masih pending (untuk approval admin maupun webhook Payment Gateway).
     */
    public function approveTopup(TopupTransaction $topup, ?int $approvedBy = null, ?array $gatewayPayload = null): TopupTransaction
    {
        $result = DB::transaction(function () use ($topup, $approvedBy, $gatewayPayload) {
            $lockedTopup = TopupTransaction::where('id', $topup->id)->lockForUpdate()->firstOrFail();

            if ($lockedTopup->status !== 'pending') {
                throw new \RuntimeException("Transaksi top-up #{$lockedTopup->reference_code} sudah diproses ({$lockedTopup->status}).");
            }

            $lockedTopup->update([
                'status'  => 'paid',
                'paid_at' => now(),
                'payload' => $gatewayPayload ?? $lockedTopup->payload,
            ]);

            $customer = $lockedTopup->customer;
            $this->credit(
                $customer,
                (float) $lockedTopup->amount,
                'topup',
                "Top-up disetujui ({$lockedTopup->payment_method_label}) #{$lockedTopup->reference_code}",
                TopupTransaction::class,
                $lockedTopup->id,
                $approvedBy
            );

            return $lockedTopup;
        });

        $customer = $result->customer->fresh();
        if (!empty($customer->phone)) {
            $this->sendTopupWhatsAppNotification($customer, $result);
        }

        return $result;
    }

    /**
     * Kirim notifikasi struk top-up berhasil ke WhatsApp pelanggan.
     */
    protected function sendTopupWhatsAppNotification(Customer $customer, TopupTransaction $topup): void
    {
        try {
            $amountFmt  = number_format((float) $topup->amount, 0, ',', '.');
            $balanceFmt = number_format((float) $customer->balance, 0, ',', '.');

            $message  = "✅ *Top-Up Saldo Berhasil*\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "Halo, *{$customer->name}*!\n";
            $message .= "Saldo deposit Anda telah berhasil ditambahkan.\n\n";
            $message .= "🧾 No. Referensi: `{$topup->reference_code}`\n";
            $message .= "💳 Metode: {$topup->payment_method_label}\n";
            $message .= "➕ Nominal Top-Up: *Rp {$amountFmt}*\n";
            $message .= "💰 Total Saldo Saat Ini: *Rp {$balanceFmt}*\n";
            if (!empty($topup->notes)) {
                $message .= "📝 Catatan: {$topup->notes}\n";
            }
            $message .= "━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "_Anda dapat menggunakan saldo ini untuk membeli voucher hotspot via WhatsApp (ketik PAKET) atau membayar tagihan bulanan._";

            $this->wa->sendMessage($customer->phone, $message);
        } catch (\Throwable $e) {
            Log::warning("[CustomerBalanceService] Gagal kirim notifikasi topup WA ke {$customer->phone}: " . $e->getMessage());
        }
    }
}

