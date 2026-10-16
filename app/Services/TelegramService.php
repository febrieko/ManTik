<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected $token;
    protected $apiUrl;

    public function __construct()
    {
        $this->token = Setting::get('telegram_bot_token') ?: config('services.telegram.bot_token');
        $this->apiUrl = "https://api.telegram.org/bot{$this->token}/";
    }

    public function setToken($token)
    {
        $this->token = $token;
        $this->apiUrl = "https://api.telegram.org/bot{$this->token}/";
    }

    public function getToken()
    {
        return $this->token;
    }

    public function getMe()
    {
        return $this->request('getMe');
    }

    public function getWebhookInfo()
    {
        return $this->request('getWebhookInfo');
    }

    public function setWebhook($url)
    {
        return $this->request('setWebhook', ['url' => $url]);
    }

    public function sendMessage($chatId, $text, $replyMarkup = null)
    {
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ];

        if ($replyMarkup) {
            $data['reply_markup'] = json_encode($replyMarkup);
        }

        return $this->request('sendMessage', $data);
    }

    public function editMessageText($chatId, $messageId, $text, $replyMarkup = null)
    {
        $data = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ];

        if ($replyMarkup) {
            $data['reply_markup'] = json_encode($replyMarkup);
        }

        return $this->request('editMessageText', $data);
    }

    public function answerCallbackQuery($callbackQueryId, $text = null, $showAlert = false)
    {
        $data = [
            'callback_query_id' => $callbackQueryId,
        ];

        if ($text) {
            $data['text'] = $text;
            $data['show_alert'] = $showAlert;
        }

        return $this->request('answerCallbackQuery', $data);
    }

    protected function request($method, $data = [])
    {
        if (empty($this->token)) {
            return ['ok' => false, 'description' => 'Token belum dikonfigurasi.'];
        }

        try {
            $response = Http::timeout(10)->post($this->apiUrl . $method, $data);
            return $response->json() ?? ['ok' => false, 'description' => 'Respon tidak valid dari Telegram'];
        } catch (\Exception $e) {
            Log::error("[Telegram API Error] {$method}: " . $e->getMessage());
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }
}
