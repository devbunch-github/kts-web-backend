<?php

namespace App\Http\Controllers\Api\Business;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Notification;
use Exception;

class NotificationController extends Controller
{

    protected function currentAccountId(): int
    {
        // mirror your PromoCode pattern exactly
        return auth()->user()?->bkUser?->account->Id ?? throw new Exception('No account found');
    }

    public function index(Request $request)
    {
        if($this->currentAccountId() <= 0){
            return response()->json(['message' => 'No account found'], 404);
        }

        $response['notifications'] = Notification::where('AccountId', $this->currentAccountId())
            ->whereNull('ReadDateTime')
            ->select('Id as id', 'Header as header', 'Message as message', 'ReadDateTime as readDateTime', 'Discriminator as discriminator')
            ->get();

        return response()->json($response, 200);
    }
}
