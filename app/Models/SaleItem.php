<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    protected $fillable = ['sale_id','product_variant_id','product_name','sku','size','color','quantity','unit_price','unit_cost','line_total'];
    protected function casts(): array { return ['quantity'=>'integer','unit_price'=>'decimal:2','unit_cost'=>'decimal:2','line_total'=>'decimal:2']; }
    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
    public function productVariant(): BelongsTo { return $this->belongsTo(ProductVariant::class); }
}
