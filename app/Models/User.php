<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class User extends Model
{
    use HasApiTokens;

    protected $fillable = ['id', 'name', 'email', 'password', 'role', 'status'];
    protected $hidden = ['password'];
    protected $casts = [
        'status' => 'boolean',
    ];

    // Ensure the ID is a string/UUID
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid(); // Generate UUID secara otomatis
            }
        });
    }
}