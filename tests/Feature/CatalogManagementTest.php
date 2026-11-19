<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->owner=User::create(['name'=>'Catalog Owner','username'=>'catalog-owner','email'=>'catalog-owner@example.test','password'=>'password123','is_active'=>true]);
        $this->owner->roles()->attach(Role::where('slug','owner')->value('id'));
    }

    public function test_owner_can_rename_and_deactivate_category_and_brand(): void
    {
        $category=Category::create(['name'=>'Old Category','slug'=>'old-category','is_active'=>true]);
        $brand=Brand::create(['name'=>'Old Brand','slug'=>'old-brand','is_active'=>true]);

        $this->actingAs($this->owner)->put(route('catalog.categories.update',$category),[
            'name'=>'New Category','description'=>'Updated category',
        ])->assertRedirect();
        $this->actingAs($this->owner)->patch(route('catalog.categories.toggle',$category))->assertRedirect();

        $this->actingAs($this->owner)->put(route('catalog.brands.update',$brand),[
            'name'=>'New Brand','description'=>'Updated brand',
        ])->assertRedirect();
        $this->actingAs($this->owner)->patch(route('catalog.brands.toggle',$brand))->assertRedirect();

        $this->assertDatabaseHas('categories',['id'=>$category->id,'name'=>'New Category','is_active'=>false]);
        $this->assertDatabaseHas('brands',['id'=>$brand->id,'name'=>'New Brand','is_active'=>false]);
    }
}
