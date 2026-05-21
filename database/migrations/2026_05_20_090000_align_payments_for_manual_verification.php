<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('order_id')->constrained()->cascadeOnDelete();
                $table->string('payment_method', 64)->nullable();
                $table->decimal('amount', 14, 2)->default(0);
                $table->string('screenshot')->nullable();
                $table->string('transaction_ref', 120)->nullable();
                $table->string('status', 32)->default('pending')->index();
                $table->text('admin_note')->nullable();
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('payments', function (Blueprint $table): void {
                if (!Schema::hasColumn('payments', 'payment_method')) {
                    $table->string('payment_method', 64)->nullable()->after('order_id');
                }

                if (!Schema::hasColumn('payments', 'screenshot')) {
                    $table->string('screenshot')->nullable()->after('amount');
                }

                if (!Schema::hasColumn('payments', 'transaction_ref')) {
                    $table->string('transaction_ref', 120)->nullable()->after('screenshot');
                }

                if (!Schema::hasColumn('payments', 'admin_note')) {
                    $table->text('admin_note')->nullable()->after('status');
                }

                if (!Schema::hasColumn('payments', 'verified_by')) {
                    $table->foreignId('verified_by')->nullable()->after('admin_note')->constrained('users')->nullOnDelete();
                }

                if (!Schema::hasColumn('payments', 'verified_at')) {
                    $table->timestamp('verified_at')->nullable()->after('verified_by');
                }
            });
        }

        if (Schema::hasTable('payments') && Schema::hasTable('orders')) {
            DB::table('payments')
                ->leftJoin('orders', 'orders.id', '=', 'payments.order_id')
                ->select([
                    'payments.id',
                    'payments.payment_method',
                    'payments.provider',
                    'payments.screenshot',
                    'payments.screenshot_path',
                    'payments.transaction_ref',
                    'payments.transaction_reference',
                    'payments.gateway_reference',
                    'payments.admin_note',
                    'payments.reject_reason',
                    'orders.payment_method as order_payment_method',
                ])
                ->get()
                ->each(function ($payment): void {
                    DB::table('payments')
                        ->where('id', $payment->id)
                        ->update([
                            'payment_method' => $payment->payment_method ?: $payment->provider ?: $payment->order_payment_method,
                            'screenshot' => $payment->screenshot ?: $payment->screenshot_path,
                            'transaction_ref' => $payment->transaction_ref ?: $payment->transaction_reference ?: $payment->gateway_reference,
                            'admin_note' => $payment->admin_note ?: $payment->reject_reason,
                        ]);
                });

            DB::table('payments')
                ->whereIn('status', ['paid', 'success'])
                ->update([
                    'status' => 'approved',
                    'verified_at' => DB::raw('COALESCE(verified_at, paid_at, updated_at)'),
                ]);
        }

        if (Schema::hasTable('orders')) {
            DB::table('orders')
                ->where('order_status', 'payment_review')
                ->update(['order_status' => 'payment_submitted']);

            DB::table('orders')
                ->where('payment_status', 'paid')
                ->update(['payment_status' => 'approved']);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('payments')) {
            return;
        }

        DB::table('orders')
            ->where('order_status', 'payment_submitted')
            ->update(['order_status' => 'payment_review']);

        DB::table('orders')
            ->where('payment_status', 'approved')
            ->update(['payment_status' => 'paid']);

        DB::table('payments')
            ->where('status', 'approved')
            ->update(['status' => 'paid']);

        Schema::table('payments', function (Blueprint $table): void {
            if (Schema::hasColumn('payments', 'verified_by')) {
                $table->dropConstrainedForeignId('verified_by');
            }

            foreach (['payment_method', 'screenshot', 'transaction_ref', 'admin_note', 'verified_at'] as $column) {
                if (Schema::hasColumn('payments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
