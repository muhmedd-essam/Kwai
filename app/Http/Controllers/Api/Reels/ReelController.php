<?php

namespace App\Http\Controllers\Api\Reels;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\MobileTrait;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;
use App\Models\Reels\Reel;
use App\Models\Reels\ReelLike;
use App\Models\Reels\ReelComment;
use App\Models\User;

class ReelController extends Controller
{
    use MobileTrait;

    public function store(Request $request)
    {
        $rules = [
            'video' => ['required', 'mimes:mp4', 'max:30720'], //15mb
            'description' => ['required', 'string', 'min:3', 'max:200'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        $user = auth()->user();

        $videoPath = Storage::disk('public')->putFile('reels/videos', new File($request->video));

        $request->request->add(['user_id' => $user->id, 'path' => $videoPath]);

        try{
            $reel = Reel::create($request->all());

            return $this->success(['reel' => $reel], 'تم إضافة الفيديو بنجاح');
        }catch(QueryException $e){
            return $this->error500();
        }
    }

    public function update(Request $request, $id)
    {
        $reel = Reel::findOrFail($id);
        $user = auth()->user();

        $rules = [
            'description' => ['string', 'min:3', 'max:200'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        if($reel->user_id != $user->id){
            return $this->error('هذا الفيديو غير خاص بك', 403);
        }

        try{
            $reel->update($request->only('description'));

            return $this->successWithoutData('تم تحديث الفيديو بنجاح');
        }catch(QueryException $e){
            return $this->error500();
        }
    }

    public function destroy($id)
    {
        $reel = Reel::findOrFail($id);
        $user = auth()->user();

        if($reel->user_id != $user->id){
            return $this->error('هذا الفيديو غير خاص بك', 403);
        }

        Storage::disk('public')->delete($reel->path);

        $reel->delete();

        return $this->successWithoutData('تم حذف الفيديو بنجاح');
    }

    public function like($id)
    {
        $reel = Reel::findOrFail($id);
        $user = auth()->user();

        $prevLike = ReelLike::where('reel_id', $reel->id)->where('user_id', $user->id);

        try
        {
        if(!$prevLike->exists()){
                ReelLike::create(['reel_id' => $reel->id, 'user_id' => $user->id]);

                return $this->success(1, 'like placed successfully!');
            }else{
                $prevLike->delete();
                return $this->success(0 ,'unliked successfully!');
            }
        }catch(QueryException $e){
            return $this->error500();
        }
    }

    public function comment(Request $request, $id)
    {
        $reel = Reel::findOrFail($id);
        $user = auth()->user();

        $rules = [
            'body' => ['required', 'string', 'min:1', 'max:500'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        try{
            $comment = ReelComment::create(['reel_id' => $reel->id, 'user_id' => $user->id, 'body' => $request->body]);

            return $this->success($comment->with('user')->first(), 'تم إضافة التعليق بنجاح');
        }catch(QueryException $e)
        {
            return $this->error500();
        }
    }

    public function destroyComment($id)
    {
        $comment = ReelComment::findOrFail($id);
        $user = auth()->user();

        if($comment->user_id != $user->id && $comment->reel->user_id != $user->id){
            return $this->error('هذا التعليق غير خاص بك', 403);
        }

        $comment->delete();

        return $this->successWithoutData('تم حذف التعليق بنجاح');
    }

    public function index()
    {
        $reels = Reel::with('user')->withCount('likes', 'comments')->orderBy('id', 'DESC')->paginate(10);

        return $this->dataPaginated($reels);
    }

    public function getUserReels($id)
    {
        $user = User::findOrFail($id);

        $reels = $user->reels()->paginate(12);

        return $this->dataPaginated($reels);
    }

    public function likes($id)
    {
        $reel = Reel::findOrFail($id);

        $likes = $reel->likes()->with('user')->paginate(20);

        return $this->dataPaginated($likes);
    }

    public function comments($id)
    {
        $reel = Reel::findOrFail($id);

        $comments = $reel->comments()->with('user')->paginate(20);

        return $this->dataPaginated($comments);
    }


}
