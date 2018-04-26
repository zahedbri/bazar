<?php

namespace Tests\Feature\Products;

use Tests\TestCase;
use App\Models\Brand;
use App\Models\Product;
use App\Jobs\ProcessProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AddProductTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_user_can_view_the_add_product_form_for_his_brand()
    {
        $brand = $this->brandForSignedInUser();

        $response = $this->get(route('products.create', $brand));

        $response->assertStatus(200);
    }

    /** @test */
    public function a_user_cannot_view_the_add_product_form_for_a_brand_he_does_not_own()
    {
        $this->signIn();
        $brand = $this->create('Brand');

        $response = $this->get(route('products.create', $brand));

        $response->assertStatus(404);
    }

    /** @test */
    public function guests_cannot_view_the_add_product_form_for_a_brand()
    {
        $brand = $this->create('Brand');

        $this->get(route('products.create', $brand))
            ->assertStatus(302)
            ->assertRedirect(route('login'));
    }

    /** @test */
    public function a_user_can_add_a_product_to_his_brand()
    {
        Storage::fake('public');
        $brand = $this->brandForSignedInUser();

        $response = $this->post(route('products.store', $brand), [
            'name'          => 'iPhone 8',
            'description'   => 'The new iPhone',
            'price'         => '700.50',
            'published'     => true,
            'item_quantity' => 20,
            'product_image' => UploadedFile::fake()->image('product-image.png')
        ]);

        $product = Product::first();

        $response->assertStatus(302)
            ->assertRedirect(route('products.show', [$product->model->brand_id, $product]));

        $this->assertEquals('iPhone 8', $product->model->name);
        $this->assertEquals('The new iPhone', $product->model->description);
        $this->assertTrue($product->model->published);
        $this->assertTrue($product->model->brand->is($brand));
        $this->assertEquals(70050, $product->price);
        $this->assertEquals(20, $product->itemsRemaining());
    }

    private function validParams($overrides = [])
    {
        Storage::fake('public');

        return array_merge([
            'name'          => 'iPhone 8',
            'description'   => 'The new iPhone',
            'price'         => '700.50',
            'published'     => true,
            'item_quantity' => 20,
            'product_image' => UploadedFile::fake()->image('product-image.png')
        ], $overrides);
    }

    /** @test */
    public function a_user_cannot_add_a_product_to_a_brand_he_does_not_own()
    {
        $this->signIn();
        $brand = $this->create('Brand');

        $response = $this->post(route('products.store', $brand), $this->validParams());

        $response->assertStatus(404);
        $this->assertEquals(0, Product::count());
    }

    /** @test */
    public function a_guest_cannot_add_a_product()
    {
        $brand = $this->create('Brand');

        $this->post(route('products.store', $brand), $this->validParams())
            ->assertStatus(302)
            ->assertRedirect(route('login'));

        $this->assertEquals(0, Product::count());
    }

    /** @test */
    public function product_image_is_uploaded()
    {
        Storage::fake('public');
        Queue::fake();
        $brand = $this->brandForSignedInUser();
        $file = UploadedFile::fake()->image('product-image.png');

        $response = $this->post(route('products.store', $brand), $this->validParams([
            'product_image' => $file
        ]));
        $product = Product::first();

        $this->assertNotNull($product->model->image_path);
        Storage::disk('public')->assertExists($product->model->image_path);
        $this->assertFileEquals($file->getPathname(), Storage::disk('public')->path($product->model->image_path));
    }

    /** @test */
    public function an_image_optimizer_job_is_queued_when_a_product_is_created()
    {
        Queue::fake();
        $brand = $this->brandForSignedInUser();

        $response = $this->post(route('products.store', $brand), $this->validParams());

        $product = Product::first();

        Queue::assertPushed(ProcessProductImage::class, function ($job) use ($product) {
            return $job->product->is($product);
        });
    }

    /** @test */
    public function name_is_required_to_create_a_product()
    {
        $brand = $this->brandForSignedInUser();

        $response = $this->from(route('products.create', $brand))->post(route('products.store', $brand), $this->validParams([
            'name' => ''
        ]));

        $this->assertValidationError($response, 'name', route('products.create', $brand));
        $this->assertEquals(0, Product::count());
    }

    /** @test */
    public function description_is_required_to_create_a_product()
    {
        $brand = $this->brandForSignedInUser();

        $response = $this->from(route('products.create', $brand))->post(route('products.store', $brand), $this->validParams([
            'description' => ''
        ]));

        $this->assertValidationError($response, 'description', route('products.create', $brand));
        $this->assertEquals(0, Product::count());
    }

    /** @test */
    public function price_is_required_to_create_a_product()
    {
        $brand = $this->brandForSignedInUser();

        $response = $this->from(route('products.create', $brand))->post(route('products.store', $brand), $this->validParams([
            'price' => ''
        ]));

        $this->assertValidationError($response, 'price', route('products.create', $brand));
        $this->assertEquals(0, Product::count());
    }

    /** @test */
    public function price_must_be_numeric_to_create_a_product()
    {
        $brand = $this->brandForSignedInUser();

        $response = $this->from(route('products.create', $brand))->post(route('products.store', $brand), $this->validParams([
            'price' => 'not-numeric'
        ]));

        $this->assertValidationError($response, 'price', route('products.create', $brand));
        $this->assertEquals(0, Product::count());
    }

    /** @test */
    public function price_must_be_0_or_more_to_create_a_product()
    {
        $brand = $this->brandForSignedInUser();

        $response = $this->from(route('products.create', $brand))->post(route('products.store', $brand), $this->validParams([
            'price' => '-1'
        ]));

        $this->assertValidationError($response, 'price', route('products.create', $brand));
        $this->assertEquals(0, Product::count());
    }

    /** @test */
    public function published_is_optional_to_create_a_product()
    {
        $brand = $this->brandForSignedInUser();

        $response = $this->from(route('products.create', $brand))->post(route('products.store', $brand), $this->validParams([
            'published' => null,
        ]));

        $product = Product::first();

        $response->assertStatus(302)
            ->assertRedirect(route('products.show', [$product->model->brand_id, $product]));

        $this->assertFalse($product->model->published);
    }

    /** @test */
    public function published_must_be_boolean_to_create_a_product()
    {
        $brand = $this->brandForSignedInUser();

        $response = $this->from(route('products.create', $brand))->post(route('products.store', $brand), $this->validParams([
            'published' => 'not-a-boolean'
        ]));

        $this->assertValidationError($response, 'published', route('products.create', $brand));
        $this->assertEquals(0, Product::count());
    }

    /** @test */
    public function item_quantity_is_required_to_create_a_product()
    {
        $brand = $this->brandForSignedInUser();

        $response = $this->from(route('products.create', $brand))->post(route('products.store', $brand), $this->validParams([
            'item_quantity' => ''
        ]));

        $this->assertValidationError($response, 'item_quantity', route('products.create', $brand));
        $this->assertEquals(0, Product::count());
    }

    /** @test */
    public function item_quantity_must_be_an_integer_to_create_a_product()
    {
        $brand = $this->brandForSignedInUser();

        $response = $this->from(route('products.create', $brand))->post(route('products.store', $brand), $this->validParams([
            'item_quantity' => '1.3'
        ]));

        $this->assertValidationError($response, 'item_quantity', route('products.create', $brand));
        $this->assertEquals(0, Product::count());
    }

    /** @test */
    public function item_quantity_must_be_0_or_more_to_create_a_product()
    {
        $brand = $this->brandForSignedInUser();

        $response = $this->from(route('products.create', $brand))->post(route('products.store', $brand), $this->validParams([
            'item_quantity' => '-1'
        ]));

        $this->assertValidationError($response, 'item_quantity', route('products.create', $brand));
        $this->assertEquals(0, Product::count());
    }

    /** @test */
    public function product_image_must_be_an_image()
    {
        Storage::fake('public');
        $brand = $this->brandForSignedInUser();
        $file = UploadedFile::fake()->create('not-an-image.pdf');

        $response = $this->from(route('products.create', $brand))->post(route('products.store', $brand), $this->validParams([
            'product_image' => $file
        ]));

        $this->assertValidationError($response, 'product_image', route('products.create', $brand));
        $this->assertEquals(0, Product::count());
    }

    /** @test */
    public function product_image_is_required_to_create_a_product()
    {
        $brand = $this->brandForSignedInUser();

        $response = $this->from(route('products.create', $brand))->post(route('products.store', $brand), $this->validParams([
            'product_image' => null
        ]));

        $this->assertValidationError($response, 'product_image', route('products.create', $brand));
        $this->assertEquals(0, Product::count());
    }
}
