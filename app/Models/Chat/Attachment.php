<?php

namespace App\Models\Chat;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Chat\Message;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'path',
        'extension',
    ];
    
    public function getPathAttribute($value)
    {
        return asset('/storage/' . $value);
    }
    
    public function message()
    {
        return $this->belongsTo(Message::class);
    }

}
