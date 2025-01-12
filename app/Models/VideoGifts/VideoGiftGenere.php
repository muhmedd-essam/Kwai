<?php

namespace App\Models\VideoGifts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\VideoGifts\VideoGift;

class VideoGiftGenere extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'precentage',
    ];

    public function gifts()
    {
        return $this->hasMany(VideoGift::class);
    }

}
