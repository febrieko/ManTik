<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected $token;
    protected $apiUrl;

    public function __construct()
    {
        $this->token = config('services.telegram.bot_token');
        $this->apiUrl = "https://api.telegram.org/bot{$this->token}/";
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
        try {
            $response = Http::post($this->apiUrl . $method, $data);
            return $response->json();
        } catch (\Exception $e) {
            Log::error("[Telegram API Error] {$method}: " . $e->getMessage());
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }
}
