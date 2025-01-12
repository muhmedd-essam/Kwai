<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Gift extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'cover',
        'svga',
        'price',
        'type',
        'category_gift_id',
    ];

    protected $casts = [
        'price' => 'double',
    ];

    public function getCoverAttribute($value){
        return asset('/storage/' . $value);
    }

    public function getSvgaAttribute($value){
        return asset('/storage/' . $value);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }


    public function CategoryGift(){
        return $this->belongsTo(CategoryGift::class, 'category_gift_id', 'id');
    }
}
