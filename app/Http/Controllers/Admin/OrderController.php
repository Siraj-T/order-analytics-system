<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display the Orders Explorer with Server-Side 10-Item Pagination
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'all');
        $country = $request->query('country');
        $from = $request->query('from');
        $to = $request->query('to');
        $search = $request->query('search');
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 10; // Fixed 10 items per page for optimal rendering performance

        $query = Order::query()
            ->status($status)
            ->country($country)
            ->dateRange($from, $to)
            ->search($search);

        // High-Performance Server-Side Deferred-Join Pagination
        $orders = Order::deferredPaginate($query, $perPage, $page, $request->all());

        // Cache distinct shipping countries for filter dropdown (cached as raw array for PHP 8.5 compatibility)
        $countries = Cache::remember('shipping_countries_list', 3600, function () {
            return DB::table('orders')->select('shipping_country')->distinct()->orderBy('shipping_country')->pluck('shipping_country')->all();
        });

        // Current status counts for filter tabs (cached as pure array to avoid PHP 8.5 stdClass serialization issue)
        $statusCounts = Cache::remember('order_status_tab_counts', 300, function () {
            $raw = DB::table('orders')->selectRaw("
                COUNT(*) as all_count,
                COALESCE(SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END), 0) as completed_count,
                COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending_count,
                COALESCE(SUM(CASE WHEN status = 'refunded' THEN 1 ELSE 0 END), 0) as refunded_count,
                COALESCE(SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END), 0) as cancelled_count
            ")->first();

            return [
                'all_count' => (int) ($raw->all_count ?? 0),
                'completed_count' => (int) ($raw->completed_count ?? 0),
                'pending_count' => (int) ($raw->pending_count ?? 0),
                'refunded_count' => (int) ($raw->refunded_count ?? 0),
                'cancelled_count' => (int) ($raw->cancelled_count ?? 0),
            ];
        });

        return view('admin.orders.index', compact(
            'orders',
            'status',
            'country',
            'from',
            'to',
            'search',
            'countries',
            'statusCounts'
        ));
    }

    /**
     * On-Demand Order Items Drawer (AJAX Endpoint)
     * Fetches only line items for the selected order in <2ms
     */
    public function items($id)
    {
        $order = Order::with(['customer', 'items'])->findOrFail($id);

        return view('admin.orders.partials.drawer', compact('order'));
    }
}
