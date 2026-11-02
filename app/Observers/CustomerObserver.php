<?php

namespace App\Observers;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CustomerObserver
{
    /**
     * Handle the Customer "created" event.
     * Increment total customers and maintain lookup tables.
     */
    public function created(Customer $customer): void
    {
        try {
            if (Schema::hasTable('dashboard_metrics_summary')) {
                DB::table('dashboard_metrics_summary')
                    ->where('id', 1)
                    ->update([
                        'total_customers' => DB::raw('total_customers + 1'),
                        'updated_at'      => now(),
                    ]);
            }

            $country = trim((string) ($customer->country ?? ''));
            if ($country !== '' && Schema::hasTable('order_countries')) {
                DB::table('order_countries')->insertOrIgnore(['country' => $country]);
            }
        } catch (\Throwable $e) {
            // Graceful degradation
        }
    }

    /**
     * Handle the Customer "deleted" event.
     */
    public function deleted(Customer $customer): void
    {
        try {
            if (Schema::hasTable('dashboard_metrics_summary')) {
                DB::table('dashboard_metrics_summary')
                    ->where('id', 1)
                    ->update([
                        'total_customers' => DB::raw('(CASE WHEN total_customers > 0 THEN total_customers - 1 ELSE 0 END)'),
                        'updated_at'      => now(),
                    ]);
            }
        } catch (\Throwable $e) {
            // Graceful degradation
        }
    }
}
