<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryGift extends Model
{
    use HasFactory;
    protected $table = 'category_gifts';

    protected $fillable = [
        'name',
        'type',
    ];

    protected $casts = [
        'price' => 'double',
    ];
    
    public function gift()
    {
        return $this->hasMany(Gift::class, 'category_gift_id', 'id');
    }
}
