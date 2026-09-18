<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginRateLimit extends Model
{
    use HasFactory;

    protected $table = 'login_rate_limits';
    public $timestamps = false;
    protected $primaryKey = 'rate_key';
    public $incrementing = false;
    protected $fillable = ['rate_key', 'failed_attempts', 'blocked_until', 'last_failed_at'];
}
