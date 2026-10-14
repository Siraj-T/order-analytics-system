<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModelRelationshipTest extends TestCase
{
    public function test_customer_has_orders_relation(): void
    {
        $customer = new Customer();
        $this->assertInstanceOf(HasMany::class, $customer->orders());
    }

    public function test_order_belongs_to_customer(): void
    {
        $order = new Order();
        $this->assertInstanceOf(BelongsTo::class, $order->customer());
    }

    public function test_order_has_items_relation(): void
    {
        $order = new Order();
        $this->assertInstanceOf(HasMany::class, $order->items());
    }

    public function test_order_item_belongs_to_order(): void
    {
        $item = new OrderItem();
        $this->assertInstanceOf(BelongsTo::class, $item->order());
    }
}
