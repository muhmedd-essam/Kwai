<?php

namespace App\Models\Chat;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Chat\Conversation;
use App\Models\User;
use App\Models\Chat\Attachment;

class Message extends Model
{
    use HasFactory;

    public function getBodyAttribute($value)
    {
        if($this->is_deleted_for_all == 1){
            $value = 'تم حذف هذه الرسالة';
        }

        return $value;
    }
    
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function reciever()
    {
        return $this->belongsTo(User::class, 'reciever_id');
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

}
