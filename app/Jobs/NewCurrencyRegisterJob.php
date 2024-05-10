<?php

namespace App\Jobs;

use App\Mail\NewCurrencyRegisterMail;
use App\Models\Currency;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NewCurrencyRegisterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Currency
     */
    // public Currency $currency;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Currency $currency)
    {
        $this->currency = $currency;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            Mail::to('soporte@ursigalletti.net')
                ->send(
                    new NewCurrencyRegisterMail(
                    $this->currency,
                    )
                );
        } catch (\Exception $exception) {
            Log::info($exception->getMessage());
        }
    }
}
