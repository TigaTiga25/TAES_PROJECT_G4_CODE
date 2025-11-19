<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $table = 'games';
    public $timestamps = false;

    protected $fillable = [
        'type',
        'player1_user_id',
        'player2_user_id', //obrigatorio
        'match_id',
        'status',
        'began_at',
        'ended_at',
        'total_time',
        'player1_points',
        'player2_points', //pontos do bot
        'custom'
    ];

    protected $casts = [
        'custom' => 'array'
    ];

    public function match()
    {
        return $this->belongsTo(GameMatch::class);
    }

    public function player()
    {
        return $this->belongsTo(User::class, 'player1_user_id');
    }
}
