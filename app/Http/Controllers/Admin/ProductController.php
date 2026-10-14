<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Display Product Insights Leaderboard from 3M line items
     */
    public function index()
    {
        // High-performance aggregate without slow COUNT(DISTINCT)
        // Stored as pure array to avoid PHP 8.5 stdClass serialization issues
        $products = Cache::remember('products_leaderboard_stats', 86400, function () {
            return DB::table('order_items')
                ->selectRaw('
                    product_name,
                    SUM(quantity) as total_units_sold,
                    SUM(quantity * unit_price) as total_gross_revenue,
                    AVG(unit_price) as average_price,
                    COUNT(*) as order_occurrences
                ')
                ->groupBy('product_name')
                ->orderByDesc('total_gross_revenue')
                ->limit(25)
                ->get()
                ->map(fn ($item) => (array) $item)
                ->all();
        });

        // Computed instantaneously from aggregated catalog metrics
        $totalCatalogUnits = Cache::remember('products_total_units_sold', 86400, function () use ($products) {
            return array_sum(array_column($products, 'total_units_sold'));
        });

        $totalItemRows = Cache::remember('products_total_item_rows', 86400, function () use ($products) {
            return array_sum(array_column($products, 'order_occurrences'));
        });

        return view('admin.products.index', compact('products', 'totalCatalogUnits', 'totalItemRows'));
    }
}
