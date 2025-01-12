<?php

namespace App\Models\Chat\Support;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Chat\Support\SupportMessage;

class SupportAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'support_message_id',
        'path',
        'extension',
    ];

    public function getPathAttribute($value)
    {
        return asset('/storage/' . $value);
    }

    public function message()
    {
        return $this->belongsTo(SupportMessage::class);
    }
    
}
