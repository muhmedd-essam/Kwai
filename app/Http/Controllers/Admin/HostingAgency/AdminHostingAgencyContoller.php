<?php

namespace App\Http\Controllers\Admin\HostingAgency;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use App\Traits\WebTrait;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;
use App\Models\HostingAgency\HostingAgency;
use App\Models\HostingAgency\HostingAgencyMember;

use App\Models\HostingAgency\HostingAgencyMemberBd; // Ensure this matches the model's namespace

use App\Models\User;

class AdminHostingAgencyContoller extends Controller
{
    use WebTrait;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $agencies = HostingAgency::with('owner')->withCount('members')->orderBy('id', 'DESC')->paginate(12);

        return $this->data($agencies);
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
            'name' => ['required', 'string', 'min:2', 'max:70', 'unique:hosting_agencies,name'],
            'description' => ['required', 'string', 'min:5', 'max:1500'],
            'cover_image' => ['required', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'owner_id' => ['required', 'numeric', 'exists:users,id'],
            'bd' => [ 'numeric', 'exists:users,id'],
            'owner_bd' => [ 'numeric', 'exists:users,id'],

        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->validationError($code, $validator);
        }

        $user = User::findOrFail($request->owner_id);

        if($user->is_hosting_agency_owner == 1 || $user->is_hosting_agent == 1)
        {
            return $this->error(403, 'المستخدم إما مضيف في وكالة أو صاحب وكالة اخرى، يجب عليه مغادرة وكالته اولا');
        }

        //Upload
        $cover = Storage::disk('public')->putFile('images/hosting-agencies/covers', new File($request->cover_image));

        $aId = $this->generateAId();



        try{
            $hostingAgency = HostingAgency::insert(['name' => $request->name, 'description' => $request->description, 'cover' => $cover, 'aid' => $aId, 'owner_id' => $request->owner_id]);

            $agency = HostingAgency::where('owner_id', $user->id)->first();
            // register admin as member in hosting member

                $newMember = HostingAgencyMember::create([
                    'hosting_agency_id' => $agency->id,
                    'user_id' => $user->id,
                    'current_target_id' => 1,
                    'current_target_video_id' => 1,
                    // 'bd'=>$request->bd,
                ]);
            //     // dd($request->owner_bd);
            // register bd as a member in hosting member bd
                // $newMemberBd = HostingAgencyMemberBd::insert([
                //     'hosting_agency_id' => $agency->id,
                //     'user_id' => $request->bd,
                //     'current_target_id' => 1,
                //     'owner_bd'=>$request->owner_bd,
                // ]);


            // give the gift of vip
                if($user->vip < 4){
                    $user->vip = 4;
                $user->save();
                 }
            // register in table user
            $user->is_hosting_agency_owner = 1;
            $user->is_hosting_agent = 1;
            $user->save();

            return $this->success('S100');
        }catch (\Exception $e) {
            \Log::error($e->getMessage());
            return response()->json(['status' => false, 'errNum' => 'E200', 'msg' => $e->getMessage()]);
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
        $agency = HostingAgency::withCount('members')->with('owner', 'members.user')->findOrFail($id);

        return $this->data($agency);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $hostingAgency = HostingAgency::findOrFail($id);

        $rules = [
            'name' => ['string', 'min:2', 'max:70', 'unique:hosting_agencies,name'],
            'description' => ['string', 'min:5', 'max:1500'],
            'cover_image' => ['mimes:jpg,jpeg,png,webp', 'max:2048'],
            'owner_id' => ['numeric', 'exists:users,id'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->validationError($code, $validator);
        }

        //Upload
        if($request->hasFile('cover')){
            $cover = Storage::disk('public')->putFile('images/hosting-agencies/covers', new File($request->cover));

            $request->request->add(['cover' => $cover]);
        }

        if(isset($request->owner_id) && $request->owner_id != $hostingAgency->owner_id){
            $oldOwner = User::findOrFail($hostingAgency->owner_id);
            $newOwner = User::findOrFail($request->owner_id);

            if($newOwner->is_hosting_agency_owner == 1 || $newOwner->is_hosting_agent = 1)
            {
                return $this->error(403, 'المستخدم إما مضيف في وكالة أو صاحب وكالة اخرى، يجب عليه مغادرة وكالته اولا');
            }

            $oldOwner->is_hosting_agency_owner =  0;
            $newOwner->is_hosting_agency_owner = 1;
            $oldOwner->save();
            $newOwner->save();
        }

        try{
            $update = $request->all();

            foreach($update as $key => $value) {
                $hostingAgency->$key = $value;
            }
            $hostingAgency->save();

            return $this->success('S100');
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
        $agency = HostingAgency::findOrFail($id);

        $user = $agency->owner;
        $user->is_hosting_agency_owner = 0;
        $user->	is_hosting_agent = 0;
        $user->save();

        $agency->delete();

        return $this->success('S103');
    }

    protected function generateAId()
    {
        $aId = rand(1000, 9999);

        if($this->aIdExists($aId)){
            $this->generateAId();
        }

        return $aId;
    }

    protected function aIdExists($aId)
    {
        if(HostingAgency::where('aid', $aId)->exists()){
            return true;
        }

        return false;
    }
}
