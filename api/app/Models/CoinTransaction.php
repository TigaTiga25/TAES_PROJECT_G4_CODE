<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoinTransaction extends Model
{
     public $timestamps = false;

    protected $fillable = [
        'transaction_datetime',
        'user_id',
        'match_id',
        'coin_transaction_type_id',
        'coins'
    ];

    public function type()
    {
        return $this->belongsTo(CoinTransactionType::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function match()
    {
        return $this->belongsTo(GameMatch::class);
    }
}
