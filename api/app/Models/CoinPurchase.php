<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoinPurchase extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'purchase_datetime',
        'user_id',
        'coin_transaction_id',
        'euros',
        'payment_type',        // ex: 'MBWAY', 'PAYPAL', 'VISA'
        'payment_reference',
        'custom'
    ];

    protected $casts = [
        'purchase_datetime' => 'datetime',
        'custom' => 'array',
        'euros' => 'decimal:2'
    ];

    public function transaction()
    {
        return $this->belongsTo(CoinTransaction::class, 'coin_transaction_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
