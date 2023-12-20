<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use App\Services\AffiliateService;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    /**
     * Pass the necessary data to the process order method
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        // fetching data from request
        $data = $request->all();
        // taking care of exceptions
        try {
            // method at OrderService Class to process the order
            $this->orderService->processOrder($data);
            Log::info("Webhook has been processed successfully");
            return response()->json(
                ['message' => 'Webhook has been processed successfully']
                , 200
            );
        } catch (\Exception $e) {
            // Logging error to the log file
            Log::error("Error in processing webhook. Error : $e");
            return response()->json(
                ['error' => 'Error in processing webhook']
                , 500
            );
        }
    }
}
