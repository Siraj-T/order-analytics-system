<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'transaction_reference',
        'payment_method',
        'amount',
        'currency_code',
        'status',
        'gateway_payload',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_payload' => 'array',
    ];

    /**
     * Relationship: A payment belongs to an order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
