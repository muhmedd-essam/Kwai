<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VipUser;
use Illuminate\Http\Request;

class AdminVipUserController extends Controller
{
    public function index()
    {
        $vipUsers = VipUser::all();
        return response()->json($vipUsers);
    }

    // إنشاء سجل جديد
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'default_frame_id' => 'nullable|integer',
            'default_entry_id' => 'nullable|integer',
            'amount' => 'required|integer'
        ]);

        $vipUser = VipUser::create($request->all());
        return response()->json($vipUser, 201);
    }

    // عرض سجل محدد
    public function show($id)
    {
        $vipUser = VipUser::findOrFail($id);
        return response()->json($vipUser);
    }

    // تحديث سجل
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'default_frame_id' => 'nullable|integer',
            'default_entry_id' => 'nullable|integer',
            'amount' => 'required|integer'
        ]);

        $vipUser = VipUser::findOrFail($id);
        $vipUser->update($request->all());
        return response()->json($vipUser);
    }

    // حذف سجل
    public function destroy($id)
    {
        $vipUser = VipUser::findOrFail($id);
        $vipUser->delete();
        return response()->json(['message' => 'VipUser deleted successfully']);
    }
}
