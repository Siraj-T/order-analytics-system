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

            // Single-column performance indexes
            $table->index('order_date', 'idx_order_date');
            $table->index('status', 'idx_status');
            $table->index('shipping_country', 'idx_shipping_country');

            // High-volume composite indexes for status filters + date sorting, and customer drilldowns
            $table->index(['status', 'order_date'], 'idx_orders_status_date');
            $table->index(['customer_id', 'order_date'], 'idx_orders_customer_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
