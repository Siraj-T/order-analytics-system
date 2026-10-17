<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the enterprise schema modernization migrations.
     * Derived from forensic database audit and 3NF normalization principles.
     */
    public function up(): void
    {
        // 1. Normalized Product Catalog (Resolves 3NF violation in order_items)
        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->string('sku', 64)->unique('uq_products_sku');
                $table->string('name', 120)->index('idx_products_name');
                $table->text('description')->nullable();
                $table->decimal('price', 10, 2);
                $table->unsignedInteger('stock_quantity')->default(0);
                $table->boolean('is_active')->default(true)->index('idx_products_active');
                $table->timestamps();
            });
        }

        // 2. Structured Customer Addresses (Resolves flat shipping_country omission)
        if (!Schema::hasTable('addresses')) {
            Schema::create('addresses', function (Blueprint $table) {
                $table->id();
                $table->integer('customer_id');
                $table->enum('type', ['shipping', 'billing'])->default('shipping');
                $table->string('recipient_name', 120);
                $table->string('street_address_1', 255);
                $table->string('street_address_2', 255)->nullable();
                $table->string('city', 100);
                $table->string('state_province', 100)->nullable();
                $table->string('postal_code', 20);
                $table->char('country_code', 2)->index('idx_addresses_country');
                $table->string('phone', 30)->nullable();
                $table->boolean('is_default')->default(false);
                $table->timestamps();

                $table->foreign('customer_id', 'fk_addresses_customer')
                      ->references('id')
                      ->on('customers')
                      ->onDelete('cascade');

                $table->index(['customer_id', 'type'], 'idx_addresses_customer_type');
            });
        }

        // 3. Financial Payments & Multi-Currency Ledger
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->integer('order_id');
                $table->string('transaction_reference', 100)->unique('uq_payment_tx_ref');
                $table->enum('payment_method', ['credit_card', 'paypal', 'stripe', 'apple_pay', 'bank_transfer']);
                $table->decimal('amount', 10, 2);
                $table->char('currency_code', 3)->default('USD')->index('idx_payments_currency');
                $table->enum('status', ['pending', 'authorized', 'captured', 'failed', 'refunded'])->default('pending');
                $table->json('gateway_payload')->nullable();
                $table->timestamps();

                $table->foreign('order_id', 'fk_payments_order')
                      ->references('id')
                      ->on('orders')
                      ->onDelete('restrict');

                $table->index(['order_id', 'status'], 'idx_payments_order_status');
            });
        }

        // 4. Order Status Audit History Ledger (Tracks state changes & SLA milestones)
        if (!Schema::hasTable('order_status_history')) {
            Schema::create('order_status_history', function (Blueprint $table) {
                $table->id();
                $table->integer('order_id');
                $table->string('from_status', 20);
                $table->string('to_status', 20);
                $table->unsignedBigInteger('changed_by_user_id')->nullable();
                $table->string('reason', 255)->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('order_id', 'fk_history_order')
                      ->references('id')
                      ->on('orders')
                      ->onDelete('cascade');

                $table->index(['order_id', 'created_at'], 'idx_history_order_time');
            });
        }

        // 5. Enhance Orders Table with Financial Breakdown, Currency & Lifecycle Timestamps
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'currency_code')) {
                $table->char('currency_code', 3)->default('USD')->after('total_amount')->index('idx_orders_currency');
            }
            if (!Schema::hasColumn('orders', 'subtotal_amount')) {
                $table->decimal('subtotal_amount', 10, 2)->default(0.00)->after('status');
            }
            if (!Schema::hasColumn('orders', 'tax_amount')) {
                $table->decimal('tax_amount', 10, 2)->default(0.00)->after('subtotal_amount');
            }
            if (!Schema::hasColumn('orders', 'shipping_amount')) {
                $table->decimal('shipping_amount', 10, 2)->default(0.00)->after('tax_amount');
            }
            if (!Schema::hasColumn('orders', 'discount_amount')) {
                $table->decimal('discount_amount', 10, 2)->default(0.00)->after('shipping_amount');
            }
            if (!Schema::hasColumn('orders', 'shipping_carrier')) {
                $table->string('shipping_carrier', 50)->nullable()->after('shipping_country');
            }
            if (!Schema::hasColumn('orders', 'tracking_number')) {
                $table->string('tracking_number', 100)->nullable()->after('shipping_carrier');
            }
            if (!Schema::hasColumn('orders', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('tracking_number');
            }
            if (!Schema::hasColumn('orders', 'shipped_at')) {
                $table->timestamp('shipped_at')->nullable()->after('paid_at');
            }
            if (!Schema::hasColumn('orders', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable()->after('shipped_at');
            }
            if (!Schema::hasColumn('orders', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('delivered_at');
            }
            if (!Schema::hasColumn('orders', 'created_at')) {
                $table->timestamps();
            }
            if (!Schema::hasColumn('orders', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // 6. Enhance Order Items with Product Catalog Reference & Audit Timestamps
        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'product_id')) {
                $table->unsignedBigInteger('product_id')->nullable()->after('order_id')->index('idx_items_product_id');
            }
            if (!Schema::hasColumn('order_items', 'created_at')) {
                $table->timestamp('created_at')->useCurrent();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'product_id')) {
                $table->dropIndex('idx_items_product_id');
                $table->dropColumn('product_id');
            }
            if (Schema::hasColumn('order_items', 'created_at')) {
                $table->dropColumn('created_at');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            $columnsToDrop = [
                'currency_code', 'subtotal_amount', 'tax_amount', 'shipping_amount',
                'discount_amount', 'shipping_carrier', 'tracking_number',
                'paid_at', 'shipped_at', 'delivered_at', 'cancelled_at',
                'created_at', 'updated_at', 'deleted_at'
            ];
            foreach ($columnsToDrop as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('order_status_history');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('products');
    }
};
