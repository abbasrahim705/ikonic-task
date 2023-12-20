<?php

namespace App\Services;

use App\Models\User;
use App\Models\Merchant;
use App\Models\Affiliate;
use App\Mail\AffiliateCreated;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Exceptions\AffiliateCreateException;

class AffiliateService
{
    public function __construct(
        protected ApiService $apiService
    ) {}

    /**
     * Create a new affiliate for the merchant with the given commission rate.
     *
     * @param  Merchant $merchant
     * @param  string $email
     * @param  string $name
     * @param  float $commissionRate
     * @return Affiliate
     */
    public function register(Merchant $merchant, string $email, string $name, float $commissionRate): Affiliate
    {
        try {

            // below are two methods for finding existing user both are working fine
            if (
                User::where('email', $email)->where('type', User::TYPE_MERCHANT)->exists() ||
                Affiliate::where('merchant_id', $merchant->id)->whereHas('user', function ($query) use ($email) {
                    $query->where('email', $email);
                })->exists()
            ) {
                throw new AffiliateCreateException('Email is already taken/ in use');
            }
            // if (
            //     User::where('email', $email)->where('type', User::TYPE_MERCHANT)
            //         ->orWhere(function ($query) use ($email, $merchant) {
            //             $query->whereHas('affiliate', function ($subquery) use ($email) {
            //                 $subquery->where('user_id', '<>', auth()->id())->where('email', $email);
            //             })->where('merchant_id', $merchant->id);
            //         })
            //         ->exists()
            // ) {
            //     throw new AffiliateCreateException('Email is already taken/ in use');
            // }
            // first find in the existing users, if not present then create new one
            $user = User::firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'type' => User::TYPE_MERCHANT]
            );

            // if user creation/insertion has failed
            if (!$user) {
                throw new AffiliateCreateException("User creation or finding failed for email: {$email}");
            }

            // now creating new affiliate for the merchant that came in as a parameter
            $affiliate = Affiliate::create([
                'user_id' => $user->id,
                'merchant_id' => $merchant->id,
                'commission_rate' => $commissionRate,
                'discount_code' => $this->apiService->createDiscountCode($merchant)['code'],
            ]);

            // if affiliate creation/insertion has failed
            if (!$affiliate) {
                throw new AffiliateCreateException("Affiliate creation failed for email: {$email}");
            }

            // Send an email
            Mail::to($email)->send(new AffiliateCreated($affiliate));

        } catch (\Exception $e) {
            // Log the error to a log file
            Log::error("Error during affiliate registration: {$e->getMessage()}");
            // Throwing error
            throw new AffiliateCreateException("Error during affiliate registration: {$e->getMessage()}");
        }

        // Return the created Affiliate instance
        return $affiliate;

    }
}
