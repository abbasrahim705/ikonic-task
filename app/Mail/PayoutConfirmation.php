<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PayoutConfirmation extends Mailable
{
    use Queueable, SerializesModels;
    protected $amount;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($amount)
    {
        $this->amount = $amount;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        dd($this->view('emails.email')->with(['amount'=>$this->amount]));
        return $this->view('emails.email')->with(['amount'=>$this->amount]);
    }
}
