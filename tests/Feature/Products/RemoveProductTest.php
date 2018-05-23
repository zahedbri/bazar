<?php

namespace Tests\Feature\Products;

use Tests\TestCase;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RemoveProductTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_remove_a_product()
    {
        $product = $this->create('Product');

        $this->json('DELETE', route('products.destroy', $product->id))
            ->assertStatus(401);

        $this->assertNotNull($product->fresh());
    }

    /** @test */
    public function a_user_cannot_remove_a_product_from_a_brand_he_does_not_own()
    {
        $this->signIn();
        $product = $this->create('Product');

        $response = $this->json('DELETE', route('products.destroy', $product->id));

        $response->assertStatus(404);
        $this->assertNotNull($product->fresh());
    }

    /** @test */
    public function a_user_can_remove_a_product_from_his_brand()
    {
        $brand = $this->brandForSignedInUser();
        $product = $this->createProductsForModel([
            'brand_id' => $brand->id
        ])->addItems(4);

        $item = $product->items->first();

        $response = $this->json('DELETE', route('products.destroy', $product->id));

        $response->assertStatus(200);
        $this->assertTrue($product->fresh()->trashed());
        $this->assertEquals(0, Product::withTrashed()->findOrFail($product->id)->itemsRemaining());
    }

    /** @test */
    public function if_a_model_has_all_of_its_products_removed_it_gets_unpublished()
    {
        $brand = $this->brandForSignedInUser();
        $product = $this->createProductsForModel([
            'brand_id' => $brand->id
        ]);

        $response = $this->json('DELETE', route('products.destroy', $product->id));

        $this->assertFalse($product->model->published);
    }
}