<?php

namespace App\Models\Gift;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GiftReceipt extends Model
{
    use HasFactory;

     // الحقول القابلة للتعبئة
     protected $fillable = [
        'user_id',
        'gift_id',
        'date_received',
        'current_streak',
    ];

    /**
     * العلاقة مع جدول DailyGift
     */
    public function dailyGift()
    {
        return $this->belongsTo(DailyGift::class, 'id');
    }

    /**
     * العلاقة مع جدول User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
