<?php

namespace App\Observers;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     * Increment dashboard metrics in O(1) time without batch aggregation.
     */
    public function created(Order $order): void
    {
        try {
            if (!Schema::hasTable('dashboard_metrics_summary')) {
                return;
            }

            $status = strtolower($order->status ?? 'pending');
            $statusColumn = match ($status) {
                'completed' => 'completed_count',
                'pending'   => 'pending_count',
                'refunded'  => 'refunded_count',
                'cancelled' => 'cancelled_count',
                default     => null,
            };

            $amount = (float) ($order->total_amount ?? 0);
            $orderId = (int) $order->id;

            // Ensure summary row 1 exists
            $exists = DB::table('dashboard_metrics_summary')->where('id', 1)->exists();
            if (!$exists) {
                DB::table('dashboard_metrics_summary')->insert([
                    'id'              => 1,
                    'total_orders'    => 1,
                    'total_revenue'   => $amount,
                    'completed_count' => $status === 'completed' ? 1 : 0,
                    'pending_count'   => $status === 'pending' ? 1 : 0,
                    'refunded_count'  => $status === 'refunded' ? 1 : 0,
                    'cancelled_count' => $status === 'cancelled' ? 1 : 0,
                    'total_customers' => Schema::hasTable('customers') ? DB::table('customers')->count() : 0,
                    'max_order_id'    => $orderId,
                    'updated_at'      => now(),
                ]);
            } else {
                $updates = [
                    'total_orders'  => DB::raw('total_orders + 1'),
                    'total_revenue' => DB::raw("total_revenue + {$amount}"),
                    'max_order_id'  => DB::raw("(CASE WHEN max_order_id > {$orderId} THEN max_order_id ELSE {$orderId} END)"),
                    'updated_at'    => now(),
                ];

                if ($statusColumn) {
                    $updates[$statusColumn] = DB::raw("{$statusColumn} + 1");
                }

                DB::table('dashboard_metrics_summary')->where('id', 1)->update($updates);
            }

            // Maintain order_countries lookup table in O(1)
            $country = trim((string) ($order->shipping_country ?? ''));
            if ($country !== '' && Schema::hasTable('order_countries')) {
                DB::table('order_countries')->insertOrIgnore(['country' => $country]);
            }
        } catch (\Throwable $e) {
            // Graceful degradation
        }
    }

    /**
     * Handle the Order "updated" event.
     * Adjust status distribution and revenue differences in real-time.
     */
    public function updated(Order $order): void
    {
        try {
            if (!Schema::hasTable('dashboard_metrics_summary')) {
                return;
            }

            $updates = ['updated_at' => now()];

            // Handle status transition
            if ($order->wasChanged('status')) {
                $oldStatus = strtolower($order->getOriginal('status') ?? '');
                $newStatus = strtolower($order->status ?? '');

                $statusMap = [
                    'completed' => 'completed_count',
                    'pending'   => 'pending_count',
                    'refunded'  => 'refunded_count',
                    'cancelled' => 'cancelled_count',
                ];

                if (isset($statusMap[$oldStatus])) {
                    $oldCol = $statusMap[$oldStatus];
                    $updates[$oldCol] = DB::raw("(CASE WHEN {$oldCol} > 0 THEN {$oldCol} - 1 ELSE 0 END)");
                }

                if (isset($statusMap[$newStatus])) {
                    $newCol = $statusMap[$newStatus];
                    $updates[$newCol] = DB::raw("{$newCol} + 1");
                }
            }

            // Handle amount adjustment
            if ($order->wasChanged('total_amount')) {
                $diff = (float) $order->total_amount - (float) $order->getOriginal('total_amount');
                $updates['total_revenue'] = DB::raw("total_revenue + ({$diff})");
            }

            if (count($updates) > 1) {
                DB::table('dashboard_metrics_summary')->where('id', 1)->update($updates);
            }

            // Check if shipping country changed
            if ($order->wasChanged('shipping_country')) {
                $country = trim((string) ($order->shipping_country ?? ''));
                if ($country !== '' && Schema::hasTable('order_countries')) {
                    DB::table('order_countries')->insertOrIgnore(['country' => $country]);
                }
            }
        } catch (\Throwable $e) {
            // Graceful degradation
        }
    }

    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        try {
            if (!Schema::hasTable('dashboard_metrics_summary')) {
                return;
            }

            $status = strtolower($order->status ?? 'pending');
            $statusColumn = match ($status) {
                'completed' => 'completed_count',
                'pending'   => 'pending_count',
                'refunded'  => 'refunded_count',
                'cancelled' => 'cancelled_count',
                default     => null,
            };

            $amount = (float) ($order->total_amount ?? 0);

            $updates = [
                'total_orders'  => DB::raw('(CASE WHEN total_orders > 0 THEN total_orders - 1 ELSE 0 END)'),
                'total_revenue' => DB::raw("(CASE WHEN total_revenue > {$amount} THEN total_revenue - {$amount} ELSE 0 END)"),
                'updated_at'    => now(),
            ];

            if ($statusColumn) {
                $updates[$statusColumn] = DB::raw("(CASE WHEN {$statusColumn} > 0 THEN {$statusColumn} - 1 ELSE 0 END)");
            }

            DB::table('dashboard_metrics_summary')->where('id', 1)->update($updates);
        } catch (\Throwable $e) {
            // Graceful degradation
        }
    }
}
