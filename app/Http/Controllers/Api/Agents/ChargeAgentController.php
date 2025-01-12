<?php

namespace App\Http\Controllers\Api\Agents;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\MobileTrait;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Agents\ChargeAgentsHistory;
use App\Models\Agents\ChargeAgent;

class ChargeAgentController extends Controller
{
    use MobileTrait;
    
    public function index()
{
    $agents = ChargeAgent::with('owner')->orderBy('id', 'DESC')->paginate(12);

    $pagination = [
        'current_page' => $agents->currentPage(),
        'total_pages' => $agents->lastPage(),
        'data' => $agents->items()
    ];

    // إضافة "next_page" إذا كانت هناك صفحة تالية
    if ($agents->hasMorePages()) {
        $pagination['next_page'] = $agents->currentPage() + 1;
    }

    // إضافة "previous_page" إذا كانت هناك صفحة سابقة
    if ($agents->currentPage() > 1) {
        $pagination['previous_page'] = $agents->currentPage() - 1;
    }

    return $this->data($pagination);
}

    public function transfer(Request $request, $id)
    {
        $owner = auth()->user();
        $agent = $owner->chargeAgent;
        $userToTransfer = User::findOrFail($id);

        $rules = [
            'amount' => ['required', 'min:1'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->validationError(422, 'The given data was invalid.', $validator);
        }

        if(!$agent){
            return $this->error('متهرجش', 403);
        }

        if($agent->balance < $request->amount){
            return $this->error('عفوا، رصيدك الحالي لا يكفي', 403);
        }

        try{
            $agent->balance-= $request->amount;
            $userToTransfer->diamond_balance+= $request->amount;
            $transaction = new ChargeAgentsHistory;
            $transaction->charge_agent_id = $agent->id;
            $transaction->user_id = $userToTransfer->id;
            $transaction->amount = $request->amount;

            $transaction->save();
            $agent->save();
            $userToTransfer->save();

            return $this->success($agent->balance, 'تمت عملية التحويل بنجاح!');

        }catch(QueryException $e){
            return $this->error('لقد حدث خطأ ما، برجاء المحاولة لاحقا', 500);
        }
    }

    public function getAdminHistory()
    {
        $user = auth()->user();
        $agent = $user->chargeAgent;

        if(!$agent){
            return $this->error('متهرجش', 403);
        }

        return $this->dataPaginated($agent->adminHistory()->paginate(20));
    }
    
    public function getUsersHistory()
    {
        $user = auth()->user();
        $agent = $user->chargeAgent;

        if(!$agent){
            return $this->error('متهرجش', 403);
        }

        return $this->dataPaginated($agent->usersHistory()->with('user')->paginate(20));
    }

    public function getUserAgent()
    {
        $user = auth()->user();
        $agent = $user->chargeAgent;

        if(!$agent){
            return $this->error('متهرجش', 403);
        }

        return $this->data($agent);
    }
}
