<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'user_id',
        'created_by',
        'confirmed_by',
        'confirmed_at',
        'type',
        'quantity',
        'date',
        'status',
        'stock_before',
        'stock_after',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'quantity' => 'integer',
        'stock_before' => 'integer',
        'stock_after' => 'integer',
        'confirmed_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($transaction) {
            if (empty($transaction->created_by) && !empty($transaction->user_id)) {
                $transaction->created_by = $transaction->user_id;
            }
            if (empty($transaction->user_id) && !empty($transaction->created_by)) {
                $transaction->user_id = $transaction->created_by;
            }
        });
    }

    /**
     * Get the product associated with this transaction.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user who created / recorded this transaction.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withDefault(function ($instance, $parent) {
            if ($parent->user_id) {
                return User::find($parent->user_id) ?? new User(['name' => 'Petugas', 'role' => 'admin']);
            }
            return new User(['name' => 'Petugas', 'role' => 'admin']);
        });
    }

    /**
     * Get the staff user who physically confirmed this transaction.
     */
    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /**
     * Backwards compatibility: Get the user who recorded this transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withDefault(function ($instance, $parent) {
            return User::find($parent->user_id) ?? new User(['name' => 'Petugas', 'role' => 'admin']);
        });
    }

    /**
     * Scope: filter by transaction type (in/out/adjustment).
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: filter by status.
     */
    public function scopeOfStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: only completed/confirmed transactions (Diterima / Dikeluarkan).
     */
    public function scopeCompleted($query)
    {
        return $query->whereIn('status', ['Diterima', 'Dikeluarkan', 'completed']);
    }

    /**
     * Scope: only pending transactions awaiting confirmation.
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', ['Pending', 'pending']);
    }
}
