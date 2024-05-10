<?php

namespace App\Jobs;


use Illuminate\Bus\Queueable;
use App\Models\Patient\Patient;
use Illuminate\Support\Facades\Log;
use App\Mail\NewPaymentRegisterMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Mail\NewAppointmentRegisterMail;

class AppointmentRegisterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Patient
     */
    // public Payment $payment;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Patient $patient)
    {
        $this->patient = $patient;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            Mail::to('citasmedicas@malcolmcordova.com')
                ->send(
                    new NewAppointmentRegisterMail(
                    $this->patient,
                    )
                );
        } catch (\Exception $exception) {
            Log::info($exception->getMessage());
        }
    }
}
