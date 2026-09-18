<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $table = 'settings';
    public $timestamps = false;
    protected $fillable = ['setting_key', 'setting_value'];
    protected $casts = ['setting_value' => 'string'];
}
