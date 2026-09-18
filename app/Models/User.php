<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
class User extends Authenticatable { use Notifiable; protected $table='users'; protected $fillable=['username','email','password_hash','role','status','email_verified']; protected $hidden=['password_hash','remember_token']; public function getAuthPassword(){return $this->password_hash;} public function news(){return $this->hasMany(News::class,'author_id');} }
