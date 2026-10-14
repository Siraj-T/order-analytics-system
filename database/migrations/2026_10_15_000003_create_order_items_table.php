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
        Schema::create('order_items', function (Blueprint $table) {
            $table->bigInteger('id')->autoIncrement();
            $table->integer('order_id');
            $table->string('product_name', 120);
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);

            // Foreign key
            $table->foreign('order_id', 'fk_items_order')
                  ->references('id')
                  ->on('orders');

            // Standard indexes
            $table->index('order_id', 'idx_order_id');
            $table->index('product_name', 'idx_product_name');

            // Covering index for top products aggregation across 3 million rows
            $table->index(['product_name', 'quantity', 'unit_price'], 'idx_items_product_metrics');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
