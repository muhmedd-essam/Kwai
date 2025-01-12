<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Traits\MobileTrait;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use App\Models\Gift;
use App\Models\Gift\DailyGift;
use App\Models\Gift\GiftReceipt;
use App\Traits\WebTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;

class GiftController extends Controller
{
    use WebTrait;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    //  private function fileNameExists($fileName)
    //     {
    //         return Storage::disk('public')->exists($fileName);
    //     }

    public function index()
    {
        $gifts = Gift::orderBy('price', 'ASC')->get();

        return $this->data($gifts);
    }



    public function store(Request $request )
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'cover_image_url' => 'required|url',
            'svga' => ['required', 'max:2048'],
            'price' => ['required', 'numeric', 'min:0'],
            'type' => ['in:0,1,2'],
            'category_gift_id' => 'required|exists:category_gifts,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // Download and save the image temporarily
        $tempImagePath = tempnam(sys_get_temp_dir(), 'cover_image');
        $imageContent = Http::get($request->cover_image_url)->body();
        file_put_contents($tempImagePath, $imageContent);

        // Upload the image
        $cover = Storage::disk('public')->putFile('images/gifts', new File($tempImagePath));
        $request->merge(['cover' => $cover]);

        //Svga
        $uniqueName = $this->generateUniqueFileName();
        $fileName = $uniqueName . '.svga';
        $svga = $request->file('svga_image')->storeAs('/public/svgas/gifts', $fileName);
        $fullFileName = 'svgas/gifts/' . $fileName;
        $request->request->add(['svga' => $fullFileName]);

        try{

            $gift = Gift::create($request->all());
            return response()->json(['message'=>'تم تسجيل الهدية','gift'=>$gift]); //Success Insert
        }catch(QueryException $e){
            return $this->error('E200', ''); //DB err(General)
        }

    }

    public function getUserGifts($id)
    {
        $user = User::findOrFail($id);

        return $this->data($user->gifts);
    }

    protected function generateUniqueFileName()
    {
        $length = 20;
        $chars='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $totalChars = strlen($chars);
        $totalRepeat = ceil($length/$totalChars);
        $repeatString = str_repeat($chars, $totalRepeat);
        $shuffleString = str_shuffle($repeatString);
        $fileName = substr($shuffleString,1,$length);

        if($this->fileNameExists($fileName)){
            $this->generateUniqueFileName();
        }

        return $fileName;
    }


    public function dailyGift(Request $request)
{
    $userId = auth()->user()->id;
    $user = User::findOrFail($userId);
    $today = Carbon::today();

    // البحث عن آخر هدية استلمها المستخدم
    $lastReceipt = GiftReceipt::with('dailyGift')->where('user_id', $userId)->latest('date_received')->first();

    // حساب السلسلة اليومية المتتالية
    if ($lastReceipt && Carbon::parse($lastReceipt->date_received)->isYesterday()) {
        $currentStreak = $lastReceipt->current_streak + 1;
    } else {
        $currentStreak = 1;
    }

    // التحقق إذا كان المستخدم قد استلم هدية اليوم بالفعل
    if ($lastReceipt && Carbon::parse($lastReceipt->date_received)->isToday()) {
        return response()->json(['message' => 'You have already claimed today\'s gift'],200);
    }

    // إذا كانت الهدية التي استلمها المستخدم هي من نوع "normal" وقام المستخدم بترقية نوعه إلى "vip"
    if($lastReceipt != null){
        if ( $lastReceipt->dailyGift->user_type === 'normal' && $user->vip != null ) {
            // تغيير السلسلة لتبدأ من الهدية الأولى الخاصة بـ VIP
            $currentStreak = 8;
        }
    }


    if($currentStreak < 8){
        $gift = DailyGift::where('day_number', $currentStreak)
                        ->where('user_type', 'normal')
                        ->first();
    }else{
        $gift = DailyGift::where('day_number', $currentStreak)
                        ->where('user_type', 'vip')
                        ->first();
    }
    // جلب الهدية المناسبة لهذا اليوم بناءً على نوع المستخدم
    // $gift = DailyGift::where('day_number', $currentStreak)
    //                  ->where('user_type', $user->user_type)
    //                  ->first();

    // إذا لم توجد هدية
    if (!$gift) {
        return response()->json(['message' => 'No gift available for today'], 404);
    }

    // تسجيل استلام الهدية
    GiftReceipt::create([
        'user_id' => $user->id,
        'gift_id' => $gift->id,
        'date_received' => $today,
        'current_streak' => $currentStreak,
    ]);

    // التحقق من نوع الهدية
    if ($gift->gift_type === 'Gold') {
        $user->gold_balance += $gift->amount; // إضافة الكمية للرصيد
        $user->save();
    } elseif ($gift->gift_type === 'Diamond') {
        $user->diamond_balance += $gift->amount; // إضافة الكمية للرصيد
        $user->save();
    } elseif ($gift->gift_type === 'Entry' || $gift->gift_type === 'Frame') {
        $user->decorations()->attach($gift->decoration_id);
    }

    // عرض تفاصيل الهدية للمستخدم
    // return $this->data($gift);

    return response()->json([
        'status' => true,
        'errNum' => 'S000',
        'data' => $gift,
      ], 201);
}


    public function indexDailyGift()
    {
        try {
            $gifts = DailyGift::all()->map->data();
            return $this->data($gifts);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve gifts', 'message' => $e->getMessage()], 500);
        }
    }

    // Show a specific gift
    public function showDailyGift($id)
    {
        try {
            $gift = DailyGift::findOrFail($id);
            return $this->data($gift->data());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gift not found', 'message' => $e->getMessage()], 404);
        }
    }

    // Store a new gift
    public function storeDailyGift(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gift_num' => 'nullable|integer',
            'gift_name' => 'required|string|max:255',
            'gift_type' => 'required|string|max:255',
            'amount' => 'nullable|integer',
            'decoration_id' => 'nullable|integer',
            'day_number' => 'required|integer',
            'user_type' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed', 'details' => $validator->errors()], 422);
        }

        try {
            $gift = DailyGift::create($validator->validated());
            return $this->data($gift->data());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create gift', 'message' => $e->getMessage()], 500);
        }
    }

    // Update an existing gift
    public function updateDailyGift(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'gift_num' => 'nullable|integer',
            'gift_name' => 'required|string|max:255',
            'gift_type' => 'required|string|max:255',
            'amount' => 'nullable|integer',
            'decoration_id' => 'nullable|integer',
            'day_number' => 'required|integer',
            'user_type' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed', 'details' => $validator->errors()], 422);
        }

        try {
            $gift = DailyGift::findOrFail($id);
            $gift->update($validator->validated());
            return $this->data($gift->data());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update gift', 'message' => $e->getMessage()], 500);
        }
    }

    // Delete a gift
    public function destroyDailyGift($id)
    {
        try {
            $gift = DailyGift::findOrFail($id);
            $gift->delete();
            return $this->data('Deleted successfully');
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete gift', 'message' => $e->getMessage()], 500);
        }
    }

}
