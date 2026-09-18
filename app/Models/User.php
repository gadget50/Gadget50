<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $fillable = [
        'username', 'email', 'password_hash', 'role', 'status',
        'email_verified', 'email_verification_token', 'email_verification_expires_at',
        'password_reset_token', 'password_reset_expires_at', 'two_factor_enabled',
        'two_factor_secret', 'is_anonymous_allowed',
    ];
    protected $hidden = ['password_hash', 'remember_token', 'two_factor_secret'];
    protected $casts = [
        'email_verified' => 'boolean',
        'two_factor_enabled' => 'boolean',
        'is_anonymous_allowed' => 'boolean',
        'email_verification_expires_at' => 'datetime',
        'password_reset_expires_at' => 'datetime',
    ];

    public function getAuthPassword(): string
    {
        return (string) $this->password_hash;
    }

    public function getRememberTokenName(): string
    {
        return '';
    }

    public function news()
    {
        return $this->hasMany(News::class, 'author_id');
    }
}
