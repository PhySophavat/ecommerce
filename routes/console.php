<?php

use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('telegram:get-updates', function () {
    $token = (string) config('services.telegram.bot_token');

    if ($token === '') {
        $this->error('Missing TELEGRAM_BOT_TOKEN in .env');

        return self::FAILURE;
    }

    $response = Http::timeout(15)->get("https://api.telegram.org/bot{$token}/getUpdates");

    if ($response->failed()) {
        $this->error('Telegram getUpdates failed.');
        $this->line($response->body());

        return self::FAILURE;
    }

    $this->line(json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return self::SUCCESS;
})->purpose('Fetch Telegram bot updates and chat IDs');

Artisan::command('telegram:test {chat_id?} {--message=}', function (TelegramService $telegramService) {
    $chatId = $this->argument('chat_id') ?: config('services.telegram.merchant_chat_id');

    if (blank($chatId)) {
        $this->error('Provide a chat_id argument or set TELEGRAM_MERCHANT_CHAT_ID in .env');

        return self::FAILURE;
    }

    $message = (string) ($this->option('message') ?: "សួស្តី!\n\nនេះជា Telegram test message ពី Laravel.");

    $telegramService->sendMessage((string) $chatId, $message);

    $this->info("Telegram message sent to chat_id {$chatId}");

    return self::SUCCESS;
})->purpose('Send a Telegram test message');

Artisan::command('telegram:link-user {user_id} {chat_id} {--username=}', function () {
    $user = User::query()->findOrFail((int) $this->argument('user_id'));

    $user->forceFill([
        'telegram_chat_id' => (string) $this->argument('chat_id'),
        'telegram_username' => $this->option('username') ?: $user->telegram_username,
        'telegram_connected_at' => now(),
    ])->save();

    $this->info("Linked Telegram chat_id to user #{$user->id} ({$user->name})");

    return self::SUCCESS;
})->purpose('Link a Telegram chat ID to a user account');

Artisan::command('telegram:send-order {order_id?}', function (TelegramService $telegramService) {
    $orderId = $this->argument('order_id');

    $order = \App\Models\Order::query()
        ->with(['customer', 'items.merchant.user'])
        ->when(
            filled($orderId),
            fn ($query) => $query->whereKey((int) $orderId),
            fn ($query) => $query->latest('id')
        )
        ->first();

    if (!$order) {
        $this->error('Order not found.');

        return self::FAILURE;
    }

    $telegramService->notifyOrderCreated($order);

    $this->info("Telegram notifications sent for order {$order->number}");

    return self::SUCCESS;
})->purpose('Send Telegram notifications for a specific order or the latest order');
