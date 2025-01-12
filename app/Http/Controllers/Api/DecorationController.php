<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store\Decoration;
use Illuminate\Http\Request;
use App\Traits\MobileTrait;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class DecorationController extends Controller
{
    use MobileTrait;

    /*
     * Display All decorations
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $decorations = Decoration::where('is_free', 0)->orderBy('id', 'DESC')->get();

        return $this->data($decorations);
    }



    /**
     * Get certain user's decorations(by type or all)
     */
    public function getUserDecorations(Request $request, $id) //ID of user
    {
        $user = User::findOrFail($id);

        $rules = [
            'type' => ['required', 'string', 'in:frame,entry,chat_bubble,room_background,room_frame'],
            // 'default_only' => ['in:1'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }


        $data['default_frame'] = Decoration::where('id', $user->default_frame_id)->first();

        $data['default_entry'] = Decoration::where('id', $user->default_entry_id)->first();

        $data['decorations'] = $user->decorations()->where('type', $request->type)->paginate(12);
        // $userDecorations = $userDecorations->toArray();
        // $data['decorations'] = array_values($userDecorations);

        return $this->data($data);
    }

    /**
     * Display Decoration by type
     * @accept [frame, entry, chat_bubble, room_background, room_frame]
     */
    public function getDecorationsByType(Request $request)
    {
        $rules = [
            'type' => ['required', 'string', 'in:frame,entry,chat_bubble,room_background,room_frame'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        $data = Decoration::where('type', $request->type)->where('is_purchased', 0)->get();

        return $this->data($data);
    }

    /**
     * purchase a new decoration
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function purchase(Request $request, $id) //ID of decoration
    {

        $decoration = Decoration::findOrFail($id);

        $user = auth()->user();

        /* Manual Validation */
        if($user->diamond_balance < $decoration->price){
            return $this->error('عفوا، رصيدك من الجواهر لا يكفي!', 403);
        }

        if($user->decorations->contains($decoration->id)){
            return $this->error('أنت تمتلك هذا التصميم بالفعل، جرب شراء تصميم أخر الأن.', 403);
        }

        try{
            //make decoration is purchased
            $decoration->is_purchased = 1;
            $decoration->save();

            $user->diamond_balance-= $decoration->price;

            $user->decorations()->attach($decoration->id);

            $user->save();

            return $this->successWithoutData('تم إضافة هذا العنصر إلى ممتلكاتك بنجاح، شكرا لإستخدامك fun chat.');
        }catch(QueryException $e){
            return $this->error('لقد حدث خطأ ما، برجاء المحاولة لاحقا', 500);
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $decoration = Decoration::findOrFail($id);

        return $this->data($decoration);
    }


    /**
     * Update the Default Decoration(frame or room background or entry)
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function setAsDefault($id) //ID of the frame
    {
        $decoration = Decoration::findOrFail($id);
        $user = auth()->user();

        /* Validate if user owns the decoration */
        if($user->decorations == null){
            return $this->error('يرجى شراء هذا العنصر أولا..', 403);
        }else{
            if(!$user->decorations->contains($decoration->id)){
                return $this->error('يرجى شراء هذا العنصر أولا..', 403);
            }
        }

        if($decoration->type == 'frame'){
            $user->default_frame_id = $decoration->id;
            $user->save();

            return $this->successWithoutData('رائع! تم تغيير الإطار الإفتراضي بنجاح');
        }elseif($decoration->type == 'entry'){
            $user->default_entry_id = $decoration->id;
            $user->save();

            return $this->successWithoutData('رائع! تم تغيير تأثير الدخول الإفتراضي بنجاح');
        }else{
            return $this->error('إختيار خاطئ!', 422,);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function unsetDefault($id) //ID or decoration
    {
        $decoration = Decoration::findOrFail($id);
        $user = auth()->user();

        /* Validate if user owns the decoration */
        if($user->decoration != null){
            if(!$user->decoration->contains($decoration->id)){
                return $this->error('يرجى شراء هذا العنصر أولا..', 403);
            }
        }

        if($decoration->type == 'frame'){
            $user->default_frame_id = null;
            $user->save();

            return $this->successWithoutData('تم إستعادة التأثير الإفتراضي بنجاح، إستخدم تأثير جديد للحصول على أفضل مظهر');
        }elseif($decoration->type == 'entry'){
            $user->default_entry_id = null;
            $user->save();

            return $this->successWithoutData('تم إستعادة التأثير الإفتراضي بنجاح، إستخدم تأثير جديد للحصول على أفضل مظهر');
        }else{
            return $this->error('إختيار خاطئ!', 422);
        }
    }

}
