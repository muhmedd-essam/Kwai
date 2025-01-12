<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;

    protected $fillable = [
        'cover',
        'related_to_id',
        'related_to_type',
        'valid_to',
    ];

    public function getCoverAttribute($value){
        return asset('/storage/' . $value);
    }
    
    public function relatedTo()
    {
        return $this->morphTo();
    }
}
