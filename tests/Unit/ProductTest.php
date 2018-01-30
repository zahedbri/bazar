<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Exceptions\NotEnoughItemsException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function can_get_a_products_price_with_decimals()
    {
        $product = factory(Product::class)->make([
            'price' => 6750,
        ]);

        $this->assertEquals('67.50', $product->price_with_decimals);
    }

    /** @test */
    public function can_order_items()
    {
        $product = factory(Product::class)->create()->addItems(3);

        $order = $product->orderItems('customer@example.com', 3);

        $this->assertEquals('customer@example.com', $order->email);
        $this->assertEquals(3, $order->itemQuantity());
    }

    /** @test */
    public function can_add_items()
    {
        $product = factory(Product::class)->create()->addItems(20);

        $this->assertEquals(20, $product->itemsRemaining());
    }

    /** @test */
    public function items_remaining_does_not_include_items_associated_with_an_order()
    {
        $product = factory(Product::class)->create()->addItems(20);
        $product->orderItems('jane@example.com', 12);

        $this->assertEquals(8, $product->itemsRemaining());
    }

    /** @test */
    public function trying_to_purchase_more_items_than_remain_throws_an_exception()
    {
        $product = factory(Product::class)->create()->addItems(10);

        try {
            $product->orderItems('customer@example.com', 11);
        } catch (NotEnoughItemsException $e) {
            $order = $product->orders()->where('email', 'customer@example.com')->first();
            $this->assertNull($order);
            $this->assertEquals(10, $product->itemsRemaining());
            return;
        }

        $this->fail('Order succeeded even though there were not enough items remaining.');
    }

    /** @test */
    public function cannot_order_items_that_have_already_been_purchased()
    {
        $product = factory(Product::class)->create()->addItems(10);
        $product->orderItems('customer@example.com', 8);

        try {
            $product->orderItems('client@example.com', 3);
        } catch (NotEnoughItemsException $e) {
            $clientsOrder = $product->orders()->where('email', 'client@example.com')->first();
            $this->assertNull($clientsOrder);
            $this->assertEquals(2, $product->itemsRemaining());
            return;
        }

        $this->fail('Order succeeded even though there were not enough items remaining.');
    }
}