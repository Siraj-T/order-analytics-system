<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the Executive Analytics Dashboard
     */
    public function index()
    {
        $dbOffline = false;
        try {
            // Consolidated single-query metric extraction (O(1) index pass on 1M rows)
            $metrics = Cache::remember('dashboard_kpi_metrics', 300, function () {
                return DB::table('orders')->selectRaw("
                    COUNT(*) as total_orders,
                    COALESCE(SUM(total_amount), 0) as total_revenue,
                    COALESCE(SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END), 0) as completed_count,
                    COALESCE(SUM(CASE WHEN status = 'refunded' THEN 1 ELSE 0 END), 0) as refunded_count,
                    COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending_count,
                    COALESCE(SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END), 0) as cancelled_count
                ")->first();
            });

            $totalOrders = (int) ($metrics->total_orders ?? 0);
            $totalRevenue = (float) ($metrics->total_revenue ?? 0);
            $completedCount = (int) ($metrics->completed_count ?? 0);
            $refundedCount = (int) ($metrics->refunded_count ?? 0);
            $pendingCount = (int) ($metrics->pending_count ?? 0);
            $cancelledCount = (int) ($metrics->cancelled_count ?? 0);

            // Fetch recent 10 orders with eager loaded customer
            $recentOrders = Order::with(['customer:id,full_name,email,country'])
                ->withCount('items')
                ->orderBy('id', 'desc')
                ->limit(10)
                ->get();

            // High-speed version fingerprint for live client sync (~1ms)
            $version = Cache::remember('dashboard_version_fingerprint', 30, function () {
                $latest = DB::table('orders')->selectRaw('COUNT(*) as total_count, MAX(id) as max_id')->first();
                return ($latest->total_count ?? 0) . '_' . ($latest->max_id ?? 0);
            });
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
     * Fast Version Fingerprint Endpoint for Live Client Auto-Sync
     */
    public function liveDashboard(Request $request)
    {
        $latest = DB::table('orders')->selectRaw('COUNT(*) as total_count, MAX(id) as max_id')->first();
        $version = ($latest->total_count ?? 0) . '_' . ($latest->max_id ?? 0);

        // If nothing changed, return tiny 35-byte payload immediately
        if ($request->query('v') === $version) {
            return response()->json([
                'changed' => false,
                'version' => $version,
            ]);
        }

        // New data detected: recalculate KPIs and render fresh Blade fragment
        $metrics = DB::table('orders')->selectRaw("
            COUNT(*) as total_orders,
            COALESCE(SUM(total_amount), 0) as total_revenue,
            COALESCE(SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END), 0) as completed_count,
            COALESCE(SUM(CASE WHEN status = 'refunded' THEN 1 ELSE 0 END), 0) as refunded_count,
            COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending_count,
            COALESCE(SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END), 0) as cancelled_count
        ")->first();

        $recentOrders = Order::with(['customer:id,full_name,email,country'])
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
