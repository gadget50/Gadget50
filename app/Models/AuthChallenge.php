<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuthChallenge extends Model
{
    use HasFactory;

    protected $table = 'auth_challenges';
    public $timestamps = false;
    protected $fillable = ['user_id', 'purpose', 'code_hash', 'expires_at', 'attempts', 'used_at'];
}
