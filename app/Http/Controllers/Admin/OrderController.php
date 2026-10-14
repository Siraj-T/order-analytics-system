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

        // Cache distinct shipping countries for filter dropdown
        $countries = Cache::remember('shipping_countries_list', 3600, function () {
            return DB::table('orders')->select('shipping_country')->distinct()->orderBy('shipping_country')->pluck('shipping_country');
        });

        // Current status counts for filter tabs
        $statusCounts = Cache::remember('order_status_tab_counts', 300, function () {
            return DB::table('orders')->selectRaw("
                COUNT(*) as all_count,
                COALESCE(SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END), 0) as completed_count,
                COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending_count,
                COALESCE(SUM(CASE WHEN status = 'refunded' THEN 1 ELSE 0 END), 0) as refunded_count,
                COALESCE(SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END), 0) as cancelled_count
            ")->first();
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
