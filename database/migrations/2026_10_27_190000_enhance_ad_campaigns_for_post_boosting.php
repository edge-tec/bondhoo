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
        if (Schema::hasTable('ad_campaigns')) {
            Schema::table('ad_campaigns', function (Blueprint $table) {
                if (! Schema::hasColumn('ad_campaigns', 'post_id')) {
                    $table->foreignId('post_id')->nullable()->after('user_id')->constrained()->cascadeOnDelete();
                }
                if (! Schema::hasColumn('ad_campaigns', 'cta_text')) {
                    $table->string('cta_text', 50)->nullable()->after('ad_creatives');
                }
                if (! Schema::hasColumn('ad_campaigns', 'cta_link')) {
                    $table->string('cta_link', 500)->nullable()->after('cta_text');
                }
                if (! Schema::hasColumn('ad_campaigns', 'spent_budget')) {
                    $table->decimal('spent_budget', 12, 2)->default(0.00)->after('amount_spent');
                }
                if (! Schema::hasColumn('ad_campaigns', 'cpm_rate')) {
                    $table->decimal('cpm_rate', 8, 2)->default(10.00)->after('amount_spent'); // 10 BDT per 1k views
                }
                if (! Schema::hasColumn('ad_campaigns', 'cpc_rate')) {
                    $table->decimal('cpc_rate', 8, 2)->default(1.50)->after('cpm_rate'); // 1.5 BDT per click
                }
                if (! Schema::hasColumn('ad_campaigns', 'billing_status')) {
                    $table->string('billing_status', 30)->default('paid')->after('status');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('ad_campaigns')) {
            Schema::table('ad_campaigns', function (Blueprint $table) {
                $columns = ['post_id', 'cta_text', 'cta_link', 'cpm_rate', 'cpc_rate', 'billing_status'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('ad_campaigns', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
