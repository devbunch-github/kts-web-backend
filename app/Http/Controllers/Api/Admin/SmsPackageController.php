<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SmsPackage;

class SmsPackageController extends Controller
{
    public function index()
    {
        return response()->json(['success' => true, 'data' => SmsPackage::latest()->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'total_sms' => 'required|integer',
            'price' => 'required|numeric',
            'description' => 'nullable|string',
        ]);

        $pkg = SmsPackage::create($data);
        return response()->json(['success' => true, 'data' => $pkg]);
    }

    public function update(Request $request, $id)
    {
        $pkg = SmsPackage::findOrFail($id);
        $pkg->update($request->validate([
            'name' => 'required|string|max:255',
            'total_sms' => 'required|integer',
            'price' => 'required|numeric',
            'description' => 'nullable|string',
        ]));

        return response()->json(['success' => true, 'data' => $pkg]);
    }

    public function destroy($id)
    {
        $pkg = SmsPackage::findOrFail($id);
        $pkg->delete();

        return response()->json(['success' => true]);
    }
}