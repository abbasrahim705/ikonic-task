<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Merchant;
use Illuminate\Http\Request;
use App\Services\OrderService;
use Illuminate\Support\Carbon;
use App\Services\MerchantService;
use Illuminate\Http\JsonResponse;

class MerchantController extends Controller
{
    // protected $orderService;
    public function __construct(
        MerchantService $merchantService
    ) {}

    /**
     * Useful order statistics for the merchant API.
     *
     * @param Request $request Will include a from and to date
     * @return JsonResponse Should be in the form {count: total number of orders in range, commission_owed: amount of unpaid commissions for orders with an affiliate, revenue: sum order subtotals}
     */
    public function orderStats(Request $request): JsonResponse
    {
          // Validate the request data
          $validatedData = $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);

        // total number of orders between dates
        $orderCount = Order::whereBetween('created_at', [$validatedData['from'], $validatedData['to']])->count();
        // total unpaid commissions
        $unpaidCommissions = Order::whereNot('affiliate_id', null)
            ->whereBetween('created_at', [$validatedData['from'], $validatedData['to']])
            ->sum('commission_owed');
        // total revenue
        $revenue = Order::whereBetween('created_at', [$validatedData['from'], $validatedData['to']])->sum('subtotal');

        // Returning response in json format
        return response()->json([
            'count' => $orderCount,
            'commissions_owed' => $unpaidCommissions,
            'revenue' => $revenue
            ]);

    }
}
