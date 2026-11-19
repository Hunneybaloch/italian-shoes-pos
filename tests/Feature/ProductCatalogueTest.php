<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCatalogueTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->owner = User::create([
            'name' => 'Catalogue Owner',
            'username' => 'catalogue-owner',
            'email' => 'catalogue-owner@example.test',
            'password' => 'password123',
            'is_active' => true,
        ]);
        $this->owner->roles()->attach(Role::where('slug', 'owner')->value('id'));

        $this->category = Category::create([
            'name' => 'Formal Shoes',
            'slug' => 'formal-shoes',
            'is_active' => true,
        ]);
    }

    public function test_owner_can_create_category_brand_product_and_multiple_variants(): void
    {
        $this->actingAs($this->owner)
            ->post(route('products.categories.store'), ['name' => 'Casual Shoes'])
            ->assertRedirect();
        $this->assertDatabaseHas('categories', ['name' => 'Casual Shoes', 'slug' => 'casual-shoes']);

        $this->actingAs($this->owner)
            ->post(route('products.brands.store'), ['name' => 'Italian Shoes'])
            ->assertRedirect();
        $brand = Brand::where('name', 'Italian Shoes')->firstOrFail();

        $this->actingAs($this->owner)
            ->post(route('products.store'), [
                'product' => [
                    'name' => 'Classic Leather Formal',
                    'category_id' => $this->category->id,
                    'brand_id' => $brand->id,
                    'model_code' => 'CLS-100',
                    'description' => 'Classic leather shoe.',
                ],
                'variants' => [
                    ['size' => '41', 'color' => 'Black', 'sku' => 'CLS-100-41-BLK', 'barcode' => '100000000041', 'cost_price' => '2500', 'selling_price' => '4500'],
                    ['size' => '42', 'color' => 'Black', 'sku' => 'CLS-100-42-BLK', 'barcode' => null, 'cost_price' => '2500', 'selling_price' => '4500'],
                ],
            ])
            ->assertRedirect(route('products.index'));

        $product = Product::where('model_code', 'CLS-100')->firstOrFail();
        $this->assertSame(2, $product->variants()->count());
        $this->assertDatabaseHas('product_variants', ['sku' => 'CLS-100-41-BLK', 'selling_price' => '4500.00']);
        $this->assertDatabaseHas('product_variants', ['sku' => 'CLS-100-42-BLK', 'barcode' => null]);
    }

    public function test_cashier_can_view_catalogue_but_cannot_create_products(): void
    {
        $cashier = User::create([
            'name' => 'Cashier',
            'username' => 'catalogue-cashier',
            'email' => 'catalogue-cashier@example.test',
            'password' => 'password123',
            'is_active' => true,
        ]);
        $cashier->roles()->attach(Role::where('slug', 'cashier')->value('id'));

        $this->actingAs($cashier)->get(route('products.index'))->assertOk();
        $this->actingAs($cashier)->get(route('products.create'))->assertForbidden();
    }

    public function test_stock_manager_can_open_product_creation_form(): void
    {
        $manager = User::create([
            'name' => 'Stock Manager',
            'username' => 'catalogue-stock-manager',
            'email' => 'catalogue-stock@example.test',
            'password' => 'password123',
            'is_active' => true,
        ]);
        $manager->roles()->attach(Role::where('slug', 'stock-manager')->value('id'));

        $this->actingAs($manager)
            ->get(route('products.create'))
            ->assertOk()
            ->assertSee('Add New Product');
    }

    public function test_duplicate_skus_within_a_submission_are_rejected(): void
    {
        $this->actingAs($this->owner)
            ->from(route('products.create'))
            ->post(route('products.store'), [
                'product' => ['name' => 'Duplicate SKU Shoe', 'category_id' => $this->category->id],
                'variants' => [
                    ['size' => '40', 'color' => 'Brown', 'sku' => 'DUPLICATE-SKU', 'cost_price' => '1000', 'selling_price' => '2000'],
                    ['size' => '41', 'color' => 'Brown', 'sku' => 'DUPLICATE-SKU', 'cost_price' => '1000', 'selling_price' => '2000'],
                ],
            ])
            ->assertSessionHasErrors('variants');

        $this->assertDatabaseMissing('products', ['name' => 'Duplicate SKU Shoe']);
    }

    public function test_product_update_changes_variant_details(): void
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Update Test Shoe',
            'model_code' => 'UPD-100',
            'is_active' => true,
        ]);
        $variant = $product->variants()->create([
            'size' => '40',
            'color' => 'Black',
            'sku' => 'UPD-100-40-BLK',
            'cost_price' => '1500',
            'selling_price' => '3000',
            'is_active' => true,
        ]);

        $this->actingAs($this->owner)
            ->put(route('products.update', $product), [
                'product' => ['name' => 'Updated Shoe', 'category_id' => $this->category->id, 'model_code' => 'UPD-100'],
                'variants' => [
                    ['id' => $variant->id, 'size' => '40', 'color' => 'Black', 'sku' => 'UPD-100-40-BLK', 'cost_price' => '1600', 'selling_price' => '3200'],
                ],
            ])
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Updated Shoe']);
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'cost_price' => '1600.00', 'selling_price' => '3200.00']);
    }
}
