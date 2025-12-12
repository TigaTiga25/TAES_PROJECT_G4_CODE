<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CoinTransactionType extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = ['name', 'type'];
}
