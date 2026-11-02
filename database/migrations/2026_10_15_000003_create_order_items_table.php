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
        if (!Schema::hasTable('order_items')) {
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

                // Covering index for on-demand drawer inspection (<1ms without table data reads)
                $table->index(['order_id', 'product_name', 'quantity', 'unit_price'], 'idx_items_order_covering');

                // Covering index for product search without filesort + top products aggregation across 3 million rows
                $table->index(['product_name', 'order_id', 'quantity', 'unit_price'], 'idx_items_product_metrics');

                if (DB::getDriverName() === 'mysql') {
                    $table->fullText('product_name', 'ft_items_product');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
