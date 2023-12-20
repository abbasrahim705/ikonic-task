<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\ApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use RuntimeException;

class PayoutOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        public Order $order
    ) {}

    /**
     * Use the API service to send a payout of the correct amount.
     * Note: The order status must be paid if the payout is successful, or remain unpaid in the event of an exception.
     *
     * @return void
     */
    public function handle(ApiService $apiService)
    {
        // using try catch to handle exceptions
        try {
            DB::beginTransaction();
            // getting commission
            $commissionOwed = $this->order->commission_owed;
            // sending notificaion/email
            $apiService->sendPayout($this->order->affiliate->user->email, $commissionOwed);
            // updating the order in table
            $this->order->update([
                'payout_status' => Order::STATUS_PAID,
                // 'discount_code' => $discountCode['code'],
                'commission_owed' => $commissionOwed,
            ]);
            // saving the transaction
            DB::commit();
            return;
        } catch (\Exception $e) {
            // reverse the transaction , meaning leave the status unpaid
            DB::rollBack();
            // logging error into log file
            Log::error("Error occured : $e");
            throw $e;
        } catch (RuntimeException $e){
            // reverse the transaction , meaning leave the status unpaid
            DB::rollBack();
            // logging error into log file
            Log::error("Error occured : $e");
            throw $e;
        }

    }
}
