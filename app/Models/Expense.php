<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class Expense extends Model
{
    protected $fillable=['branch_id','created_by','category','description','amount','payment_method','reference','expense_date'];
    protected function casts(): array { return ['amount'=>'decimal:2','expense_date'=>'date']; }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class,'created_by'); }
}
