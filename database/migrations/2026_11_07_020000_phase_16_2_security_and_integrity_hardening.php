<?php

declare(strict_types=1);

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
        // 1. Add cached seller rating columns on users
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table): void {
                if (! Schema::hasColumn('users', 'seller_rating')) {
                    $table->decimal('seller_rating', 3, 2)->default(0.00);
                }
                if (! Schema::hasColumn('users', 'seller_reviews_count')) {
                    $table->unsignedInteger('seller_reviews_count')->default(0);
                }
            });
        }

        // 2. Add optional stock tracking on marketplace_products
        if (Schema::hasTable('marketplace_products')) {
            Schema::table('marketplace_products', function (Blueprint $table): void {
                if (! Schema::hasColumn('marketplace_products', 'stock')) {
                    $table->unsignedInteger('stock')->nullable()->default(null)->after('price');
                }
            });
        }

        // 3. Scope marketplace_orders idempotency_key uniqueness per buyer_id & link accepted_offer_id
        if (Schema::hasTable('marketplace_orders')) {
            try {
                Schema::table('marketplace_orders', function (Blueprint $table): void {
                    $table->dropUnique('marketplace_orders_idempotency_key_unique');
                });
            } catch (Throwable) {
                // Index may already be dropped or named differently
            }

            Schema::table('marketplace_orders', function (Blueprint $table): void {
                if (! Schema::hasColumn('marketplace_orders', 'accepted_offer_id')) {
                    $table->foreignId('accepted_offer_id')
                        ->nullable()
                        ->constrained('marketplace_offers')
                        ->nullOnDelete();
                }
            });

            try {
                Schema::table('marketplace_orders', function (Blueprint $table): void {
                    $table->unique(['buyer_id', 'idempotency_key'], 'marketplace_orders_buyer_idempotency_unique');
                });
            } catch (Throwable) {
                // Composite unique index already exists
            }
        }

        // 4. Add redeemed_at and active status lookup index on marketplace_offers
        if (Schema::hasTable('marketplace_offers')) {
            Schema::table('marketplace_offers', function (Blueprint $table): void {
                if (! Schema::hasColumn('marketplace_offers', 'redeemed_at')) {
                    $table->timestamp('redeemed_at')->nullable()->after('cancelled_at');
                }
            });

            try {
                Schema::table('marketplace_offers', function (Blueprint $table): void {
                    $table->index(['product_id', 'buyer_id', 'status'], 'marketplace_offers_product_buyer_status_idx');
                });
            } catch (Throwable) {
                // Index already exists
            }
        }

        // 5. Add revoked_at timestamp to learning_certificates
        if (Schema::hasTable('learning_certificates')) {
            Schema::table('learning_certificates', function (Blueprint $table): void {
                if (! Schema::hasColumn('learning_certificates', 'revoked_at')) {
                    $table->timestamp('revoked_at')->nullable()->after('revoked_reason');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('learning_certificates') && Schema::hasColumn('learning_certificates', 'revoked_at')) {
            Schema::table('learning_certificates', function (Blueprint $table): void {
                $table->dropColumn('revoked_at');
            });
        }

        if (Schema::hasTable('marketplace_offers')) {
            try {
                Schema::table('marketplace_offers', function (Blueprint $table): void {
                    $table->dropIndex('marketplace_offers_product_buyer_status_idx');
                });
            } catch (Throwable) {
            }

            if (Schema::hasColumn('marketplace_offers', 'redeemed_at')) {
                Schema::table('marketplace_offers', function (Blueprint $table): void {
                    $table->dropColumn('redeemed_at');
                });
            }
        }

        if (Schema::hasTable('marketplace_orders')) {
            try {
                Schema::table('marketplace_orders', function (Blueprint $table): void {
                    $table->dropUnique('marketplace_orders_buyer_idempotency_unique');
                });
            } catch (Throwable) {
            }

            if (Schema::hasColumn('marketplace_orders', 'accepted_offer_id')) {
                Schema::table('marketplace_orders', function (Blueprint $table): void {
                    $table->dropConstrainedForeignId('accepted_offer_id');
                });
            }
        }

        if (Schema::hasTable('marketplace_products') && Schema::hasColumn('marketplace_products', 'stock')) {
            Schema::table('marketplace_products', function (Blueprint $table): void {
                $table->dropColumn('stock');
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table): void {
                $drop = [];
                if (Schema::hasColumn('users', 'seller_rating')) {
                    $drop[] = 'seller_rating';
                }
                if (Schema::hasColumn('users', 'seller_reviews_count')) {
                    $drop[] = 'seller_reviews_count';
                }
                if (! empty($drop)) {
                    $table->dropColumn($drop);
                }
            });
        }
    }
};
