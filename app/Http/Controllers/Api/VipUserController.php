<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SuperAdmin;
use App\Models\User;
use App\Models\VipUser;
use App\Models\Store\Decoration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VipUserController extends Controller
{
    public function store(Request $request){
        // $admin = SuperAdmin::find(auth()->user()->id);
        $vip = $request->input('vip');
        
        $vipStatus = VipUser::findOrFail($vip);
        
        // dd($vipStatus);
        $user= auth()->user();
        if($vipStatus->amount > $user->diamond_balance){
            return response()->json(['message' => 'فلوسك مش كفاية يا بيه'], 404);
        }
        
        if($vipStatus->id === $user->vip){
return response()->json(['message' => 'هتشتري حاجة معاك ؟ اتكل على الله'], 200);        }
        
        
        // if(!$admin){
        //     // if ($userId == auth()->user()->id){
        //     //     if ($vipStatus !== null) {
        //     //         $user->vip = $vipStatus;
        //     //         $user->save();
        //     //     }
        //     //     return response()->json(['message' => 'تم الشراء بنجاح'], 200);
        //     // }
        //     return response()->json(['message' => 'غير مصرح لك'], 404);
        // }
        

        if ($vipStatus !== null) {

            $vipStatus->amount -=  $user->diamond_balance;
                        // dd($vipStatus);
            $user->vip = $vipStatus->id;
            $user->save();
            $decorationFrame = Decoration::where('id',$vipStatus->default_frame_id)->first();
        $decorationEntry = Decoration::where('id',$vipStatus->default_entry_id)->first();
        $user->decorations()->attach($decorationFrame->id);
        $user->decorations()->attach($decorationEntry->id);
            
            
        }
        return response()->json(['message' => 'تم الشراء بنجاح']);

    }

}
