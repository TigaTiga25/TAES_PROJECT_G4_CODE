<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'nickname', 
        'email',
        'password',
        'photo_avatar_filename',
        'coins_balance',
        'custom_avatar_seed',
        'unlocked_avatars', 
        'unlocked_decks',
        'current_deck',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'unlocked_avatars' => 'array', //Transforma o JSON da DB em Array automaticamente
            'unlocked_decks' => 'array',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relações (Relationships)
    |--------------------------------------------------------------------------
    */

    public function transactions()
    {
        return $this->hasMany(\App\Models\Transaction::class);
    }
}