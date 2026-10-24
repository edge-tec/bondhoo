<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Enhance marketplace_orders table
        Schema::table('marketplace_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('marketplace_orders', 'order_number')) {
                $table->string('order_number')->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('marketplace_orders', 'quantity')) {
                $table->unsignedInteger('quantity')->default(1)->after('product_id');
            }
            if (! Schema::hasColumn('marketplace_orders', 'unit_price')) {
                $table->decimal('unit_price', 12, 2)->nullable()->after('quantity');
            }
            if (! Schema::hasColumn('marketplace_orders', 'subtotal')) {
                $table->decimal('subtotal', 12, 2)->nullable()->after('unit_price');
            }
            if (! Schema::hasColumn('marketplace_orders', 'delivery_type')) {
                $table->string('delivery_type')->default('pickup')->after('subtotal');
            }
            if (! Schema::hasColumn('marketplace_orders', 'delivery_charge')) {
                $table->decimal('delivery_charge', 12, 2)->default(0.00)->after('delivery_type');
            }
            if (! Schema::hasColumn('marketplace_orders', 'discount_amount')) {
                $table->decimal('discount_amount', 12, 2)->default(0.00)->after('delivery_charge');
            }
            if (! Schema::hasColumn('marketplace_orders', 'currency')) {
                $table->string('currency', 3)->default('BDT')->after('amount');
            }
            if (! Schema::hasColumn('marketplace_orders', 'shipping_name')) {
                $table->string('shipping_name')->nullable()->after('shipping_address');
            }
            if (! Schema::hasColumn('marketplace_orders', 'shipping_phone')) {
                $table->string('shipping_phone')->nullable()->after('shipping_name');
            }
            if (! Schema::hasColumn('marketplace_orders', 'product_snapshot')) {
                $table->json('product_snapshot')->nullable()->after('shipping_phone');
            }
            if (! Schema::hasColumn('marketplace_orders', 'payment_status')) {
                $table->string('payment_status')->default('unpaid')->after('product_snapshot');
            }
            if (! Schema::hasColumn('marketplace_orders', 'transaction_id')) {
                $table->string('transaction_id')->nullable()->after('payment_status');
            }
            if (! Schema::hasColumn('marketplace_orders', 'idempotency_key')) {
                $table->string('idempotency_key')->nullable()->unique()->after('transaction_id');
            }
            if (! Schema::hasColumn('marketplace_orders', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('idempotency_key');
            }
            if (! Schema::hasColumn('marketplace_orders', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable()->after('paid_at');
            }
            if (! Schema::hasColumn('marketplace_orders', 'shipped_at')) {
                $table->timestamp('shipped_at')->nullable()->after('confirmed_at');
            }
            if (! Schema::hasColumn('marketplace_orders', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable()->after('shipped_at');
            }
            if (! Schema::hasColumn('marketplace_orders', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('delivered_at');
            }
            if (! Schema::hasColumn('marketplace_orders', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable()->after('cancelled_at');
            }
            if (! Schema::hasColumn('marketplace_orders', 'cancelled_by')) {
                $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete()->after('cancellation_reason');
            }
            if (! Schema::hasColumn('marketplace_orders', 'disputed_at')) {
                $table->timestamp('disputed_at')->nullable()->after('cancelled_by');
            }
            if (! Schema::hasColumn('marketplace_orders', 'dispute_reason')) {
                $table->text('dispute_reason')->nullable()->after('disputed_at');
            }
            if (! Schema::hasColumn('marketplace_orders', 'dispute_status')) {
                $table->string('dispute_status')->nullable()->after('dispute_reason');
            }
            if (! Schema::hasColumn('marketplace_orders', 'dispute_notes')) {
                $table->text('dispute_notes')->nullable()->after('dispute_status');
            }
            if (! Schema::hasColumn('marketplace_orders', 'refunded_at')) {
                $table->timestamp('refunded_at')->nullable()->after('dispute_notes');
            }
            if (! Schema::hasColumn('marketplace_orders', 'refund_amount')) {
                $table->decimal('refund_amount', 12, 2)->nullable()->after('refunded_at');
            }
            if (! Schema::hasColumn('marketplace_orders', 'refund_reason')) {
                $table->text('refund_reason')->nullable()->after('refund_amount');
            }
            if (! Schema::hasColumn('marketplace_orders', 'escrow_released_at')) {
                $table->timestamp('escrow_released_at')->nullable()->after('refund_reason');
            }
            if (! Schema::hasColumn('marketplace_orders', 'tracking_carrier')) {
                $table->string('tracking_carrier')->nullable()->after('escrow_released_at');
            }
            if (! Schema::hasColumn('marketplace_orders', 'tracking_number')) {
                $table->string('tracking_number')->nullable()->after('tracking_carrier');
            }
        });

        // 2. Create marketplace_payment_transactions ledger
        if (! Schema::hasTable('marketplace_payment_transactions')) {
            Schema::create('marketplace_payment_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('marketplace_orders')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('transaction_reference')->unique();
                $table->string('provider')->default('bkash');
                $table->string('type')->default('payment'); // payment, refund, release, fee
                $table->decimal('amount', 12, 2);
                $table->string('currency', 3)->default('BDT');
                $table->string('status')->default('pending'); // pending, processing, successful, failed, reversed
                $table->string('idempotency_key')->nullable()->index();
                $table->json('gateway_payload')->nullable();
                $table->json('gateway_response')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();

                $table->index(['order_id', 'status']);
                $table->index(['provider', 'status']);
            });
        }

        // 3. Create marketplace_disputes table
        if (! Schema::hasTable('marketplace_disputes')) {
            Schema::create('marketplace_disputes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('marketplace_orders')->cascadeOnDelete();
                $table->foreignId('raised_by')->constrained('users')->cascadeOnDelete();
                $table->string('status')->default('open'); // open, under_review, resolved_refund, resolved_reject, closed
                $table->string('reason');
                $table->text('details');
                $table->json('evidence_files')->nullable();
                $table->text('admin_notes')->nullable();
                $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                $table->index(['order_id', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_disputes');
        Schema::dropIfExists('marketplace_payment_transactions');

        Schema::table('marketplace_orders', function (Blueprint $table) {
            $columns = [
                'order_number',
                'quantity',
                'unit_price',
                'subtotal',
                'delivery_type',
                'delivery_charge',
                'discount_amount',
                'currency',
                'shipping_name',
                'shipping_phone',
                'product_snapshot',
                'payment_status',
                'transaction_id',
                'idempotency_key',
                'paid_at',
                'confirmed_at',
                'shipped_at',
                'delivered_at',
                'cancelled_at',
                'cancellation_reason',
                'cancelled_by',
                'disputed_at',
                'dispute_reason',
                'dispute_status',
                'dispute_notes',
                'refunded_at',
                'refund_amount',
                'refund_reason',
                'escrow_released_at',
                'tracking_carrier',
                'tracking_number',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('marketplace_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
