<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;

class Sale extends Model
{
    protected $fillable = ['receipt_number', 'branch_id', 'customer_id', 'cashier_id', 'status', 'subtotal', 'discount_amount', 'tax_amount', 'total_amount', 'amount_paid', 'change_amount', 'notes', 'sold_at'];
    protected function casts(): array { return ['subtotal'=>'decimal:2','discount_amount'=>'decimal:2','tax_amount'=>'decimal:2','total_amount'=>'decimal:2','amount_paid'=>'decimal:2','change_amount'=>'decimal:2','sold_at'=>'datetime']; }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function cashier(): BelongsTo { return $this->belongsTo(User::class, 'cashier_id'); }
    public function items(): HasMany { return $this->hasMany(SaleItem::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
}
