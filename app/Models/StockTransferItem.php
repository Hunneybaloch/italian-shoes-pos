<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class StockTransferItem extends Model
{
    protected $fillable = ['stock_transfer_id', 'product_variant_id', 'quantity', 'received_quantity'];
    protected function casts(): array { return ['quantity' => 'integer', 'received_quantity' => 'integer']; }
    public function transfer(): BelongsTo { return $this->belongsTo(StockTransfer::class, 'stock_transfer_id'); }
    public function productVariant(): BelongsTo { return $this->belongsTo(ProductVariant::class); }
}
