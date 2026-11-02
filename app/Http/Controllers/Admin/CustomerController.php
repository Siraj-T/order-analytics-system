<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    /**
     * Display the Customers Directory
     */
    public function index(Request $request)
    {
        $search  = $request->filled('search') ? trim((string) $request->query('search')) : null;
        $country = $request->filled('country') ? trim((string) $request->query('country')) : null;
        $page    = max(1, (int) $request->query('page', 1));
        $perPage = 10;

        $hasFilters = $request->filled('search') || $request->filled('country');

        // Total count resolution: instant cached/summary count for unfiltered dataset
        if (!$hasFilters) {
            $summary = Order::getSummaryMetrics();
            $totalCustomers = (!empty($summary['total_customers']))
                ? (int) $summary['total_customers']
                : Cache::remember('total_customers_count', 600, fn () => (int) DB::table('customers')->count());
        } else {
            $filterCriteria = array_filter(['s' => $search, 'c' => $country]);
            $cacheKey = 'customers_cnt_' . md5(json_encode($filterCriteria));
            $totalCustomers = Cache::remember($cacheKey, 600, function () use ($search, $country) {
                return (int) Customer::query()
                    ->when(!empty($country), fn ($q) => $q->country($country))
                    ->when(!empty($search), fn ($q) => $q->search($search))
                    ->count();
            });
        }

        // Deferred-Join Optimization: Fetch only 10 primary keys first
        $idQuery = Customer::query()
            ->when(!empty($country), fn ($q) => $q->country($country))
            ->when(!empty($search), fn ($q) => $q->search($search))
            ->select('id')
            ->orderBy('id', 'desc');

        $offset = ($page - 1) * $perPage;
        $customerIds = (clone $idQuery)->offset($offset)->limit($perPage)->pluck('id')->all();

        // Eager-load order count and sum with explicit columns only for the 10 selected IDs
        $customersList = !empty($customerIds)
            ? Customer::select(['id', 'full_name', 'email', 'country', 'signup_date', 'is_active'])
                ->whereIn('id', $customerIds)
                ->withCount('orders')
                ->withSum('orders', 'total_amount')
                ->orderBy('id', 'desc')
                ->get()
            : collect([]);

        $customers = new \Illuminate\Pagination\LengthAwarePaginator(
            $customersList,
            $totalCustomers,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        $countries = Customer::getCountries();

        return view('admin.customers.index', compact('customers', 'search', 'country', 'countries', 'totalCustomers'));
    }

    /**
     * Display Customer Profile & Order History Modal Fragment
     */
    public function show($id)
    {
        $customer = Customer::select(['id', 'full_name', 'email', 'country', 'signup_date', 'is_active'])
            ->with([
                'orders' => fn ($q) => $q->select(['id', 'customer_id', 'order_date', 'status', 'total_amount', 'shipping_country'])
                    ->orderBy('order_date', 'desc')
                    ->limit(15)
            ])
            ->withCount('orders')
            ->withSum('orders', 'total_amount')
            ->findOrFail($id);

        return view('admin.customers.partials.modal', compact('customer'));
    }
}
