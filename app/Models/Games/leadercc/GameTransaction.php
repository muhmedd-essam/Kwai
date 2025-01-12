<?php

namespace App\Models\Games\leadercc;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameTransaction extends Model
{
    use HasFactory;


    // تحديد اسم الجدول في قاعدة البيانات
    protected $table = 'game_transactions';

    // تحديد الحقول القابلة للتعديل (Mass Assignment)
    protected $fillable = [
        'user_id',
        'transaction_type',
        'amount'
    ];

    // تحديد العلاقة مع المستخدم (User)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // إذا أردت إضافة تواريخ مخصصة، يمكنك إضافة:
    // protected $dates = ['created_at', 'updated_at'];

    // الحصول على نوع المعاملة (فوز أو خسارة)
    public function getTransactionTypeAttribute($value)
    {
        return ucfirst($value); // على سبيل المثال: "win" يتحول إلى "Win"
    }
}
