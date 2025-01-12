<?php

namespace App\Models;

use App\Models\Store\Decoration;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VipUser extends Model
{
    use HasFactory;

    protected $table = 'vip_user';

    protected $fillable = [

        'default_frame_id',

    ];
    protected $casts = [

        'default_frame_id' => 'integer',
    ];



    public function defaultFrame()
    {
        return $this->belongsTo(Decoration::class, 'default_frame_id');
    }

    public function defaultEntry()
    {
        return $this->belongsTo(Decoration::class, 'default_entry_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'vip', 'id');
    }
    
    // public function toArray()
    //     {
    //         $array = parent::toArray();
        
    //         // تحويل خصائص null إلى 0
    //         return array_map(function ($value) {
    //             return is_null($value) ? [] : $value;
    //         }, $array);
    //     }
}
