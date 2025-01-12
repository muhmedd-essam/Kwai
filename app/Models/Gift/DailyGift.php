<?php

namespace App\Models\Gift;

use App\Models\Store\Decoration;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyGift extends Model
{
    use HasFactory;

    // الحقول القابلة للتعبئة
    protected $fillable = [
        'gift_name',
        'gift_type',
        'amount',
        'gift_num',
        'day_number',
        'user_type',
        'decoration_id'
    ];

    /**
     * العلاقة مع جدول GiftReceipt
     */
    public function giftReceipts()
    {
        return $this->hasMany(GiftReceipt::class);
    }

    public function decoration()
    {
        return $this->belongsTo(Decoration::class);
    }
}
