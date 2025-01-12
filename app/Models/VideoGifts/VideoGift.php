<?php

namespace App\Models\VideoGifts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\VideoGifts\VideoGiftGenere;
use App\Models\User;

class VideoGift extends Model
{
    use HasFactory;    

    protected $fillable = [
        'name',
        'price',
        'cover',
        'svga',
        'video_gift_genere_id',
        'type',
        'related_gift_ids',
        'sending_counter',
        'required_sending_counter',
        'surprise_gift_id',
    ];

    protected $casts = [
        'related_gift_ids' => 'array',
    ];

    public function genere()
    {
        return $this->belongsTo(VideoGiftGenere::class, 'video_gift_genere_id');
    }

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

}
