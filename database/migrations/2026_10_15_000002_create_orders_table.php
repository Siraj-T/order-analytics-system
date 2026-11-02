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
        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->integer('id')->autoIncrement();
                $table->integer('customer_id');
                $table->dateTime('order_date');
                $table->string('status', 20);
                $table->decimal('total_amount', 10, 2);
                $table->string('shipping_country', 50);

                // Foreign key
                $table->foreign('customer_id', 'fk_orders_customer')
                      ->references('id')
                      ->on('customers');

                // Performance indexes
                $table->index('order_date', 'idx_order_date');

                // High-volume composite indexes for status filters + date sorting, and customer drilldowns
                $table->index(['status', 'order_date'], 'idx_orders_status_date');
                $table->index(['customer_id', 'order_date'], 'idx_orders_customer_date');

                // Covering index for customer LTV and count aggregation
                $table->index(['customer_id', 'total_amount'], 'idx_orders_customer_amount');

                // Composite filter + keyset cursor seek index
                $table->index(['status', 'id'], 'idx_orders_status_id');
                $table->index(['status', 'shipping_country', 'id'], 'idx_orders_status_country_id');

                // Covering index for dashboard status counts + revenue aggregation
                $table->index(['status', 'total_amount'], 'idx_orders_status_amount');

                // Keyset pagination index for country-filtered exploration
                $table->index(['shipping_country', 'id'], 'idx_orders_country_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
