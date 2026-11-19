<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = ['branch_id', 'product_variant_id', 'user_id', 'movement_type', 'quantity_change', 'reference_type', 'reference_id', 'notes'];

    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function productVariant(): BelongsTo { return $this->belongsTo(ProductVariant::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
