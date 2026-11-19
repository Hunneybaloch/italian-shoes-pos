<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashTransaction extends Model
{
    protected $fillable=['branch_id','created_by','direction','type','amount','payment_method','reference_type','reference_id','description','transacted_at'];
    protected function casts(): array { return ['amount'=>'decimal:2','transacted_at'=>'datetime']; }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class,'created_by'); }
}
