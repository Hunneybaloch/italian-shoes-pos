<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CatalogController extends Controller
{
    public function index()
    {
        $categories=Category::withCount('products')->orderBy('name')->paginate(20,['*'],'categories_page');
        $brands=Brand::withCount('products')->orderBy('name')->paginate(20,['*'],'brands_page');
        return view('catalog.index',compact('categories','brands'));
    }

    public function updateCategory(Request $request, Category $category)
    {
        $data=$request->validate([
            'name'=>['required','string','max:120',Rule::unique('categories','name')->ignore($category->id)],
            'description'=>['nullable','string','max:2000'],
        ]);
        $category->update($data);
        return back()->with('success','Category updated.');
    }

    public function toggleCategory(Category $category)
    {
        $category->update(['is_active'=>!$category->is_active]);
        return back()->with('success','Category status updated.');
    }

    public function updateBrand(Request $request, Brand $brand)
    {
        $data=$request->validate([
            'name'=>['required','string','max:120',Rule::unique('brands','name')->ignore($brand->id)],
            'description'=>['nullable','string','max:2000'],
        ]);
        $brand->update($data);
        return back()->with('success','Brand updated.');
    }

    public function toggleBrand(Brand $brand)
    {
        $brand->update(['is_active'=>!$brand->is_active]);
        return back()->with('success','Brand status updated.');
    }
}
