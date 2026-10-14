<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
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
        $search = $request->query('search');
        $country = $request->query('country');
        $perPage = 10;

        $query = Customer::query()
            ->country($country)
            ->search($search)
            ->withCount('orders')
            ->withSum('orders', 'total_amount');

        $customers = $query->orderBy('id', 'desc')->paginate($perPage)->withQueryString();

        $countries = Cache::remember('customer_countries_list', 3600, function () {
            return DB::table('customers')->select('country')->distinct()->orderBy('country')->pluck('country');
        });

        $totalCustomers = Cache::remember('total_customers_count', 600, function () {
            return (int) DB::table('customers')->count();
        });

        return view('admin.customers.index', compact('customers', 'search', 'country', 'countries', 'totalCustomers'));
    }

    /**
     * Display Customer Profile & Order History Modal Fragment
     */
    public function show($id)
    {
        $customer = Customer::with([
            'orders' => fn ($q) => $q->orderBy('order_date', 'desc')->limit(15)
        ])
        ->withCount('orders')
        ->withSum('orders', 'total_amount')
        ->findOrFail($id);

        return view('admin.customers.partials.modal', compact('customer'));
    }
}
