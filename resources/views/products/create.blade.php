<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $product->exists ? 'Edit Product' : 'Add Product' }} | Italian Shoes POS</title><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"><style>
*{box-sizing:border-box}body{margin:0;background:#f4f6fa;color:#1b2940;font-family:'DM Sans',sans-serif}.wrap{max-width:1100px;margin:30px auto;padding:0 18px}.panel{background:white;border:1px solid #e9edf3;border-radius:12px;padding:25px;margin-bottom:18px}.back{color:#1c568b;text-decoration:none;font-size:13px;font-weight:700}.heading{margin:18px 0 22px}.heading h1{margin:0 0 5px;font-size:25px}.muted{color:#8591a2;font-size:13px}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:15px}.field label{display:block;font-size:12px;font-weight:700;margin-bottom:7px}.field input,.field select,.field textarea{width:100%;padding:11px;border:1px solid #dce2eb;border-radius:7px;font:inherit;font-size:13px;background:white}.field textarea{min-height:85px}.full{grid-column:1/-1}.variant{display:grid;grid-template-columns:repeat(6,minmax(90px,1fr)) auto;gap:8px;align-items:end;padding:13px;background:#f7f9fc;border:1px solid #edf1f6;border-radius:9px;margin-bottom:10px}.variant label{font-size:10px;font-weight:700;display:block;margin-bottom:5px}.variant input{width:100%;min-width:0;padding:9px;border:1px solid #dce2eb;border-radius:6px;font:inherit;font-size:12px}.btn{border:0;border-radius:8px;padding:11px 16px;background:#173d65;color:white;font:inherit;font-size:12px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-block}.light{background:#edf2f8;color:#254d75}.actions{display:flex;justify-content:flex-end;gap:9px}.errors{background:#fff0ef;color:#9b3028;padding:12px;border-radius:8px;margin-bottom:15px;font-size:13px}.required{color:#c0392b}@media(max-width:850px){.variant{grid-template-columns:repeat(3,minmax(0,1fr))}}@media(max-width:560px){.grid{grid-template-columns:1fr}.full{grid-column:auto}.variant{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style></head><body><main class="wrap"><a class="back" href="{{ route('products.index') }}">← Back to Products</a><div class="heading"><h1>{{ $product->exists ? 'Edit Product' : 'Add New Product' }}</h1><p class="muted">Enter the shoe model details and its size/colour variants.</p></div>
@if($errors->any())<div class="errors">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<form method="POST" action="{{ $product->exists ? route('products.update',$product) : route('products.store') }}">@csrf @if($product->exists)@method('PUT')@endif
<section class="panel"><h3>Product Information</h3><div class="grid">
<div class="field"><label>Product Name <span class="required">*</span></label><input name="product[name]" value="{{ old('product.name',$product->name) }}" required maxlength="180" placeholder="e.g. Men's Classic Formal Shoe"></div>
<div class="field"><label>Category <span class="required">*</span></label><select name="product[category_id]" required><option value="">Select category</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string)old('product.category_id',$product->category_id)===(string)$category->id)>{{ $category->name }}</option>@endforeach</select></div>
<div class="field"><label>Brand</label><select name="product[brand_id]"><option value="">No brand</option>@foreach($brands as $brand)<option value="{{ $brand->id }}" @selected((string)old('product.brand_id',$product->brand_id)===(string)$brand->id)>{{ $brand->name }}</option>@endforeach</select></div>
<div class="field"><label>Model Code</label><input name="product[model_code]" value="{{ old('product.model_code',$product->model_code) }}" maxlength="80" placeholder="Optional model reference"></div>
<div class="field full"><label>Description</label><textarea name="product[description]" placeholder="Product description">{{ old('product.description',$product->description) }}</textarea></div>
</div></section>
<section class="panel"><h3>Sizes, Colours & Pricing</h3><p class="muted" style="margin:5px 0 16px">Each size and colour combination is a separate variant with its own SKU and price.</p>
<div id="variants">@foreach($variants as $i=>$variant)<div class="variant">
@if(isset($variant->id))<input type="hidden" name="variants[{{ $i }}][id]" value="{{ $variant->id }}">@endif
<div><label>Size *</label><input name="variants[{{ $i }}][size]" value="{{ old("variants.$i.size",$variant->size) }}" required placeholder="e.g. 42"></div>
<div><label>Colour *</label><input name="variants[{{ $i }}][color]" value="{{ old("variants.$i.color",$variant->color) }}" required placeholder="e.g. Black"></div>
<div><label>SKU *</label><input name="variants[{{ $i }}][sku]" value="{{ old("variants.$i.sku",$variant->sku) }}" required placeholder="Unique SKU"></div>
<div><label>Barcode</label><input name="variants[{{ $i }}][barcode]" value="{{ old("variants.$i.barcode",$variant->barcode) }}" placeholder="Optional"></div>
<div><label>Cost (PKR) *</label><input type="number" step="0.01" min="0" name="variants[{{ $i }}][cost_price]" value="{{ old("variants.$i.cost_price",$variant->cost_price) }}" required></div>
<div><label>Sale Price (PKR) *</label><input type="number" step="0.01" min="0.01" name="variants[{{ $i }}][selling_price]" value="{{ old("variants.$i.selling_price",$variant->selling_price) }}" required></div>
@if(isset($variant->id))<span class="muted">Existing</span>@else<button type="button" class="btn light remove-variant">Remove</button>@endif
</div>@endforeach</div>
<button type="button" class="btn light" id="add-variant">＋ Add Another Size / Colour</button>
</section><div class="actions"><a class="btn light" href="{{ route('products.index') }}">Cancel</a><button class="btn" type="submit">{{ $product->exists ? 'Save Changes' : 'Create Product' }}</button></div>
</form></main>
<script>
let nextVariantIndex = {{ count($variants) }};
document.getElementById('add-variant').addEventListener('click', () => {
 const i = nextVariantIndex++;
 const row = document.createElement('div');
 row.className = 'variant';
 row.innerHTML = '<div><label>Size *</label><input name="variants['+i+'][size]" required placeholder="e.g. 42"></div>'+
 '<div><label>Colour *</label><input name="variants['+i+'][color]" required placeholder="e.g. Black"></div>'+
 '<div><label>SKU *</label><input name="variants['+i+'][sku]" required placeholder="Unique SKU"></div>'+
 '<div><label>Barcode</label><input name="variants['+i+'][barcode]" placeholder="Optional"></div>'+
 '<div><label>Cost (PKR) *</label><input type="number" step="0.01" min="0" name="variants['+i+'][cost_price]" value="0.00" required></div>'+
 '<div><label>Sale Price (PKR) *</label><input type="number" step="0.01" min="0.01" name="variants['+i+'][selling_price]" required></div>'+
 '<button type="button" class="btn light remove-variant">Remove</button>';
 document.getElementById('variants').appendChild(row);
});
document.getElementById('variants').addEventListener('click', event => {
 if (event.target.classList.contains('remove-variant')) event.target.closest('.variant').remove();
});
</script></body></html>