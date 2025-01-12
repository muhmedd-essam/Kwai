<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $table = 'country';
    
    public function getFlagAttribute($value)
    {
        return asset('/storage/images/flags/' . $this->code . '.png');
    }
    
}
