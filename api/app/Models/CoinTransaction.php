<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoinTransaction extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'transaction_datetime',
        'user_id',
        'match_id',
        'game_id',
        'coin_transaction_type_id',
        'coins',
        'custom'
    ];

    protected $casts = [
        'custom' => 'array',
        'transaction_datetime' => 'datetime',
    ];

    public function type()
    {
        return $this->belongsTo(CoinTransactionType::class, 'coin_transaction_type_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function match()
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }

    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    public function purchase()
    {
        return $this->hasOne(CoinPurchase::class);
    }
}
