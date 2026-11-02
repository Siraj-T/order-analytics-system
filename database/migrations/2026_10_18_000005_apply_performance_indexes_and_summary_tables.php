<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Safely applies all high-performance composite indexes, drops redundant indexes,
     * and materializes summary tables on existing 4M-row databases.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // 1. Drop redundant duplicate leftmost indexes to save buffer pool RAM
            $redundantIndexes = [
                'orders' => ['idx_status', 'idx_shipping_country'],
                'order_items' => ['idx_order_id', 'idx_product_name'],
                'customers' => ['idx_country'],
            ];

            foreach ($redundantIndexes as $table => $indexes) {
                if (Schema::hasTable($table)) {
                    foreach ($indexes as $index) {
                        try {
                            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$index}`");
                        } catch (\Throwable $e) {
                            // Index might not exist
                        }
                    }
                }
            }

            // 2. Add high-performance indexes to Customers
            if (Schema::hasTable('customers')) {
                $customerIndexes = [
                    "ALTER TABLE `customers` ADD INDEX `idx_customers_country_id` (`country`, `id`)",
                    "ALTER TABLE `customers` ADD INDEX `idx_full_name` (`full_name`)",
                    "ALTER TABLE `customers` ADD FULLTEXT INDEX `ft_customers_name_email` (`full_name`, `email`)",
                ];
                foreach ($customerIndexes as $sql) {
                    try {
                        DB::statement($sql);
                    } catch (\Throwable $e) {
                        // Already exists
                    }
                }
            }

            // 3. Add high-performance indexes to Orders
            if (Schema::hasTable('orders')) {
                $orderIndexes = [
                    "ALTER TABLE `orders` ADD INDEX `idx_order_date` (`order_date`)",
                    "ALTER TABLE `orders` ADD INDEX `idx_orders_status_date` (`status`, `order_date`)",
                    "ALTER TABLE `orders` ADD INDEX `idx_orders_status_id` (`status`, `id`)",
                    "ALTER TABLE `orders` ADD INDEX `idx_orders_customer_date` (`customer_id`, `order_date`)",
                    "ALTER TABLE `orders` ADD INDEX `idx_orders_customer_amount` (`customer_id`, `total_amount`)",
                    "ALTER TABLE `orders` ADD INDEX `idx_orders_status_country_id` (`status`, `shipping_country`, `id`)",
                    "ALTER TABLE `orders` ADD INDEX `idx_orders_status_amount` (`status`, `total_amount`)",
                    "ALTER TABLE `orders` ADD INDEX `idx_orders_country_id` (`shipping_country`, `id`)",
                ];
                foreach ($orderIndexes as $sql) {
                    try {
                        DB::statement($sql);
                    } catch (\Throwable $e) {
                        // Already exists
                    }
                }
            }

            // 4. Add high-performance indexes to Order Items
            if (Schema::hasTable('order_items')) {
                $itemIndexes = [
                    "ALTER TABLE `order_items` ADD INDEX `idx_items_order_covering` (`order_id`, `product_name`, `quantity`, `unit_price`)",
                    "ALTER TABLE `order_items` ADD INDEX `idx_items_product_metrics` (`product_name`, `order_id`, `quantity`, `unit_price`)",
                    "ALTER TABLE `order_items` ADD FULLTEXT INDEX `ft_items_product` (`product_name`)",
                ];
                foreach ($itemIndexes as $sql) {
                    try {
                        DB::statement($sql);
                    } catch (\Throwable $e) {
                        // Already exists
                    }
                }
            }

            // 5. Populate Product Metrics Summary if empty
            if (Schema::hasTable('product_metrics_summary') && Schema::hasTable('order_items')) {
                try {
                    $hasRows = DB::table('product_metrics_summary')->exists();
                    if (!$hasRows && DB::table('order_items')->exists()) {
                        DB::statement("INSERT INTO `product_metrics_summary` (product_name, total_units_sold, total_gross_revenue, average_price, order_occurrences)
                            SELECT product_name, SUM(quantity), SUM(quantity * unit_price), AVG(unit_price), COUNT(*)
                            FROM order_items
                            GROUP BY product_name
                            ON DUPLICATE KEY UPDATE
                                total_units_sold = VALUES(total_units_sold),
                                total_gross_revenue = VALUES(total_gross_revenue),
                                average_price = VALUES(average_price),
                                order_occurrences = VALUES(order_occurrences)");
                    }
                } catch (\Throwable $e) {
                    // Summary table population notice
                }
            }

            // 6. Populate Dashboard Metrics Summary if empty
            if (Schema::hasTable('dashboard_metrics_summary') && Schema::hasTable('orders')) {
                try {
                    $hasSummary = DB::table('dashboard_metrics_summary')->where('id', 1)->exists();
                    if (!$hasSummary && DB::table('orders')->exists()) {
                        DB::statement("REPLACE INTO `dashboard_metrics_summary` (id, total_orders, total_revenue, completed_count, pending_count, refunded_count, cancelled_count, total_customers, max_order_id, updated_at)
                            SELECT 
                                1,
                                COUNT(*),
                                COALESCE(SUM(total_amount), 0),
                                COALESCE(SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END), 0),
                                COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0),
                                COALESCE(SUM(CASE WHEN status = 'refunded' THEN 1 ELSE 0 END), 0),
                                COALESCE(SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END), 0),
                                (SELECT COUNT(*) FROM `customers`),
                                COALESCE(MAX(id), 0),
                                NOW()
                            FROM orders");
                    }
                } catch (\Throwable $e) {
                    // Summary table population notice
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe no-op or drop indexes if needed
    }
};

