<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $table = 'categories';
    public $timestamps = false;
    protected $fillable = ['name', 'slug', 'description', 'status'];

    public function news()
    {
        return $this->hasMany(News::class);
    }
}
