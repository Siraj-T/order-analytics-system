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
        // Aggregates across 3,000,000 rows cached for 1 hour
        $products = Cache::remember('products_leaderboard_stats', 3600, function () {
            return DB::table('order_items')
                ->selectRaw('
                    product_name,
                    SUM(quantity) as total_units_sold,
                    SUM(quantity * unit_price) as total_gross_revenue,
                    AVG(unit_price) as average_price,
                    COUNT(DISTINCT order_id) as order_occurrences
                ')
                ->groupBy('product_name')
                ->orderByDesc('total_gross_revenue')
                ->limit(25)
                ->get();
        });

        $totalCatalogUnits = Cache::remember('products_total_units_sold', 3600, function () {
            return (int) DB::table('order_items')->sum('quantity');
        });

        $totalItemRows = Cache::remember('products_total_item_rows', 3600, function () {
            return (int) DB::table('order_items')->count();
        });

        return view('admin.products.index', compact('products', 'totalCatalogUnits', 'totalItemRows'));
    }
}
