<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class News extends Model { protected $table='news'; protected $fillable=['title','slug','content','excerpt','image','category_id','author_id','author_name','is_anonymous','status']; protected $casts=['is_anonymous'=>'boolean','views'=>'integer']; public function author(){return $this->belongsTo(User::class,'author_id');} public function category(){return $this->belongsTo(Category::class);} }
