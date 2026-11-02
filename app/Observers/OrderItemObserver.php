<?php

namespace App\Observers;

use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OrderItemObserver
{
    /**
     * Handle the OrderItem "created" event.
     * Increment product leaderboard metrics in O(1) time.
     */
    public function created(OrderItem $item): void
    {
        try {
            if (!Schema::hasTable('product_metrics_summary')) {
                return;
            }

            $productName = trim((string) $item->product_name);
            if ($productName === '') {
                return;
            }

            $qty = (int) $item->quantity;
            $price = (float) $item->unit_price;
            $revenue = $qty * $price;

            $exists = DB::table('product_metrics_summary')->where('product_name', $productName)->exists();

            if (!$exists) {
                DB::table('product_metrics_summary')->insert([
                    'product_name'        => $productName,
                    'total_units_sold'    => $qty,
                    'total_gross_revenue' => $revenue,
                    'average_price'       => $price,
                    'order_occurrences'   => 1,
                    'updated_at'          => now(),
                ]);
            } else {
                DB::table('product_metrics_summary')
                    ->where('product_name', $productName)
                    ->update([
                        'total_units_sold'    => DB::raw("total_units_sold + {$qty}"),
                        'total_gross_revenue' => DB::raw("total_gross_revenue + {$revenue}"),
                        'order_occurrences'   => DB::raw("order_occurrences + 1"),
                        'average_price'       => DB::raw("(total_gross_revenue + {$revenue}) / (total_units_sold + {$qty})"),
                        'updated_at'          => now(),
                    ]);
            }
        } catch (\Throwable $e) {
            // Graceful degradation
        }
    }

    /**
     * Handle the OrderItem "deleted" event.
     */
    public function deleted(OrderItem $item): void
    {
        try {
            if (!Schema::hasTable('product_metrics_summary')) {
                return;
            }

            $productName = trim((string) $item->product_name);
            if ($productName === '') {
                return;
            }

            $qty = (int) $item->quantity;
            $price = (float) $item->unit_price;
            $revenue = $qty * $price;

            DB::table('product_metrics_summary')
                ->where('product_name', $productName)
                ->update([
                    'total_units_sold'    => DB::raw("(CASE WHEN total_units_sold > {$qty} THEN total_units_sold - {$qty} ELSE 0 END)"),
                    'total_gross_revenue' => DB::raw("(CASE WHEN total_gross_revenue > {$revenue} THEN total_gross_revenue - {$revenue} ELSE 0 END)"),
                    'order_occurrences'   => DB::raw("(CASE WHEN order_occurrences > 0 THEN order_occurrences - 1 ELSE 0 END)"),
                    'updated_at'          => now(),
                ]);
        } catch (\Throwable $e) {
            // Graceful degradation
        }
    }
}
