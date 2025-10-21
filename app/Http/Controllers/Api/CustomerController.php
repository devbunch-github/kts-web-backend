<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Eloquent\CustomerRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\CustomerReview;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;

class CustomerController extends Controller
{
    protected CustomerRepository $customers;

    public function __construct(CustomerRepository $customers)
    {
        $this->customers = $customers;
    }

    /**
     * Get current AccountId based on Auth user or provided header
     */
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
        try {
            $accId = $this->currentAccountId();
            if (!$accId) {
                return response()->json(['message' => 'No account found'], 404);
            }

            $customers = $this->customers->listByAccount($accId);
            return response()->json(['success' => true, 'data' => $customers]);
        } catch (Exception $e) {
            Log::error('CustomerController@index: '.$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Unable to load customers'], 500);
        }
    }

    public function show($id)
    {
        try {
            $accId = $this->currentAccountId();
            if (!$accId) return response()->json(['message' => 'No account found'], 404);

            $customer = $this->customers->findByAccount($accId, (int)$id);
            return response()->json(['success' => true, 'data' => $customer]);
        } catch (Exception $e) {
            Log::error("CustomerController@show: ".$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Customer not found'], 404);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'Name'         => 'required|string|max:255',
                'MobileNumber' => 'nullable|string|max:20',
                'Email'        => 'nullable|email|max:255',
                'DateOfBirth'  => 'nullable|date',
                'Note'         => 'nullable|string',
            ]);

            $accId = $this->currentAccountId();
            if (!$accId) return response()->json(['message'=>'No account found'],404);

            $customer = $this->customers->createForAccount($accId, $validated);
            return response()->json(['success' => true, 'data' => $customer], 201);
        } catch (ValidationException $ex) {
            return response()->json(['success' => false, 'errors' => $ex->errors()], 422);
        } catch (Exception $e) {
            Log::error('CustomerController@store: '.$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create customer.'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'Name'         => 'required|string|max:255',
                'MobileNumber' => 'nullable|string|max:20',
                'Email'        => 'nullable|email|max:255',
                'DateOfBirth'  => 'nullable|date',
                'Note'         => 'nullable|string',
            ]);

            $accId = $this->currentAccountId();
            if (!$accId) return response()->json(['message'=>'No account found'],404);

            $customer = $this->customers->updateForAccount($accId, (int)$id, $validated);
            return response()->json(['success' => true, 'data' => $customer]);
        } catch (ValidationException $ex) {
            return response()->json(['success' => false, 'errors' => $ex->errors()], 422);
        } catch (Exception $e) {
            Log::error('CustomerController@update: '.$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update customer.'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $accId = $this->currentAccountId();
            if (!$accId) return response()->json(['message'=>'No account found'],404);

            $this->customers->softDeleteByAccount($accId, (int)$id);
            return response()->json(['success' => true, 'message' => 'Customer deleted successfully.']);
        } catch (Exception $e) {
            Log::error('CustomerController@destroy: '.$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete customer.'], 500);
        }
    }

    public function reviews()
    {
        // return response()->json(['message'=>$this->currentAccountId()],200);
        try {
            $accId = $this->currentAccountId();
            if (!$accId) return response()->json(['message' => 'No account found'], 404);

            $reviews = CustomerReview::where('AccountId', $accId)
                ->orderByDesc('created_at')
                ->get();

            return response()->json(['success' => true, 'data' => $reviews]);
        } catch (\Exception $e) {
            \Log::error('CustomerController@reviews: '.$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Unable to load reviews.'], 500);
        }
    }

    public function updateReviewStatus(Request $request, $id)
    {
        try {
            $accId = $this->currentAccountId();
            if (!$accId) return response()->json(['message'=>'No account found'],404);

            $review = CustomerReview::where('AccountId', $accId)->findOrFail($id);
            $review->status = $request->boolean('status');
            $review->save();

            return response()->json(['success' => true, 'message' => 'Review status updated successfully.']);
        } catch (\Exception $e) {
            \Log::error('CustomerController@updateReviewStatus: '.$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update review status.'], 500);
        }
    }

    public function bulkUpdateReviewStatus(Request $request)
    {
        try {
            $accId = $this->currentAccountId();
            if (!$accId) return response()->json(['message'=>'No account found'],404);

            $validated = $request->validate([
                'ids' => 'required|array|min:1',
                'status' => 'required|boolean',
            ]);

            CustomerReview::where('AccountId', $accId)
                ->whereIn('id', $validated['ids'])
                ->update(['status' => $validated['status']]);

            return response()->json(['success' => true, 'message' => 'Bulk status update successful.']);
        } catch (\Exception $e) {
            \Log::error('CustomerController@bulkUpdateReviewStatus: '.$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed bulk update.'], 500);
        }
    }

    public function destroyReview($id)
    {
        try {
            $accId = $this->currentAccountId();
            if (!$accId) return response()->json(['message'=>'No account found'],404);

            CustomerReview::where('AccountId', $accId)->where('id', $id)->delete();

            return response()->json(['success' => true, 'message' => 'Review deleted successfully.']);
        } catch (\Exception $e) {
            \Log::error('CustomerController@destroyReview: '.$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete review.'], 500);
        }
    }
}
