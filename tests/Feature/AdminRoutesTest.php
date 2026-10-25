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
        $response->assertSee('Page 1 of 2');
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
}
