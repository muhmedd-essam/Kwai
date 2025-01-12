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
use App\Models\Chat\Conversation;
use App\Models\Chat\Message;
use App\Models\Chat\Attachment;
use App\Models\Chat\ChatBlock;
use App\Models\Following;
use App\Models\Chat\Support\SupportConversation;
use Carbon\Carbon;
use App\Events\Chat\ConversationUpdate;
use App\Events\Chat\NewMessage;

class ChatController extends Controller
{
    use MobileTrait;

    public function sendMessage(Request $request, $id) //Reciever ID
    {
        $rules = [
            'body' => ['required_without:attachments', 'max:2500'],
            'attachments' => ['array'],
            'attachments.*' => ['mimes:jpg,jpeg,png,webp,gif,svg,mp3,mp4,3gp', 'max:5122'], //5MB
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        $sender = auth()->user();
        $reciever = User::findOrFail($id);

        if($sender->id == $reciever->id){
            return $this->error('لا يمكنك مراسلة نفسك', 403);
        }

        if(ChatBlock::where('blocker_id', $sender->id)->where('blocked_id', $reciever->id)->exists() || ChatBlock::where('blocker_id', $reciever->id)->where('blocked_id', $sender->id)->exists()){
            return $this->error('لا يمكنك مراسلة هذا الشخص', 403);
        }

        // if(!Following::where('following_id', $sender->id)->where('follower_id', $reciever->id)->exists() || !Following::where('following_id', $reciever->id)->where('follower_id', $sender->id)->exists()){
        //     return $this->error('يجب أن تتابعوا بعضكم أولا', 403);
        // }

        $body = $request->body;

        //Check if this is the first message => initialize conversation, otherwise just store the message to the conversation
        $intiConvo = Conversation::where('initializer_id', $sender->id)->where('dependent_id', $reciever->id);
        $depeConvo = Conversation::where('initializer_id', $reciever->id)->where('dependent_id', $sender->id);

        if($intiConvo->exists()){
            $conversation = $intiConvo->first();
        }elseif($depeConvo->exists()){
            $conversation = $depeConvo->first();
        }else{
            try{
                $conversation = new Conversation;
                $conversation->initializer_id = $sender->id;
                $conversation->dependent_id = $reciever->id;
                $conversation->save();
            }catch(QueryException $e){
                return $this->error('لقد حدث خطأ ما، برجاء المحاولة لاحقا', 500);
            }
        }

        //Store message to conversation
        try{
            $message = new Message;
            $message->conversation_id = $conversation->id;
            $message->sender_id = $sender->id;
            $message->reciever_id = $reciever->id;
            $message->body = $request->body;
            $message->save();
            
            //Update Conversation to orderBy(updated_by)
            $conversation->updated_at = now();
            $conversation->save();
        }catch(QueryException $e){
            return $this->error('لقد حدث خطأ ما، برجاء المحاولة لاحقا', 500);
        }
        
        //Store attachments if exists
        if(isset($request->attachments)){
            foreach($request->attachments as $requestAttachement){
            $path = Storage::disk('public')->putFile('chat-attach', new File($requestAttachement));
            $extension = $requestAttachement->extension();

            $attachmentData[] = ['message_id' => $message->id, 'path' => $path, 'extension' => $extension, 'created_at' => now(), 'updated_at' => now()];
        }
            try{
                Attachment::insert($attachmentData);

                //Handle Events
                $messageWithAttach = Message::with('attachments')->findOrFail($message->id);
                NewMessage::dispatch($conversation, $messageWithAttach);
                ConversationUpdate::dispatch($conversation, $messageWithAttach);

                return $this->successWithoutData('تم إرسال الرسالة بنجاح');
            }catch(QueryException $e){
                return $e;
                return $this->error('لقد حدث خطأ ما، برجاء المحاولة لاحقا', 500);
            }
        }

        //Handle Events
        $messageWithAttach = Message::with('attachments')->findOrFail($message->id);
        NewMessage::dispatch($conversation, $messageWithAttach);
        ConversationUpdate::dispatch($conversation, $message);

        return $this->successWithoutData('تم إرسال الرسالة بنجاح');
    }

    public function removeForMe($id) //Message ID
    {
        $requestUser = auth()->user();
        $message = Message::findOrFail($id);
        
        if($message->conversation->dependent_id != $requestUser->id && $message->conversation->initializer_id != $requestUser->id){
            return $this->error('أنت لست طرف في هذه المحادثة', 403);
        }

        try{
            if($message->sender_id == $requestUser->id){
                $message->is_deleted_for_sender = 1;
            }else{
                $message->is_deleted_for_reciever = 1;
            }
            $message->save();

            return $this->successWithoutData('تم الحذف بنجاح');
        }catch(QueryException $e){
            return $this->error('لقد حدث خطأ ما، برجاء المحاولة لاحقا', 500);
        }
    }

    public function removeForAll($id) //Message ID
    {
        $requestUser = auth()->user();
        $message = Message::findOrFail($id);
        
        if($message->conversation->dependent_id != $requestUser->id && $message->conversation->initializer_id != $requestUser->id){
            return $this->error('أنت لست طرف في هذه المحادثة', 403);
        }

        //Validate that no more than 1h have passed since the message was sent
        $carbonFrom = new Carbon($message->created_at);
        $carbonTo = Carbon::now();
        $diffOfPeriod = $carbonFrom->diffInHours($carbonTo);
        if($diffOfPeriod >= 1){
            return $this->error('عفوا، لا يمكن حذف المحادثات التي مر عليها أكثر من ساعة واحدة', 403);
        }

        try{
            $message->attachments()->delete();

            $message->is_deleted_for_all = 1;
            $message->save();

            return $this->successWithoutData('تم الحذف بنجاح');
        }catch(QueryException $e){
            return $this->error('لقد حدث خطأ ما، برجاء المحاولة لاحقا', 500);
        }

    }

    public function getAllConversations()
    {
        $requestUser = auth()->user();
        
        $conversations = Conversation::with('lastMessage', 'initializer', 'dependent')->where('initializer_id', $requestUser->id)->where('is_deleted_for_initializer', 0)->orWhere('dependent_id', $requestUser->id)->where('is_deleted_for_dependent', 0)->orderBy('updated_at', 'DESC')->paginate(12);

        return $this->dataPaginated($conversations);
    }

    public function getSupportConversations()
    {
        $requestUser = auth()->user();
        
        $supportConversation = SupportConversation::where('user_id', $requestUser->id)->with('lastMessage')->first();

        return $this->data($supportConversation);
    }
    public function getConversation($id) // User ID
    {
        $requestUser = auth()->user();
        $user = User::findOrFail($id);

        $conversation = Conversation::with('initializer', 'dependent')->where('initializer_id', $user->id)->where('dependent_id', $requestUser->id)->orWhere('initializer_id', $requestUser->id)->where('dependent_id', $user->id)->first();

        $messages = null;
        
        // if($requestUser->id != $conversation->dependent_id && $requestUser->id != $conversation->initializer_id){
        //         return $this->error('أنت لست طرف في هذه المحادثة', 403);
        // }
            
        if($conversation)
        {
            if($conversation->is_deleted_for_initializer == 1 && $conversation->initializer_id == $requestUser->id){
                return $this->error('محادثة محذوفة', 403);
            }
            
            if($conversation->is_deleted_for_dependent == 1 && $conversation->dependent_id == $requestUser->id){
                return $this->error('محادثة محذوفة', 403);
            }

            $messages = $conversation->messages()->with('attachments')->paginate(30);
        }

        return $this->data(['conversation' => $conversation, 'messages' => $messages]);
    }

    public function deleteConversation($id)
    {
        $requestUser = auth()->user();

        $conversation = Conversation::findOrFail($id);

        if($requestUser->id != $conversation->dependent_id && $requestUser->id != $conversation->initializer_id){
            return $this->error('أنت لست طرف في هذه المحادثة', 403);
        }

        try{
            if($requestUser->id == $conversation->dependent_id){
                $conversation->is_deleted_for_dependent = 1;
            }else{
                $conversation->is_deleted_for_initializer = 1;
            }

            $conversation->save();

            return $this->successWithoutData('تم الحذف بنجاح');
        }catch(QueryException $e){
            return $this->error('لقد حدث خطأ ما، برجاء المحاولة لاحقا', 500);
        }
    }

    public function blockUserMessages($id) //user to be blocked id
    {
        $blocker = auth()->user();
        $blocked = User::findOrFail($id);

        try{
            $block = new ChatBlock;
            $block->blocker_id = $blocker->id;
            $block->blocked_id = $blocked->id;
            $block->save();

            return $this->successWithoutData('تم حظر مراسلة هذا الشخص');
        }catch(QueryException $e){
            return $e;
            return $this->error('لقد حدث خطأ ما، برجاء المحاولة لاحقا', 500);
        }
    }

}
