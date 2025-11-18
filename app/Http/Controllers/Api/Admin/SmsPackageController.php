<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SmsPackage;

class SmsPackageController extends Controller
{
    public function index()
    {
        $packages = SmsPackage::orderBy('price', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $packages
        ]);
    }

    public function show($id)
    {
        $package = SmsPackage::findOrFail($id);
        return response()->json(['success' => true, 'data' => $package]);
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

        return response()->json(['success' => true, 'message' => 'Package updated successfully', 'data' => $pkg]);
    }

    public function destroy($id)
    {
        $package = SmsPackage::findOrFail($id);
        if (!$package) {
            return response()->json([
                'success' => false,
                'message' => 'Package not found.',
            ], 404);
        }

        $package->delete();

        return response()->json([
            'success' => true,
            'message' => 'Package deleted successfully.',
        ]);
    }

    public function purchasebalance()
    {
        // For demo; replace later with DB logic
        $data = [
            'total_balance' => 1600.00,
            'total_sms' => 1600,
            'used_sms' => 400,
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function getSmsPackages()
    {
        $packages = SmsPackage::select('id', 'name', 'price', 'total_sms', 'description')->get();

        return response()->json([
            'success' => true,
            'data' => $packages
        ]);
    }
}
