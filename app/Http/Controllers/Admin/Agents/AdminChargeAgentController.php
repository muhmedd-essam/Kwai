<?php

namespace App\Http\Controllers\Admin\Agents;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use App\Traits\WebTrait;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;
use App\Models\User;
use App\Models\Agents\ChargeAgent;
use App\Models\Agents\ChargeAgentAdminHistory;

class AdminChargeAgentController extends Controller
{
    use WebTrait;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $agents = ChargeAgent::with('owner')->orderBy('id', 'DESC')->paginate(12);

        return $this->data($agents);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $rules = [
            'user_id' => ['required', 'numeric', 'exists:users,id', 'unique:charge_agents,user_id'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->validationError($code, $validator);
        }

        $user = User::findOrFail($request->user_id);

        $request->request->add(['charge_agent_no' => $this->generateChargeAgentNo()]);

        try{
            $user->is_charge_agent = 1;
            $user->save();
            $agent = ChargeAgent::create($request->all());
            
            return $this->success('S100');
        }catch(QueryException $e){
            return $this->error('E200', '');
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
        $agent = ChargeAgent::with('owner')->findOrFail($id);

        return $this->data($agent);
    }

    public function getAdminHistory($id)
    {
        $agent = ChargeAgent::with('owner')->findOrFail($id);

        return $this->data(['agent' => $agent, 'history' => $agent->adminHistory()->paginate(20)]);
    }

    public function getUsersHistory($id)
    {
        $agent = ChargeAgent::with('owner')->findOrFail($id);

        return $this->data(['agent' => $agent, 'history' => $agent->usersHistory()->paginate(20)]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateBalance(Request $request, $id)
    {
        $chargeAgent = ChargeAgent::findOrFail($id);

        $rules = [
            'amount' => ['required', 'numeric', 'min:1'],
            'type' => ['required', 'in:0,1'], // 0 => deposite, 1 => withdrawl
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->validationError($code, $validator);
        }

        try{
            if($request->type == 0){
                $chargeAgent->balance+= $request->amount;
            }else{
                $chargeAgent->balance-= $request->amount;
            }
            $chargeAgent->save();

            $record = new ChargeAgentAdminHistory;
            $record->charge_agent_id = $chargeAgent->id;
            $record->amount = $request->amount;
            $record->type = $request->type;
            $record->save();

            return $this->success('S101');
        }catch(QueryException $e){
            return $this->error('E200', '');
        }
        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $agent = ChargeAgent::findOrFail($id);

        User::where('id', $agent->user_id)->update(['is_charge_agent' => 0]);

        $agent->delete();

        return $this->success('S103');
    }

    protected function generateChargeAgentNo()
    {
        $chargeAgentNo = rand(10000, 99999);

        if($this->chargeAgentNoExists($chargeAgentNo)){
            $this->generateChargeAgentNo();
        }

        return $chargeAgentNo;
    }

    protected function chargeAgentNoExists($chargeAgentNo)
    {
        if(ChargeAgent::where('charge_agent_no', $chargeAgentNo)->exists()){
            return true;
        }

        return false;
    }

}
