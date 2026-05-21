<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payments') && !Schema::hasTable('merchant_payments')) {
            Schema::rename('payments', 'merchant_payments');
        }

        Schema::table('orders', function (Blueprint $table): void {
            if (!Schema::hasColumn('orders', 'order_code')) {
                $table->string('order_code', 64)->nullable()->after('number');
            }

            if (!Schema::hasColumn('orders', 'order_status')) {
                $table->string('order_status', 32)->nullable()->after('status');
            }
        });

        DB::table('orders')->orderBy('id')->get(['id', 'number', 'status', 'payment_status'])->each(function ($order): void {
            $status = strtolower((string) $order->status);
            $paymentStatus = strtolower((string) $order->payment_status);

            $normalizedPaymentStatus = match ($paymentStatus) {
                'paid' => 'paid',
                'failed' => 'failed',
                'refunded', 'cancelled', 'canceled' => 'cancelled',
                default => 'pending',
            };

            $normalizedOrderStatus = match (true) {
                in_array($status, ['completed', 'delivered'], true) => 'completed',
                in_array($status, ['cancelled', 'failed', 'payment_failed', 'refunded'], true) => 'cancelled',
                $normalizedPaymentStatus === 'paid' || in_array($status, ['paid', 'processing', 'shipped'], true) => 'processing',
                default => 'pending_payment',
            };

            DB::table('orders')
                ->where('id', $order->id)
                ->update([
                    'order_code' => $order->number,
                    'order_status' => $normalizedOrderStatus,
                    'payment_status' => $normalizedPaymentStatus,
                ]);
        });

        Schema::table('orders', function (Blueprint $table): void {
            if (! $this->hasIndex('orders', 'orders_order_code_unique')) {
                $table->unique('order_code');
            }

            if (! $this->hasIndex('orders', 'orders_order_status_payment_status_index')) {
                $table->index(['order_status', 'payment_status']);
            }
        });

        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('order_id');
                $table->string('provider', 64);
                $table->string('transaction_id', 120)->unique();
                $table->string('gateway_reference', 120)->nullable()->index();
                $table->decimal('amount', 14, 2);
                $table->string('currency', 3)->default('USD');
                $table->string('status', 32)->default('pending')->index();
                $table->timestamp('paid_at')->nullable();
                $table->json('raw_response')->nullable();
                $table->timestamps();

                $table->index(['order_id', 'status']);
            });
        }

        Schema::table('payments', function (Blueprint $table): void {
            if (! $this->hasForeignKey('payments', 'gateway_payments_order_fk')) {
                $table->foreign('order_id', 'gateway_payments_order_fk')->references('id')->on('orders')->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');

        Schema::table('orders', function (Blueprint $table): void {
            if ($this->hasIndex('orders', 'orders_order_status_payment_status_index')) {
                $table->dropIndex('orders_order_status_payment_status_index');
            }

            if ($this->hasIndex('orders', 'orders_order_code_unique')) {
                $table->dropUnique('orders_order_code_unique');
            }

            $table->dropColumn([
                'order_code',
                'order_status',
            ]);
        });

        if (Schema::hasTable('merchant_payments') && !Schema::hasTable('payments')) {
            Schema::rename('merchant_payments', 'payments');
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");

            foreach ($indexes as $item) {
                if (($item->name ?? null) === $index) {
                    return true;
                }
            }

            return false;
        }

        $database = DB::getDatabaseName();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }

    private function hasForeignKey(string $table, string $name): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return false;
        }

        $database = DB::getDatabaseName();

        return DB::table('information_schema.table_constraints')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('constraint_name', $name)
            ->where('constraint_type', 'FOREIGN KEY')
            ->exists();
    }
};
