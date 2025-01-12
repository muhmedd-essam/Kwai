<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiamondPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'quantity',
        'price',
        'cover',
    ];

    protected $casts = [
        'price' => 'double',
    ];

    public function getCoverAttribute($value){
        return asset('/storage/' . $value);
    }
    
}
