<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPayment extends Model
{
    protected $fillable=['supplier_id','purchase_id','branch_id','created_by','amount','payment_method','reference','paid_at'];
    protected function casts(): array { return ['amount'=>'decimal:2','paid_at'=>'datetime']; }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function purchase(): BelongsTo { return $this->belongsTo(Purchase::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
}
