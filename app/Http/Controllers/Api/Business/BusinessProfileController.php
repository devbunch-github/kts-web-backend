<?php

namespace App\Http\Controllers\Api\Business;

use App\Http\Controllers\Controller;
use App\Models\BusinessProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Exception;

class BusinessProfileController extends Controller
{
    protected function currentAccount()
    {
        $account = auth()->user()?->bkUser?->account;
        if (!$account) {
            throw new Exception('No account found for this user.');
        }
        return $account;
    }

    protected function currentAccountId(): int
    {
        return (int) $this->currentAccount()->Id;
    }

    public function show()
    {
        $account = $this->currentAccount();
        $user = Auth::user();
        $profile = BusinessProfile::where('AccountId', $account->Id)->first();

        return response()->json([
            'data' => [
                'name'          => $account->Name ?? '',
                'email'         => $user->email ?? '',
                'phone_number'  => $profile?->phone_number ?? '',
                'date_of_birth' => $account->DateOfBirth ?? null,
                'image_url'     => $profile?->image_url ?? null,
                'AccountId'     => $account->Id,
            ],
        ]);
    }

    public function update(Request $request)
    {
        $account = $this->currentAccount();
        $accountId = (int) $account->Id;

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'password' => 'nullable|min:6|confirmed',
            'image' => 'nullable|image|max:2048',
        ]);

        DB::beginTransaction();
        try {
            $profile = BusinessProfile::firstOrCreate(['AccountId' => $accountId]);

            if (isset($validated['phone_number'])) {
                $profile->phone_number = $validated['phone_number'];
            }

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('profile-images', 'public');
                $profile->image_url = Storage::url($path);
            }

            $profile->save();

            if (isset($validated['name'])) {
                $account->Name = $validated['name'];
            }
            if (isset($validated['date_of_birth'])) {
                $account->DateOfBirth = $validated['date_of_birth'];
            }
            $account->save();

            if ($request->filled('password')) {
                $user = Auth::user();
                $user->password = bcrypt($request->password);
                $user->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'name'          => $account->Name,
                    'email'         => Auth::user()->email,
                    'phone_number'  => $profile->phone_number,
                    'date_of_birth' => $account->DateOfBirth,
                    'image_url'     => $profile->image_url,
                ],
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile: ' . $e->getMessage(),
            ], 500);
        }
    }
}
