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
        'player2_user_id',
        'is_draw',
        'winner_user_id',
        'loser_user_id',
        'match_id',
        'status',
        'began_at',
        'ended_at',
        'total_time',
        'player1_points',
        'player2_points',
        'custom'
    ];

    protected $casts = [
        'custom' => 'array',
        'began_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function match()
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }

    public function player1()
    {
        return $this->belongsTo(User::class, 'player1_user_id');
    }

    public function player2()
    {
        return $this->belongsTo(User::class, 'player2_user_id');
    }

    public function winner()
    {
        return $this->belongsTo(User::class, 'winner_user_id');
    }
}
