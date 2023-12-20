<?php

namespace App\Services;

use RuntimeException;
use App\Models\Merchant;
use Illuminate\Support\Str;
use App\Mail\PayoutConfirmation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * You don't need to do anything here. This is just to help
 */
class ApiService
{
    /**
     * Create a new discount code for an affiliate
     *
     * @param Merchant $merchant
     *
     * @return array{id: int, code: string}
     */
    public function createDiscountCode(Merchant $merchant): array
    {
        return [
            'id' => rand(0, 100000),
            'code' => Str::uuid()
        ];
    }

    /**
     * Send a payout to an email
     *
     * @param  string $email
     * @param  float $amount
     * @return void
     * @throws RuntimeException
     */
    public function sendPayout(string $email, float $amount)
    {
        try {
            $this->sendPayoutConfirmationEmail($email, $amount);
            Log::info("Payout sent to $email. Amount: $amount");
            return ;
        } catch (\Exception $e) {
            // logging error to the log file
            Log::error('Error sending payout confirmation email: ' . $e->getMessage());
            throw new RuntimeException('Payout confirmation email failed');
        }
    }

    protected function sendPayoutConfirmationEmail(string $email, float $amount)
    {
        try {
            Mail::to($email)->send(new PayoutConfirmation($amount));
        } catch (\Exception $e) {
            // logging error to the log file
            Log::error('Error sending payout confirmation email: ' . $e->getMessage());
            throw new RuntimeException('Payout confirmation email failed');
        }
    }
}
