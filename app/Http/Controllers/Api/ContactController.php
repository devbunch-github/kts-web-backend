<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ContactService;

class ContactController extends Controller {
    public function __construct(private ContactService $contacts) {}

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name'=>'required|string|max:100',
            'last_name'=>'nullable|string|max:100',
            'email'=>'required|email',
            'phone_code'=>'nullable|string|max:10',
            'phone'=>'nullable|string|max:30',
            'message'=>'nullable|string|max:5000',
            'agree'=>'boolean',
        ]);

        $msg = $this->contacts->create($data);
        return response()->json(['ok'=>true,'message'=>'Thanks! We received your message.','id'=>$msg->id]);
    }
}
