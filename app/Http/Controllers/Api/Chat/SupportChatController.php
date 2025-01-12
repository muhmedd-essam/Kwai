<?php

namespace App\Http\Controllers\Api\Chat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\MobileTrait;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;
use App\Models\User;
use App\Models\Chat\Support\SupportConversation;
use App\Models\Chat\Support\SupportMessage;
use App\Models\Chat\Support\SupportAttachment;

class SupportChatController extends Controller
{
    use MobileTrait;

    public function sendMessage(Request $request)
    {
        $rules = [
            'body' => ['required', 'max:2500'],
            'attachments' => ['array'],
            'attachments.*' => ['mimes:jpg,jpeg,png,webp,gif,svg,mp3,mp4,3gp', 'max:5122'], //5MB
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        $user = auth()->user();

        //Handle Conversation
        $currentConvo = SupportConversation::where('user_id', $user->id);
        if($currentConvo->exists()){
            $conversation = $currentConvo->first();
        }else{ //Initialize new conversations
            try{
                $conversation = new SupportConversation;
                $conversation->user_id = $user->id;
                $conversation->save();
            }catch(QueryException $e){
                return $this->error('لقد حدث خطأ ما، برجاء المحاولة لاحقا', 500);
            }
        }

        //Store message to conversation
        try{
            $message = new SupportMessage;
            $message->support_conversation_id = $conversation->id;
            $message->is_sender = 1;
            $message->body = $request->body;
            $message->save();
            
            //Update Conversation to orderBy(updated_by)
            $conversation->updated_at = now();
            $conversation->save();
        }catch(QueryException $e){
            return $e;
            return $this->error('لقد حدث خطأ ما، برجاء المحاولة لاحقا', 500);
        }

        //Store attachments if exists
        if(isset($request->attachments)){
            foreach($request->attachments as $requestAttachement){
            $path = Storage::disk('public')->putFile('chat-attach', new File($requestAttachement));
            $extension = $requestAttachement->extension();

            $attachmentData[] = ['support_message_id' => $message->id, 'path' => $path, 'extension' => $extension, 'created_at' => now(), 'updated_at' => now()];
            }
            
            try{
                SupportAttachment::insert($attachmentData);

                return $this->successWithoutData('تم إرسال الرسالة بنجاح');
            }catch(QueryException $e){
                return $e;
                return $this->error('لقد حدث خطأ ما، برجاء المحاولة لاحقا', 500);
            }
        }

        return $this->successWithoutData('تم إرسال الرسالة بنجاح');
    }

    public function getConversation()
    {
        $conversation = SupportConversation::where('user_id', auth()->id());
        if(!$conversation->exists()){
            return $this->error('لا يوجد محادثة قائمة بين النظام وهذا المستخدم', 404);
        }

        $conversation = $conversation->first();
        $messages = $conversation->messages()->with('attachments')->paginate(30);

        return $this->data(['conversation' => $conversation, 'messages' => $messages]);
    }

}
