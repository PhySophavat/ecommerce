<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    public function notifyOrderCreated(Order $order): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $order->loadMissing(['customer', 'items.merchant.user']);

        $this->notifyCustomerAboutNewOrder($order);
        $this->notifyMerchantsAboutNewOrder($order);
        $this->notifyAdminsAboutNewOrder($order);
    }

    public function notifyMerchantsAboutNewOrder(Order $order): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $merchantGroups = $order->items
            ->filter(fn ($item) => $item->merchant !== null)
            ->groupBy('merchant_id');

        foreach ($merchantGroups as $items) {
            $merchant = $items->first()?->merchant;
            $merchantUser = $merchant?->user;
            $chatId = $this->resolveMerchantChatId($merchantUser);

            if (!$merchant || !$merchantUser || blank($chatId)) {
                continue;
            }

            $this->sendMessage($chatId, $this->merchantOrderMessage($order, $merchantUser, $items));
        }
    }

    public function sendMessage(string $chatId, string $message): void
    {
        $token = (string) config('services.telegram.bot_token');

        if ($token === '') {
            return;
        }

        try {
            $response = Http::asJson()
                ->timeout(10)
                ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $message,
                ]);

            if ($response->failed()) {
                Log::warning('Telegram notification failed.', [
                    'chat_id' => $chatId,
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);
            }
        } catch (\Throwable $exception) {
            Log::warning('Telegram notification threw an exception.', [
                'chat_id' => $chatId,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function isEnabled(): bool
    {
        return (bool) config('services.telegram.enabled')
            && filled(config('services.telegram.bot_token'));
    }

    private function resolveMerchantChatId(User $merchantUser): ?string
    {
        $chatId = $merchantUser->telegram_chat_id ?: config('services.telegram.merchant_chat_id');

        return filled($chatId) ? (string) $chatId : null;
    }

    private function notifyCustomerAboutNewOrder(Order $order): void
    {
        $customer = $order->customer;

        if (!$customer || blank($customer->telegram_chat_id)) {
            return;
        }

        $this->sendMessage((string) $customer->telegram_chat_id, $this->customerOrderMessage($order, $customer));
    }

    private function notifyAdminsAboutNewOrder(Order $order): void
    {
        $adminChatIds = User::query()
            ->where('role', 'admin')
            ->whereNotNull('telegram_chat_id')
            ->pluck('telegram_chat_id')
            ->filter()
            ->map(fn ($chatId) => (string) $chatId);

        $fallbackAdminChatId = config('services.telegram.admin_chat_id');

        if (filled($fallbackAdminChatId)) {
            $adminChatIds->push((string) $fallbackAdminChatId);
        }

        $adminChatIds = $adminChatIds->unique()->values();

        foreach ($adminChatIds as $chatId) {
            $this->sendMessage($chatId, $this->adminOrderMessage($order));
        }
    }

    private function merchantOrderMessage(Order $order, User $merchantUser, Collection $items): string
    {
        $merchantName = $merchantUser->merchant?->shop_name ?: $merchantUser->name;
        $merchantAmount = number_format((float) $items->sum('line_total'), 2);

        return implode("\n", [
            "សួស្តី {$merchantName}!",
            '',
            "មានការកម្មង់ថ្មី #{$order->number}",
            "👤 អតិថិជន: {$order->customer_name}",
            "💰 សរុប: {$merchantAmount}",
            '',
            'សូមពិនិត្យ និងដំណើរការ។',
        ]);
    }

    private function customerOrderMessage(Order $order, User $customer): string
    {
        $customerName = $customer->name ?: $order->customer_name;
        $orderAmount = number_format((float) $order->total_amount, 2);

        return implode("\n", [
            "សួស្តី {$customerName}!",
            '',
            "ការកម្មង់ #{$order->number} របស់អ្នកត្រូវបានបង្កើតរួចរាល់។",
            "💰 សរុប: {$orderAmount}",
            "📦 ស្ថានភាព: {$order->status}",
            '',
            'សូមអរគុណដែលបានកម្មង់ជាមួយយើង។',
        ]);
    }

    private function adminOrderMessage(Order $order): string
    {
        $orderAmount = number_format((float) $order->total_amount, 2);

        return implode("\n", [
            'Admin Alert!',
            '',
            "មាន order ថ្មី #{$order->number}",
            "👤 អតិថិជន: {$order->customer_name}",
            "💰 សរុប: {$orderAmount}",
            "📌 Payment: {$order->payment_method}",
            '',
            'សូមចូលទៅពិនិត្យក្នុងប្រព័ន្ធ។',
        ]);
    }
}
