<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;

class SaleReturn extends Model
{
    protected $fillable = ['return_number','sale_id','branch_id','processed_by','return_type','status','reason','returned_value','refund_amount','refund_method','processed_at'];
    protected function casts(): array { return ['returned_value'=>'decimal:2','refund_amount'=>'decimal:2','processed_at'=>'datetime']; }
    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function processor(): BelongsTo { return $this->belongsTo(User::class,'processed_by'); }
    public function items(): HasMany { return $this->hasMany(SaleReturnItem::class); }
}
