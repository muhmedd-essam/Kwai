<?php

namespace App\Models\Store;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpecialUID extends Model
{
    use HasFactory;

    protected $fillable = [
        'body',
        'price',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
}
