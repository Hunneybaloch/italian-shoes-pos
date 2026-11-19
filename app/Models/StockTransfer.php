<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransfer extends Model
{
    protected $fillable = ['transfer_number', 'from_branch_id', 'to_branch_id', 'requested_by', 'approved_by', 'received_by', 'status', 'approved_at', 'received_at', 'notes'];
    protected function casts(): array { return ['approved_at' => 'datetime', 'received_at' => 'datetime']; }
    public function fromBranch(): BelongsTo { return $this->belongsTo(Branch::class, 'from_branch_id'); }
    public function toBranch(): BelongsTo { return $this->belongsTo(Branch::class, 'to_branch_id'); }
    public function items(): HasMany { return $this->hasMany(StockTransferItem::class); }
}
