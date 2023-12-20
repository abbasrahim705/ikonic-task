<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Models\Merchant;
use App\Models\Affiliate;
use App\Jobs\PayoutOrderJob;
use Illuminate\Support\Facades\Log;
use App\Exceptions\NoUnpaidOrdersException;
use Exception;

class MerchantService
{
    /**
     * Register a new user and associated merchant.
     * Hint: Use the password field to store the API key.
     * Hint: Be sure to set the correct user type according to the constants in the User model.
     *
     * @param array{domain: string, name: string, email: string, api_key: string} $data
     * @return Merchant
     */
    public function register(array $data): Merchant
    {
        try {
            $type = User::TYPE_MERCHANT;
            $newUser = [
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['api_key'],
                'type' => $type
            ];

            $user = User::create($newUser);

            // check if user creation failed
            if (!$user) {
                throw new Exception('User creation failed.');
            }
            $newMerchant = [
                'domain' => $data['domain'],
                'display_name' => $data['name'],
                'user_id' => $user->id
            ];

            $merchant = Merchant::Create($newMerchant);

            // check if merchant creation failed
            if (!$merchant) {
                throw new Exception('Merchant creation failed.');
            }

            return $merchant;

        }catch(\Exception $e){
            // logging error
            Log::error("Error Occured : $e");
        }
    }

    /**
     * Update the user
     *
     * @param array{domain: string, name: string, email: string, api_key: string} $data
     * @return void
     */
    public function updateMerchant(User $user, array $data)
    {
        try{
            // data to be updated at user table
            $userData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['api_key']
            ];
            // updating data at user table
            $user->update($userData);

            // data to be updated at merchant table
            $updatedData = [
                'domain' => $data['domain'],
                'display_name' => $data['name']
            ];
            // updating data at merchant table
            $user->merchant->update($updatedData);
        }catch (\Exception $e) {
            // Logging the error
            Log::error("Error Occurred: {$e->getMessage()}");

            // Throwing a custom exception
            throw new Exception('Data update failed.');
        }

    }

    /**
     * Find a merchant by their email.
     * Hint: You'll need to look up the user first.
     *
     * @param string $email
     * @return Merchant|null
     */
    public function findMerchantByEmail(string $email): ?Merchant
    {
        // finding first user by email, if present then merchant and return the result
        return optional(User::whereEmail($email)->first())->merchant;
    }

    /**
     * Pay out all of an affiliate's orders.
     * Hint: You'll need to dispatch the job for each unpaid order.
     *
     * @param Affiliate $affiliate
     * @return void
     */
    public function payout(Affiliate $affiliate)
    {
        try {
            // first taking / fetching out all those affiliate's orders whose status is unpaid
            $unpaidOrders = $affiliate->orders()
                ->where('payout_status', Order::STATUS_UNPAID)
                ->get();

            // just in case there are no unpaid orders for an affiliate
            if ($unpaidOrders->isEmpty()) {
                throw new NoUnpaidOrdersException("No unpaid orders for affiliate ID: {$affiliate->id}");
            }

            // now dispatching them one by one for pay out
            foreach ($unpaidOrders as $order) {
                PayoutOrderJob::dispatch($order);
            }
        } catch (NoUnpaidOrdersException $e) {
            // logging error to the log file
            Log::error("Error occured : $e");
            throw $e;
        } catch (\Exception $e) {
           // logging error to the log file
           Log::error("Error occured : $e");
           throw $e;
        }
    }
}
