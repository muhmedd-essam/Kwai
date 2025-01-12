<?php

namespace App\Models\Store;

use App\Models\Gift\DailyGift;
use App\Models\User;
use App\Models\VipUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Decoration extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'cover',
        'svga',
        'is_free',
        'price',
        'currency_type',
        'valid_days',
    ];


    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function vipUsers()
    {
        return $this->hasMany(VipUser::class, 'default_frame_id');
    }

    public function vipEntries()
    {
        return $this->hasMany(VipUser::class, 'default_entry_id');
    }

    public function dailyGift()
    {
        return $this->hasMany(DailyGift::class);
    }

    public function getCoverAttribute($value)
    {
        return asset('/storage/' . $value);
    }

    public function getSvgaAttribute($value)
    {
        if($value != null){
            return asset('/storage/' . $value);
        }

        return null;
    }

}
