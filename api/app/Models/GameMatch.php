<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameMatch extends Model
{
   protected $table = 'matches';
   public $timestamps = false;

    protected $fillable = [
        'type',
        'player1_user_id',
        'player2_user_id', //obrigatorio
        'status',
        'stake',
        'began_at',
        'ended_at',
        'total_time',
        'player1_marks',
        'player2_marks',
        'player1_points',
        'player2_points',
        'custom'
    ];

    protected $casts = [
        'custom' => 'array'
    ];

    public function games()
    {
        return $this->hasMany(Game::class);
    }

    public function player()
    {
        return $this->belongsTo(User::class, 'player1_user_id');
    }

    
}