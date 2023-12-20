<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Models\Merchant;
use App\Models\Affiliate;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class OrderService
{
    public function __construct(
        protected AffiliateService $affiliateService
    ) {}

    /**
     * Process an order and log any commissions.
     * This should create a new affiliate if the customer_email is not already associated with one.
     * This method should also ignore duplicates based on order_id.
     *
     * @param  array{order_id: string, subtotal_price: float, merchant_domain: string, discount_code: string, customer_email: string, customer_name: string} $data
     * @return void
     */
    public function processOrder(array $data)
    {
        try {

            // Check for duplicate orders based on order_id
            if (Order::where('external_order_id',$data['order_id'])->exists()) {
                // Log that the order is a duplicate and return
                Log::info("Duplicate order with order_id: ".$data['order_id']);
                return;
            }

            // finding user by email
            $user = User::where('email',$data['customer_email'])->first();

            // Check if the user is already associated with an affiliate
            $affiliate = $user->affiliate ?? Affiliate::where('discount_code',$data['discount_code'])->first();
            $merchant = $affiliate ? ($affiliate->merchant ?? null) : null;
            if (!$affiliate) {
                $merchant = Merchant::where('domain', $data['merchant_domain'])->first();
                $user = User::where('email',$data['customer_email'])->first();
                $userId = $user->id;
                $affiliate = Affiliate::Create([
                    'user_id' => $userId,
                    'merchant_id' => $merchant->id,
                    'commission_rate' => $merchant->default_commission_rate,
                    'discount_code' => $data['discount_code']
                ]);
            }else{
                   // Associate the user with the merchant as an affiliate
                    $affiliateRegistration = $this->affiliateService->register(
                    $merchant,
                    $data['customer_email'],
                    $data['customer_name'],
                    $merchant->default_commission_rate
                );
            }
            // Update if exists or Create the order
            $order = Order::updateOrCreate(
                ['external_order_id' => $data['order_id']],
                ['merchant_id' => $merchant->id,
                'affiliate_id' => $affiliate->id,
                'subtotal' => $data['subtotal_price'],
                'commission_owed' => $data['subtotal_price'] * ($affiliate ? $affiliate->commission_rate : $merchant->default_commission_rate),
                'payout_status' => Order::STATUS_PAID,
                'discount_code' => $data['discount_code']
                ]
            );

            // Log any commissions or other order processing logic
            $this->logCommissions($affiliate, $order);
            return;

        } catch (ModelNotFoundException $e) {
            // Log the error if a model is not found
            Log::error("Model not found: {$e->getMessage()}");
            throw new \Exception("Error processing order: {$e->getMessage()}");
        } catch (\Exception $e) {
            // Log the generic error
            Log::error("Error processing order: {$e->getMessage()}");
            throw new \Exception("Error processing order: {$e->getMessage()}");
        }

    }

    protected function logCommissions(?Affiliate $affiliate, Order $order)
    {
        // logging commision
        if ($affiliate) {
            Log::info("Commission logged for order_id: {$order->order_id}, affiliate_id: {$affiliate->id}");
        } else {
            Log::info("No affiliate associated with order_id: {$order->order_id}");
        }
    }

}
