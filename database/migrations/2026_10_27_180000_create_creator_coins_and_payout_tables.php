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
        // 1. Add coins_balance to wallets table if not exists
        if (Schema::hasTable('wallets') && ! Schema::hasColumn('wallets', 'coins_balance')) {
            Schema::table('wallets', function (Blueprint $table) {
                $table->unsignedBigInteger('coins_balance')->default(0)->after('pending_balance');
            });
        }

        // 2. Creator Payout / Cashout Requests
        if (! Schema::hasTable('creator_payout_requests')) {
            Schema::create('creator_payout_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->decimal('amount', 12, 2);
                $table->string('currency', 10)->default('BDT');
                $table->string('payment_method', 30); // bkash, nagad, rocket, bank
                $table->string('account_number', 64);
                $table->string('account_name', 120)->nullable();
                $table->string('bank_name', 120)->nullable();
                $table->string('branch_name', 120)->nullable();
                $table->string('routing_number', 64)->nullable();
                $table->string('status', 30)->default('pending'); // pending, approved, processing, completed, rejected
                $table->string('transaction_reference', 120)->nullable();
                $table->text('rejection_reason')->nullable();
                $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index(['status', 'created_at']);
            });
        }

        // 3. Wallet Transactions Ledger
        if (! Schema::hasTable('wallet_transactions')) {
            Schema::create('wallet_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('type', 40); // coin_purchase, gift_sent, gift_received, subscription_earning, ad_rev_share, payout_hold, payout_settled, payout_refund
                $table->decimal('amount', 12, 2)->default(0.00);
                $table->bigInteger('coins')->default(0); // positive for credit, negative for debit
                $table->string('currency', 10)->default('BDT');
                $table->string('reference_id', 120)->nullable()->index();
                $table->string('description', 255);
                $table->decimal('balance_after', 12, 2)->default(0.00);
                $table->unsignedBigInteger('coins_after')->default(0);
                $table->timestamps();

                $table->index(['user_id', 'type', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('creator_payout_requests');

        if (Schema::hasTable('wallets') && Schema::hasColumn('wallets', 'coins_balance')) {
            Schema::table('wallets', function (Blueprint $table) {
                $table->dropColumn('coins_balance');
            });
        }
    }
};
