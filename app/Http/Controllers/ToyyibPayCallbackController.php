<?php

namespace App\Http\Controllers;

use App\Services\ToyyibPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ToyyibPayCallbackController extends Controller
{
    protected $toyyibpayService;

    public function __construct(ToyyibPayService $toyyibpayService)
    {
        $this->toyyibpayService = $toyyibpayService;
    }

    public function handle(Request $request)
    {
        Log::info('ToyyibPay callback received', $request->all());

        $result = $this->toyyibpayService->processCallback($request->all());

        if ($result['success']) {
            return response()->json(['status' => 'success'], 200);
        }

        return response()->json(['status' => 'failed'], 400);
    }
}
