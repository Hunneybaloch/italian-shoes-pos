<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer', 'exists:categories,id'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $products = Product::with(['category', 'brand', 'variants'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('model_code', 'like', "%{$search}%")
                        ->orWhereHas('variants', fn ($v) => $v->where('sku', 'like', "%{$search}%")->orWhere('barcode', 'like', "%{$search}%"));
                });
            })
            ->when($filters['category'] ?? null, fn ($q, $id) => $q->where('category_id', $id))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('is_active', $status === 'active'))
            ->latest()->paginate(15)->withQueryString();

        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $brands = Brand::where('is_active', true)->orderBy('name')->get();

        return view('products.index', compact('products', 'categories', 'brands'));
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:categories,name'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        Category::create($data + [
            'slug' => $this->uniqueSlug(Category::class, $data['name']),
            'is_active' => true,
        ]);

        return back()->with('success', 'Category created successfully.');
    }

    public function storeBrand(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:brands,name'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        Brand::create($data + [
            'slug' => $this->uniqueSlug(Brand::class, $data['name']),
            'is_active' => true,
        ]);

        return back()->with('success', 'Brand created successfully.');
    }

    public function create()
    {
        return view('products.create', [
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
            'brands' => Brand::where('is_active', true)->orderBy('name')->get(),
            'product' => new Product(),
            'variants' => collect([(object) ['size' => '', 'color' => '', 'sku' => '', 'barcode' => '', 'cost_price' => '0.00', 'selling_price' => '']]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedProduct($request);

        DB::transaction(function () use ($data) {
            $product = Product::create($data['product']);
            foreach ($data['variants'] as $variant) {
                $product->variants()->create($variant + ['is_active' => true]);
            }
        });

        return redirect()->route('products.index')->with('success', 'Product and variants created successfully.');
    }

    public function edit(Product $product)
    {
        return view('products.create', [
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
            'brands' => Brand::where('is_active', true)->orderBy('name')->get(),
            'product' => $product,
            'variants' => $product->variants()->orderBy('id')->get(),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validatedProduct($request, $product);

        DB::transaction(function () use ($data, $product) {
            $product->update($data['product']);
            foreach ($data['variants'] as $variant) {
                $id = $variant['id'] ?? null;
                unset($variant['id']);
                if ($id) {
                    $product->variants()->whereKey($id)->firstOrFail()->update($variant);
                } else {
                    $product->variants()->create($variant + ['is_active' => true]);
                }
            }
        });

        return redirect()->route('products.index')->with('success', 'Product updated successfully.');
    }

    public function toggleStatus(Product $product)
    {
        $product->update(['is_active' => ! $product->is_active]);

        return back()->with('success', 'Product status updated successfully.');
    }

    private function validatedProduct(Request $request, ?Product $product = null): array
    {
        $validated = $request->validate([
            'product' => ['required', 'array'],
            'product.name' => ['required', 'string', 'max:180'],
            'product.category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
            'product.brand_id' => ['nullable', 'integer', Rule::exists('brands', 'id')->where('is_active', true)],
            'product.model_code' => ['nullable', 'string', 'max:80', Rule::unique('products', 'model_code')->ignore($product?->id)],
            'product.description' => ['nullable', 'string', 'max:5000'],
            'variants' => ['required', 'array', 'min:1', 'max:100'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.size' => ['required', 'string', 'max:20'],
            'variants.*.color' => ['required', 'string', 'max:60'],
            'variants.*.sku' => ['required', 'string', 'max:80'],
            'variants.*.barcode' => ['nullable', 'string', 'max:100'],
            'variants.*.cost_price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'variants.*.selling_price' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
        ]);

        $rows = collect($validated['variants'])->values();
        if ($rows->pluck('sku')->duplicates()->isNotEmpty()
            || $rows->pluck('barcode')->filter()->duplicates()->isNotEmpty()
            || $rows->map(fn ($v) => strtolower(trim($v['size']).'|'.trim($v['color'])))->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages([
                'variants' => 'Each SKU, barcode and size/colour combination must be unique.',
            ]);
        }

        foreach ($rows as $index => $row) {
            if (! empty($row['id']) && (! $product || ! $product->variants()->whereKey($row['id'])->exists())) {
                throw ValidationException::withMessages(["variants.{$index}.id" => 'This variant does not belong to the selected product.']);
            }

            $request->validate([
                "variants.{$index}.sku" => ['required', 'string', 'max:80', Rule::unique('product_variants', 'sku')->ignore($row['id'] ?? null)],
                "variants.{$index}.barcode" => ['nullable', 'string', 'max:100', Rule::unique('product_variants', 'barcode')->ignore($row['id'] ?? null)],
            ]);
        }

        return [
            'product' => [
                'name' => $validated['product']['name'],
                'category_id' => $validated['product']['category_id'],
                'brand_id' => ($validated['product']['brand_id'] ?? null) ?: null,
                'model_code' => $validated['product']['model_code'] ?: null,
                'description' => $validated['product']['description'] ?? null,
            ],
            'variants' => $rows->map(fn ($row) => [
                'id' => $row['id'] ?? null,
                'size' => trim($row['size']),
                'color' => trim($row['color']),
                'sku' => trim($row['sku']),
                'barcode' => filled($row['barcode'] ?? null) ? trim($row['barcode']) : null,
                'cost_price' => $row['cost_price'],
                'selling_price' => $row['selling_price'],
            ])->all(),
        ];
    }

    private function uniqueSlug(string $model, string $name): string
    {
        $base = Str::slug($name) ?: 'item';
        $slug = $base;
        $suffix = 2;
        while ($model::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }
        return $slug;
    }
}
