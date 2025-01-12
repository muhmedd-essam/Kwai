<?php

namespace App\Http\Controllers\Api\Groups;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\MobileTrait;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;

class GroupController extends Controller
{
    use MobileTrait;

    public function store(Request $request)
    {
        $user = auth()->user();

        $rules = [
            'name' => ['required', 'unique:groups,name', 'string', 'min:3', 'max:50'],
            'cover_image' => ['required', 'mimes:jpg,png,jpeg,webp', 'max:2048'],
            'description' => ['required', 'string', 'min:15', 'max:400'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        if($user->diamond_balance < 50000){
            return $this->error('عفوا، رصيدك من الجواهر لا يكفي، يجب أن تمتلك على الأقل 50000 جوهرة', 403);
        }

        if($user->groupMember){
            $user->groupMember()->delete();
        }

        if(Group::where('owner_id', $user->id)->exists()){
            return $this->error('أنت بالفعل صاحب عائلة، جرب تطوير عائلتك بدلا من محاولة إنشاء عائلة جديدة', 403);
        }

        $request->request->add(['owner_id' => $user->id]);

        //Upload
        $cover = Storage::disk('public')->putFile('images/groups/covers', new File($request->cover_image));
        $request->request->add(['cover' => $cover]);

        try{
            $group = Group::create($request->all());

            $groupMember = GroupMember::insert(['user_id' => $user->id, 'group_id' => $group->id, 'membership_status' => 2, 'created_at' => now(), 'updated_at' => now()]);

            return $this->successWithoutData('تم إنشاء العائلة بنجاح');
        }catch(QueryException $e){
            // return $e;
            return $this->error('لقد حدث خطأ ما، برجاء المحاولة لاحقا', 500);
        }
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $group = Group::findOrFail($id);

        $rules = [
            'name' => ['unique:groups,name,'.$group->id, 'string', 'min:3', 'max:50'],
            'cover_image' => ['mimes:jpg,png,jpeg,webp', 'max:2048'],
            'description' => ['string', 'min:15', 'max:400'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        if($group->owner_id != $user->id){
            return $this->error('أنت لست صاحب هذه العائلة أو حتى أحد المشرفيين', 403);
        }

        try{
            $group->update($request->all());

            return $this->successWithoutData('تم تحديث بيانات العائلة بنجاح');
        }catch(QueryException $e){
            return $this->error('لقد حدث خطأ ما، برجاء المحاولة لاحقا', 500);
        }
    }

    public function destroy($id)
    {
        $group = Group::findOrFail($id);

        if($group->owner_id != auth()->id()){
            return $this->error('أنت لست صاحب هذه العائلة أو حتى أحد المشرفيين', 403);
        }

        $group->delete();

        return $this->successWithoutData('تم حذف العائلة بنجاح، يحزننا الفراق');
    }

    public function index()
    {
        $groups = Group::with('owner')->withCount('members')->paginate(12);

        $myGroup = Group::where('owner_id', auth()->id())->first();

        return $this->dataPaginated(['my_group' => $myGroup, 'groups' => $groups]);
    }

    public function show($id)
    {
        $group = Group::with('owner')->withCount('members')->findOrFail($id);

        return $this->data($group);
    }

    public function getGroupMembers($id)
    {
        $group = Group::findOrFail($id);

        return $this->dataPaginated($group->members()->with('user.RoomMember.room')->paginate(30));
    }

    public function search(Request $request)
    {
        $rules = [
            'name' => ['required', 'string'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        $groups = Group::with('owner')->withCount('members')->where('name', 'LIKE', '%'.$request->name.'%')->get();

        return $this->data($groups);
    }

    public function kick(Request $request, $id)
    {
        $requestUser = auth()->user();
        $group = Group::findOrFail($id);

        $rules = [
            'user_id' => ['required', 'numeric'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        $user = User::findOrFail($request->user_id);

        if($group->owner_id != $requestUser->id){
            return $this->error('أنت لست صاحب هذه العائلة أو حتى أحد المشرفيين', 403);
        }

        if(!$user->groupMember || $user->groupMember->group->id != $group->id){
            return $this->error('المستخدم غير منضم أصلا لهذه العائلة', 403);
        }

        if($user->id == $requestUser->id || $user->id == $group->owner_id){
            return $this->error('لا يمكنك طرد نفسك أو طرد صاحب العائلة، لماذا؟', 403);
        }

        try{
            $user->groupMember->delete();

            return $this->successWithoutData('تم طرد المستخدم من العائلة، يحزننا الفراق');
        }catch(QueryException $e){
            return $this->error('لقد حدث خطأ ما، برجاء المحاولة لاحقا', 500);
        }

    }

    public function join($id)
    {
        $user = auth()->user();
        $group = Group::findOrFail($id);

        if($user->groupMember){
            return $this->error('أنت بالفعل منضم لعائلة اخرى، ودع عائلتك أولا وغادرها ثم حاول الإنضمام لهذه العائلة', 403);
        }

        try{
            $groupMember = GroupMember::insert(['user_id' => $user->id, 'group_id' => $group->id, 'membership_status' => 0, 'created_at' => now(), 'updated_at' => now()]);

            return $this->successWithoutData('مرحبا بك في عائلتك الجديدة');
        }catch(QueryException $e){
            return $this->error('لقد حدث خطأ ما، برجاء المحاولة لاحقا', 500);
        }
    }

    public function leave()
    {
        $user = auth()->user();

        if(!$user->groupMember){
            return $this->error('انت لست فرد من أي عائلة حتى يبن عمي', 403);
        }

        if($user->groupMember->group->owner_id == $user->id){
            return $this->error('لا يمكنك مغادرة عائلتك، إذا كنت مصمم على الفراق؛ قم بتفكيك العائلة أولا.', 403);
        }

        $user->groupMember->delete();

        return $this->successWithoutData('تم مغادرة العائلة.. يحزننا الفراق');
    }

}
