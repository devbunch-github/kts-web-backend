<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Accountant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\AccountantCreatedMail;
use App\Mail\AccountantPasswordResetMail;


class AccountantController extends Controller
{
    protected function currentAccountId(): ?int
    {
        if (Auth::check()) {
            return Auth::user()?->bkUser?->account?->Id;
        }
        $userId = request()->header('X-User-Id') ?? request('user_id');
        if ($userId) {
            $user = User::find($userId);
            return $user?->bkUser?->account?->Id;
        }
        return null;
    }

    public function index()
    {
        $accId = $this->currentAccountId();
        if (!$accId) return response()->json(['success'=>false,'message'=>'No account found'], 404);

        $rows = Accountant::where('AccountId', $accId)
            ->orderBy('created_at', 'desc')
            ->get(['id','name','email','is_active','created_at']);

        return response()->json(['success'=>true,'data'=>$rows]);
    }

    public function store(Request $request)
    {
        $accId = $this->currentAccountId();
        if (!$accId) return response()->json(['success'=>false,'message'=>'No account found'],404);

        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => [
                'required', 'email', 'max:255',
                Rule::unique('accountants', 'email')->where('AccountId', $accId),
                Rule::unique('users', 'email'),
            ],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $password = $validated['password'] ?? Str::random(10);

        $acct = Accountant::create([
            'AccountId' => $accId,
            'created_by' => Auth::id(), // store Business Admin ID
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'password'  => Hash::make($password),
            'is_active' => true,
        ]);

        // Create corresponding user account (for login)
        $user = User::create([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'password'  => Hash::make($password),
        ]);

        // ✅ Assign Spatie Role + Permissions
        $roleName = 'accountant';

        // Create role if not exists
        if (!\Spatie\Permission\Models\Role::where('name', $roleName)->exists()) {
            $role = \Spatie\Permission\Models\Role::create(['name' => $roleName, 'guard_name' => 'web']);
        } else {
            $role = \Spatie\Permission\Models\Role::where('name', $roleName)->first();
        }

        // Ensure required permissions exist
        $permissions = ['financial_reports'];
        foreach ($permissions as $perm) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // Assign role & permissions to user
        $user->assignRole($roleName);
        $user->givePermissionTo($permissions);


        // Send email notification
        Mail::to($acct->email)->send(new AccountantCreatedMail($acct, $password));

        return response()->json(['success'=>true,'data'=>$acct, 'message' => 'Accountant created successfully, role & permissions assigned, and email sent.',]);
    }

    public function update(Request $request, $id)
    {
        $accId = $this->currentAccountId();
        if (!$accId) return response()->json(['success'=>false,'message'=>'No account found'],404);

        $acct = Accountant::where('AccountId',$accId)->findOrFail($id);

        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => [
                'required', 'email', 'max:255',
                Rule::unique('accountants', 'email')->where('AccountId', $accId)->ignore($acct->id),
                Rule::unique('users', 'email')->ignore($acct->email, 'email'),
            ],
            'is_active' => ['nullable', 'boolean'],
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        $acct->update([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'is_active' => $validated['is_active'] ?? $acct->is_active,
        ]);

        // Sync email in users table too
        $user = User::where('email', $acct->getOriginal('email'))->first();
        if ($user) {
            $user->update(['name' => $acct->name, 'email' => $acct->email]);
        }

        if (!empty($validated['password'])) {
            $acct->password = Hash::make($validated['password']);
            $acct->save();

            $user->password = Hash::make($validated['password']);
            $user->save();
        }

        return response()->json(['success'=>true,'data'=>$acct]);
    }

    public function destroy($id)
    {
        $accId = $this->currentAccountId();
        if (!$accId) return response()->json(['success'=>false,'message'=>'No account found'],404);

        $acct = Accountant::where('AccountId', $accId)->where('id', $id)->first();

        if (!$acct) {
            return response()->json([
                'success' => false,
                'message' => 'Accountant not found or does not belong to your account.'
            ], 404);
        }

        // Delete linked user
        $acctEmail = $acct->email;
        User::where('email', $acctEmail)->delete();

        $acct->delete();
        // $acct->forceDelete();


        return response()->json(['success'=>true,'message'=>'Accountant deleted']);
    }

    public function resetPassword(Request $request, $id)
    {
        $accId = $this->currentAccountId();
        if (!$accId) return response()->json(['success'=>false,'message'=>'No account found'],404);

        $validated = $request->validate([
            'password' => 'required|min:6|confirmed',
        ]);

        $acct = Accountant::where('AccountId', $accId)->findOrFail($id);
        $acct->update(['password' => Hash::make($validated['password'])]);

        // Update user table too
        $user = User::where('email', $acct->email)->first();
        if ($user) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        // Send password reset email
        Mail::to($acct->email)->send(new AccountantPasswordResetMail($acct));

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully and email sent.',
        ]);
    }

    /**
     * Toggle (revoke/restore) accountant access
     */
    public function toggleAccess($id)
    {
        $accId = $this->currentAccountId();
        if (!$accId) {
            return response()->json([
                'success' => false,
                'message' => 'No account found.'
            ], 404);
        }

        $accountant = Accountant::where('AccountId', $accId)->find($id);

        if (!$accountant) {
            return response()->json([
                'success' => false,
                'message' => 'Accountant not found.'
            ], 404);
        }

        // Toggle is_active status
        $accountant->is_active = !$accountant->is_active;
        $accountant->save();

        // Also update corresponding user account (for login control)
        // $user = User::where('email', $accountant->email)->first();
        // if ($user) {
        //     $user->update(['is_active' => $accountant->is_active]);
        // }

        return response()->json([
            'success' => true,
            'message' => $accountant->is_active
                ? 'Access restored successfully.'
                : 'Access revoked successfully.',
            'data' => $accountant
        ]);
    }

    public function show($id)
    {
        $accId = $this->currentAccountId();
        if (!$accId) {
            return response()->json(['success' => false, 'message' => 'No account found'], 404);
        }

        // ✅ Find accountant under the current account
        $accountant = Accountant::where('AccountId', $accId)->find($id);

        if (!$accountant) {
            return response()->json(['success' => false, 'message' => 'Accountant not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id'         => $accountant->id,
                'name'       => $accountant->name,
                'email'      => $accountant->email,
                'is_active'  => (bool) $accountant->is_active,
                'created_at' => $accountant->created_at,
            ],
        ]);
    }

}
