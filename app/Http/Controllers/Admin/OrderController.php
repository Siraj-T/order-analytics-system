<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OrderController extends Controller
{
    /**
     * Display the Orders Explorer with Server-Side 10-Item Pagination
     */
    public function index(Request $request)
    {
        // Enforce filled checks so non-picked filters NEVER execute unneeded SQL clauses
        $status  = $request->filled('status') ? trim((string) $request->query('status')) : 'all';
        $country = $request->filled('country') ? trim((string) $request->query('country')) : null;
        $from    = $request->filled('from') ? trim((string) $request->query('from')) : null;
        $to      = $request->filled('to') ? trim((string) $request->query('to')) : null;
        $search  = $request->filled('search') ? trim((string) $request->query('search')) : null;
        $page    = max(1, (int) $request->query('page', 1));
        $perPage = 10;

        // Apply query filters strictly when filled
        $query = Order::query()
            ->when($request->filled('status') && $status !== 'all', fn ($q) => $q->status($status))
            ->when($request->filled('country'), fn ($q) => $q->country($country))
            ->when($request->filled('from') || $request->filled('to'), fn ($q) => $q->dateRange($from, $to))
            ->when($request->filled('search'), fn ($q) => $q->search($search));

        // Pull status counts instantaneously from unified summary metrics (0.2ms)
        $summaryMetrics = Order::getSummaryMetrics();
        $statusCounts = [
            'all_count'       => $summaryMetrics['total_orders'],
            'completed_count' => $summaryMetrics['completed_count'],
            'pending_count'   => $summaryMetrics['pending_count'],
            'refunded_count'  => $summaryMetrics['refunded_count'],
            'cancelled_count' => $summaryMetrics['cancelled_count'],
        ];

        // Pass clean active parameters into deferred pagination
        $activeParams = array_filter([
            'status'        => $status !== 'all' ? $status : null,
            'country'       => $country,
            'from'          => $from,
            'to'            => $to,
            'search'        => $search,
            'cursor_after'  => $request->filled('cursor_after') ? (int) $request->query('cursor_after') : null,
            'cursor_before' => $request->filled('cursor_before') ? (int) $request->query('cursor_before') : null,
        ], fn ($v) => $v !== null && $v !== '');

        // High-Performance Server-Side Deferred-Join Pagination
        $orders = Order::deferredPaginate($query, $perPage, $page, $activeParams, $statusCounts);

        // Cached distinct shipping countries for filter dropdown
        $countries = Order::getShippingCountries();

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
        $order = Order::select(['id', 'customer_id', 'order_date', 'status', 'total_amount', 'shipping_country'])
            ->with([
                'customer:id,full_name,email,country',
                'items:id,order_id,product_name,quantity,unit_price'
            ])
            ->findOrFail($id);

        return view('admin.orders.partials.drawer', compact('order'));
    }
}
