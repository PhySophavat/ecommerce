<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            if (!Schema::hasColumn('orders', 'payment_type')) {
                $table->string('payment_type', 32)->nullable()->after('payment_method');
            }
        });

        Schema::table('payments', function (Blueprint $table): void {
            if (!Schema::hasColumn('payments', 'transaction_reference')) {
                $table->string('transaction_reference', 120)->nullable()->after('gateway_reference');
            }

            if (!Schema::hasColumn('payments', 'screenshot_path')) {
                $table->string('screenshot_path')->nullable()->after('transaction_reference');
            }

            if (!Schema::hasColumn('payments', 'reject_reason')) {
                $table->text('reject_reason')->nullable()->after('screenshot_path');
            }
        });

        DB::table('orders')->orderBy('id')->get(['id', 'payment_method', 'payment_status', 'order_status'])->each(function ($order): void {
            $method = strtolower((string) $order->payment_method);
            $legacyPaymentStatus = strtolower((string) $order->payment_status);
            $legacyOrderStatus = strtolower((string) $order->order_status);

            $paymentType = match ($method) {
                'cash' => 'cash',
                'aba_qr', 'acleda', 'wing' => 'manual_transfer',
                default => 'gateway',
            };

            $paymentStatus = match ($legacyPaymentStatus) {
                'paid' => 'paid',
                'rejected', 'cancelled', 'expired' => 'rejected',
                'failed' => 'failed',
                'submitted' => 'submitted',
                'unpaid' => 'unpaid',
                default => $paymentType === 'cash' ? 'unpaid' : 'pending',
            };

            $orderStatus = match (true) {
                in_array($legacyOrderStatus, ['completed', 'delivered'], true) => 'completed',
                $paymentStatus === 'paid' || in_array($legacyOrderStatus, ['processing', 'paid', 'shipped'], true) => 'processing',
                $paymentType === 'manual_transfer' && $paymentStatus === 'submitted' => 'payment_review',
                in_array($legacyOrderStatus, ['cancelled', 'failed', 'refunded'], true) => 'cancelled',
                $paymentType === 'gateway' => 'pending_payment',
                default => 'pending',
            };

            DB::table('orders')
                ->where('id', $order->id)
                ->update([
                    'payment_type' => $paymentType,
                    'payment_status' => $paymentStatus,
                    'order_status' => $orderStatus,
                ]);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn([
                'transaction_reference',
                'screenshot_path',
                'reject_reason',
            ]);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('payment_type');
        });
    }
};
