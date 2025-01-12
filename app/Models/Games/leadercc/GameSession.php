<?php

namespace App\Models\Games\leadercc;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',     // الإشارة إلى اللاعب الذي بدأ الجلسة
        'room_id',     // رقم الغرفة
        'lang',        // اللغة المستخدمة
        'round_id',    // رقم الجولة
        'status',      // حالة اللعبة (مثال: active, completed)
        'started_at',  // وقت بدء الجلسة
        'ended_at',    // وقت انتهاء الجلسة
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    // تعريف العلاقة مع المستخدم
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
