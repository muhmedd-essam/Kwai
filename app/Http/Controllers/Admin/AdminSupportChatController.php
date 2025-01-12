<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use App\Traits\WebTrait;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;
use App\Models\User;
use App\Models\Chat\Support\SupportConversation;
use App\Models\Chat\Support\SupportMessage;
use App\Models\Chat\Support\SupportAttachment;

class AdminSupportChatController extends Controller
{
    use WebTrait;

    public function sendMessage(Request $request, $id) //User ID
    {
        $rules = [
            'body' => ['required', 'max:2500'],
            'attachments' => ['array'],
            'attachments.*' => ['mimes:jpg,jpeg,png,webp,gif,svg,mp3,mp4,3gp', 'max:5122'], //5MB
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->validationError($code, $validator);
        }
        
        $user = User::findOrFail($id);

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
                return $this->error('E200', 'حدث خطأ ما برجاء التواصل مع المطور');
            }
        }

        //Store message to conversation
        try{
            $message = new SupportMessage;
            $message->support_conversation_id = $conversation->id;
            $message->is_sender = 0;
            $message->body = $request->body;
            $message->save();

            //Update Conversation to orderBy(updated_by)
            $conversation->updated_at = now();
            $conversation->save();
        }catch(QueryException $e){
            // return $e;
            return $this->error('E200', 'حدث خطأ ما برجاء التواصل مع المطور');
        }

        //Handle attachments//Store attachments if exists
        if(isset($request->attachments)){
            foreach($request->attachments as $requestAttachement){
            $path = Storage::disk('public')->putFile('support-chat-attach', new File($requestAttachement));
            $extension = $requestAttachement->extension();

            $attachmentData[] = ['support_message_id' => $message->id, 'path' => $path, 'extension' => $extension, 'created_at' => now(), 'updated_at' => now()];
        }
            try{
                SupportAttachment::insert($attachmentData);

                return $this->success('S100' ,'تم إرسال الرسالة بنجاح');
            }catch(QueryException $e){
                return $e;
                return $this->error('E200', 'حدث خطأ ما برجاء التواصل مع المطور');
            }
        }

        return $this->success('S100' ,'تم إرسال الرسالة بنجاح');
    }

    public function getAllConversations()
    {
        $conversations = SupportConversation::with('user', 'lastMessage')->orderBy('updated_at', 'DESC')->paginate(12);

        return $this->data($conversations);
    }

    public function getConversation($id) //User ID
    {
        $user = User::findOrFail($id);

        $conversation = SupportConversation::where('user_id', $user->id);
        if(!$conversation->exists()){
            return $this->error('E403', 'لا يوجد محادثة قائمة بين النظام وهذا المستخدم');
        }

        $conversation = $conversation->first();
        $messages = $conversation->messages()->with('attachments')->paginate(50);

        return $this->data(['conversation' => $conversation, 'messages' => $messages]);
    }

    public function deleteMessage($id) //Message ID
    {
        $message = SupportMessage::findOrFail($id);

        $message->delete();

        return $this->success('S103', 'تم حذف الرسالة بنجاح');
    }

    public function deleteConversation($id) //Conversation ID
    {
        $conversation = SupportConversation::findOrFail($id);

        $conversation->delete();

        return $this->success('S103', 'تم حذف المحادثة بنجاح');
    }

}
