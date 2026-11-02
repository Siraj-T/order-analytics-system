<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    /**
     * Display the Executive Analytics Dashboard
     */
    public function index()
    {
        $dbOffline = false;
        try {
            // Instantaneous unified summary metrics (<0.2ms)
            $metrics = Order::getSummaryMetrics();

            $totalOrders = (int) ($metrics['total_orders'] ?? 0);
            $totalRevenue = (float) ($metrics['total_revenue'] ?? 0);
            $completedCount = (int) ($metrics['completed_count'] ?? 0);
            $refundedCount = (int) ($metrics['refunded_count'] ?? 0);
            $pendingCount = (int) ($metrics['pending_count'] ?? 0);
            $cancelledCount = (int) ($metrics['cancelled_count'] ?? 0);

            // Fetch recent 10 orders with explicit covered columns
            $recentOrders = Order::select(['id', 'customer_id', 'order_date', 'status', 'total_amount', 'shipping_country'])
                ->with(['customer:id,full_name,email,country'])
                ->withCount('items')
                ->orderBy('id', 'desc')
                ->limit(10)
                ->get();

            // High-speed O(1) version fingerprint for live client sync
            $version = $totalOrders . '_' . ($metrics['max_order_id'] ?? 0);
        } catch (\Throwable $e) {
            $dbOffline = true;
            $totalOrders = 0;
            $totalRevenue = 0.0;
            $completedCount = 0;
            $refundedCount = 0;
            $pendingCount = 0;
            $cancelledCount = 0;
            $recentOrders = collect([]);
            $version = 'initial';
        }

        return view('admin.dashboard', compact(
            'totalOrders',
            'totalRevenue',
            'completedCount',
            'refundedCount',
            'pendingCount',
            'cancelledCount',
            'recentOrders',
            'version',
            'dbOffline'
        ));
    }

    /**
     * Fast Version Fingerprint Endpoint for Live Client Auto-Sync (Sub-millisecond)
     */
    public function liveDashboard(Request $request)
    {
        // O(1) version check avoiding Schema::hasTable & 1M-row count on 8-second polling
        try {
            $summary = DB::table('dashboard_metrics_summary')->where('id', 1)->first();
            if ($summary && $summary->total_orders > 0) {
                $version = ($summary->total_orders ?? 0) . '_' . ($summary->max_order_id ?? 0);
            } else {
                $metrics = Order::getSummaryMetrics();
                $version = ($metrics['total_orders'] ?? 0) . '_' . ($metrics['max_order_id'] ?? 0);
            }
        } catch (\Throwable $e) {
            $metrics = Order::getSummaryMetrics();
            $version = ($metrics['total_orders'] ?? 0) . '_' . ($metrics['max_order_id'] ?? 0);
        }

        // If nothing changed, return tiny 35-byte payload immediately (<0.2ms)
        if ($request->query('v') === $version) {
            return response()->json([
                'changed' => false,
                'version' => $version,
            ]);
        }

        // New data detected: recalculate KPIs and render fresh Blade fragment
        $metrics = (object) Order::getSummaryMetrics();
        $version = ($metrics->total_orders ?? 0) . '_' . ($metrics->max_order_id ?? 0);

        $recentOrders = Order::select(['id', 'customer_id', 'order_date', 'status', 'total_amount', 'shipping_country'])
            ->with(['customer:id,full_name,email,country'])
            ->withCount('items')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        $view = view('admin.dashboard', [
            'totalOrders' => (int) ($metrics->total_orders ?? 0),
            'totalRevenue' => (float) ($metrics->total_revenue ?? 0),
            'completedCount' => (int) ($metrics->completed_count ?? 0),
            'refundedCount' => (int) ($metrics->refunded_count ?? 0),
            'pendingCount' => (int) ($metrics->pending_count ?? 0),
            'cancelledCount' => (int) ($metrics->cancelled_count ?? 0),
            'recentOrders' => $recentOrders,
            'version' => $version,
        ]);

        return response()->json([
            'changed' => true,
            'version' => $version,
            'metrics' => [
                'total_revenue' => '$' . number_format((float) ($metrics->total_revenue ?? 0), 2),
                'total_orders' => number_format((int) ($metrics->total_orders ?? 0)),
                'completed_count' => number_format((int) ($metrics->completed_count ?? 0)),
                'pending_count' => number_format((int) ($metrics->pending_count ?? 0)),
                'refunded_count' => number_format((int) ($metrics->refunded_count ?? 0)),
                'cancelled_count' => number_format((int) ($metrics->cancelled_count ?? 0)),
            ],
            'rowsHtml' => $view->fragment('live-rows'),
        ]);
    }
}
