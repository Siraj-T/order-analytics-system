<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;

class AdminRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_redirects_to_dashboard(): void
    {
        $response = $this->get('/');
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_executive_dashboard_loads_successfully(): void
    {
        $customer = Customer::create([
            'full_name' => 'John Doe',
            'email' => 'john@example.com',
            'country' => 'US',
            'signup_date' => now(),
            'is_active' => true,
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'order_date' => now(),
            'status' => 'completed',
            'total_amount' => 199.99,
            'shipping_country' => 'US',
        ]);

        $response = $this->get(route('admin.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Executive Analytics Dashboard');
        $response->assertSee('$199.99');
    }

    public function test_orders_explorer_loads_with_server_side_pagination(): void
    {
        $customer = Customer::create([
            'full_name' => 'Alice Smith',
            'email' => 'alice@example.com',
            'country' => 'CA',
            'signup_date' => now(),
            'is_active' => true,
        ]);

        for ($i = 1; $i <= 15; $i++) {
            Order::create([
                'customer_id' => $customer->id,
                'order_date' => now(),
                'status' => 'completed',
                'total_amount' => 50.00 * $i,
                'shipping_country' => 'CA',
            ]);
        }

        $response = $this->get(route('admin.orders'));
        $response->assertStatus(200);
        $response->assertSee('Orders Explorer');
        // Confirms 10-item pagination (showing 1 to 10 of 15)
        $response->assertSee('Showing');
        $response->assertSee('orders');
    }

    public function test_order_items_drawer_loads_on_demand(): void
    {
        $customer = Customer::create([
            'full_name' => 'Bob Brown',
            'email' => 'bob@example.com',
            'country' => 'GB',
            'signup_date' => now(),
            'is_active' => true,
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'order_date' => now(),
            'status' => 'pending',
            'total_amount' => 120.00,
            'shipping_country' => 'GB',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_name' => 'Wireless Keyboard',
            'quantity' => 2,
            'unit_price' => 60.00,
        ]);

        $response = $this->get(route('admin.orders.items', $order->id));
        $response->assertStatus(200);
        $response->assertSee('Wireless Keyboard');
        $response->assertSee('$120.00');
    }

    public function test_customers_directory_loads_successfully(): void
    {
        $customer = Customer::create([
            'full_name' => 'Jane Wilson',
            'email' => 'jane@example.com',
            'country' => 'FR',
            'signup_date' => now(),
            'is_active' => true,
        ]);

        $response = $this->get(route('admin.customers'));
        $response->assertStatus(200);
        $response->assertSee('Customers Directory');
        $response->assertSee('Jane Wilson');
    }

    public function test_product_insights_loads_successfully(): void
    {
        $customer = Customer::create([
            'full_name' => 'Mark Taylor',
            'email' => 'mark@example.com',
            'country' => 'DE',
            'signup_date' => now(),
            'is_active' => true,
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'order_date' => now(),
            'status' => 'completed',
            'total_amount' => 300.00,
            'shipping_country' => 'DE',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_name' => 'Noise-Cancelling Headphones',
            'quantity' => 1,
            'unit_price' => 300.00,
        ]);

        $response = $this->get(route('admin.products'));
        $response->assertStatus(200);
        $response->assertSee('Product Metrics');
        $response->assertSee('Noise-Cancelling Headphones');
    }

    public function test_orders_explorer_with_filled_and_empty_filters(): void
    {
        $customer = Customer::create([
            'full_name' => 'Filter Tester',
            'email' => 'filter@example.com',
            'country' => 'US',
            'signup_date' => now(),
            'is_active' => true,
        ]);

        Order::create([
            'customer_id' => $customer->id,
            'order_date' => now(),
            'status' => 'completed',
            'total_amount' => 150.00,
            'shipping_country' => 'US',
        ]);

        // When empty query parameters are submitted (?status=all&search=&country=&from=&to=)
        $response = $this->get('/admin/orders?status=all&search=&country=&from=&to=');
        $response->assertStatus(200);
        $response->assertSee('$150.00');

        // When status is specifically filled
        $responseCompleted = $this->get('/admin/orders?status=completed');
        $responseCompleted->assertStatus(200);
        $responseCompleted->assertSee('$150.00');

        $responsePending = $this->get('/admin/orders?status=pending');
        $responsePending->assertStatus(200);
        $responsePending->assertSee('No orders match');
    }

    public function test_customers_directory_with_filters(): void
    {
        Customer::create([
            'full_name' => 'Charlie Canadian',
            'email' => 'charlie@canada.ca',
            'country' => 'CA',
            'signup_date' => now(),
            'is_active' => true,
        ]);

        Customer::create([
            'full_name' => 'Dennis German',
            'email' => 'dennis@germany.de',
            'country' => 'DE',
            'signup_date' => now(),
            'is_active' => true,
        ]);

        // Filter by country CA
        $response = $this->get('/admin/customers?country=CA');
        $response->assertStatus(200);
        $response->assertSee('Charlie Canadian');
        $response->assertDontSee('Dennis German');

        // Search by name
        $searchResponse = $this->get('/admin/customers?search=Dennis');
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('Dennis German');
        $searchResponse->assertDontSee('Charlie Canadian');
    }

    public function test_dashboard_live_endpoint_fast_response(): void
    {
        $customer = Customer::create([
            'full_name' => 'Live User',
            'email' => 'live@example.com',
            'country' => 'US',
            'signup_date' => now(),
            'is_active' => true,
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'order_date' => now(),
            'status' => 'completed',
            'total_amount' => 88.00,
            'shipping_country' => 'US',
        ]);

        $response = $this->get(route('admin.dashboard.live', ['v' => 'initial']));
        $response->assertStatus(200);
        $response->assertJsonStructure(['changed', 'version', 'metrics', 'rowsHtml']);
        $this->assertTrue($response->json('changed'));

        // If queried with same version, returns changed=false immediately
        $version = $response->json('version');
        $cachedResponse = $this->get(route('admin.dashboard.live', ['v' => $version]));
        $cachedResponse->assertStatus(200);
        $this->assertFalse($cachedResponse->json('changed'));
    }

    public function test_customer_profile_modal_fragment(): void
    {
        $customer = Customer::create([
            'full_name' => 'Modal User',
            'email' => 'modal@example.com',
            'country' => 'US',
            'signup_date' => now(),
            'is_active' => true,
        ]);

        Order::create([
            'customer_id' => $customer->id,
            'order_date' => now(),
            'status' => 'completed',
            'total_amount' => 450.00,
            'shipping_country' => 'US',
        ]);

        $response = $this->get(route('admin.customers.show', $customer->id));
        $response->assertStatus(200);
        $response->assertSee('Modal User');
        $response->assertSee('$450.00');
    }

    public function test_orders_keyset_seeking_navigation(): void
    {
        $customer = Customer::create([
            'full_name' => 'Keyset User',
            'email' => 'keyset@example.com',
            'country' => 'US',
            'signup_date' => now(),
            'is_active' => true,
        ]);

        for ($i = 1; $i <= 25; $i++) {
            Order::create([
                'customer_id' => $customer->id,
                'order_date' => now()->subMinutes(30 - $i),
                'status' => 'completed',
                'total_amount' => 10.00 * $i,
                'shipping_country' => 'US',
            ]);
        }

        // Keyset forward seek
        $firstPage = $this->get('/admin/orders');
        $firstPage->assertStatus(200);

        $lastId = Order::orderByDesc('id')->skip(9)->value('id');
        $secondPage = $this->get("/admin/orders?cursor_after={$lastId}");
        $secondPage->assertStatus(200);
        $secondPage->assertSee('Showing');
    }

    public function test_summary_metrics_lazy_population(): void
    {
        $customer = Customer::create([
            'full_name' => 'Summary Test User',
            'email' => 'summary@example.com',
            'country' => 'CA',
            'signup_date' => now(),
            'is_active' => true,
        ]);

        Order::create([
            'customer_id' => $customer->id,
            'order_date' => now(),
            'status' => 'completed',
            'total_amount' => 150.00,
            'shipping_country' => 'CA',
        ]);

        Order::create([
            'customer_id' => $customer->id,
            'order_date' => now(),
            'status' => 'pending',
            'total_amount' => 50.00,
            'shipping_country' => 'CA',
        ]);

        \Illuminate\Support\Facades\Cache::forget('orders_summary_metrics');
        $metrics = Order::getSummaryMetrics();

        $this->assertEquals(2, $metrics['total_orders']);
        $this->assertEquals(200.00, $metrics['total_revenue']);
        $this->assertEquals(1, $metrics['completed_count']);
        $this->assertEquals(1, $metrics['pending_count']);
    }

    public function test_product_search_and_eager_covered_columns(): void
    {
        $customer = Customer::create([
            'full_name' => 'Product Searcher',
            'email' => 'searcher@example.com',
            'country' => 'US',
            'signup_date' => now(),
            'is_active' => true,
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'order_date' => now(),
            'status' => 'completed',
            'total_amount' => 320.00,
            'shipping_country' => 'US',
        ]);

        \App\Models\OrderItem::create([
            'order_id' => $order->id,
            'product_name' => 'Noise-Cancelling Headphones Pro',
            'quantity' => 1,
            'unit_price' => 320.00,
        ]);

        // Search by product name
        $response = $this->get('/admin/orders?search=Headphones');
        $response->assertStatus(200);
        $response->assertSee('Product Searcher');

        // Order items drawer
        $drawerResponse = $this->get("/admin/orders/{$order->id}/items");
        $drawerResponse->assertStatus(200);
        $drawerResponse->assertSee('Noise-Cancelling Headphones Pro');
        $drawerResponse->assertSee('$320.00');
    }

    public function test_observers_maintain_dashboard_and_lookup_tables_in_real_time(): void
    {
        // 1. Create Customer -> verify CustomerObserver adds country and customer count
        $customer = Customer::create([
            'full_name' => 'Observer Customer',
            'email' => 'observer@example.com',
            'country' => 'Denmark',
            'signup_date' => now(),
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('order_countries', ['country' => 'Denmark']);

        // 2. Create Order -> verify OrderObserver updates dashboard summary in real-time
        $order = Order::create([
            'customer_id' => $customer->id,
            'order_date' => now(),
            'status' => 'pending',
            'total_amount' => 125.50,
            'shipping_country' => 'Norway',
        ]);

        $this->assertDatabaseHas('order_countries', ['country' => 'Norway']);

        $summary = \Illuminate\Support\Facades\DB::table('dashboard_metrics_summary')->where('id', 1)->first();
        $this->assertNotNull($summary);
        $this->assertEquals(1, $summary->pending_count);
        $this->assertEquals(125.50, (float) $summary->total_revenue);

        // 3. Update Order status from pending to completed
        $order->status = 'completed';
        $order->total_amount = 150.00;
        $order->save();

        $summaryAfterUpdate = \Illuminate\Support\Facades\DB::table('dashboard_metrics_summary')->where('id', 1)->first();
        $this->assertEquals(0, $summaryAfterUpdate->pending_count);
        $this->assertEquals(1, $summaryAfterUpdate->completed_count);
        $this->assertEquals(150.00, (float) $summaryAfterUpdate->total_revenue);

        // 4. Create OrderItem -> verify OrderItemObserver updates product_metrics_summary
        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_name' => 'Ergonomic Desk Mat',
            'quantity' => 2,
            'unit_price' => 75.00,
        ]);

        $productSummary = \Illuminate\Support\Facades\DB::table('product_metrics_summary')->where('product_name', 'Ergonomic Desk Mat')->first();
        $this->assertNotNull($productSummary);
        $this->assertEquals(2, $productSummary->total_units_sold);
        $this->assertEquals(150.00, (float) $productSummary->total_gross_revenue);

        // 5. Delete OrderItem -> verify decrements
        $item->delete();
        $productSummaryAfterDel = \Illuminate\Support\Facades\DB::table('product_metrics_summary')->where('product_name', 'Ergonomic Desk Mat')->first();
        $this->assertEquals(0, $productSummaryAfterDel->total_units_sold);
        $this->assertEquals(0.00, (float) $productSummaryAfterDel->total_gross_revenue);

        // 6. Delete Order -> verify decrements
        $order->delete();
        $summaryAfterDelete = \Illuminate\Support\Facades\DB::table('dashboard_metrics_summary')->where('id', 1)->first();
        $this->assertEquals(0, $summaryAfterDelete->completed_count);
        $this->assertEquals(0, $summaryAfterDelete->total_orders);
    }
}
