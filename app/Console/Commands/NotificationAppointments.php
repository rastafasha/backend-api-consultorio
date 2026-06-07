<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotificationAppoint;
use App\Models\Appointment\Appointment;
use Carbon\Carbon;
use Illuminate\Console\Command;

class NotificationAppointments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:notification-appointments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notificar al cliente una hora antes de su cita medica por email';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
   public function handle()
    {
        date_default_timezone_set('America/Caracas');
        
        $appointments = Appointment::whereDate("date_appointment", now()->format("Y-m-d"))
                                    ->where("status", 1)
                                    ->where("cron_state", 1)
                                    ->get();  

        $now_time_number = strtotime(now()->format("Y-m-d H:i:s")); 
        $patients = collect([]);
        
        // Creamos una colección exclusiva para acumular los WhatsApps destinados a Node.js
        $whatsappQueue = collect([]);

        foreach($appointments as $key => $appointment){
            
            $hour_start_raw = $appointment->doctor_schedule_join_hour->doctor_schedule_hour->hour_start;
            $hour_end_raw = $appointment->doctor_schedule_join_hour->doctor_schedule_hour->hour_end;
            
            $hour_start = strtotime(Carbon::parse(date("Y-m-d")." ".$hour_start_raw)->subHour());
            $hour_end = strtotime(Carbon::parse(date("Y-m-d")." ".$hour_end_raw)->subHour());
            
            if( $hour_start <= $now_time_number && $hour_end >= $now_time_number ){
                
                $hour_start_format = Carbon::parse(date("Y-m-d")." ".$hour_start_raw)->format("h:i A");
                $hour_end_format = Carbon::parse(date("Y-m-d")." ".$hour_end_raw)->format("h:i A");

                $patientData = [
                    "name"            => $appointment->patient->name,
                    "surname"         => $appointment->patient->surname,
                    "avatar"          => $appointment->patient->avatar ? env("APP_URL")."storage/".$appointment->patient->avatar : null,
                    "email"           => $appointment->patient->email,
                    "speciality_name" => $appointment->speciality->name,
                    "phone"           => $appointment->patient->phone,
                    "n_doc"           => $appointment->patient->n_doc,
                    "doctor_id"       => (string) $appointment->doctor_id,
                    "hour_start_format" => $hour_start_format,
                    "hour_end_format"   => $hour_end_format,
                ];

                $patients->push($patientData);

                // Acumulamos el mensaje en nuestra lista de WhatsApp
                $whatsappQueue->push([
                    'doctor_id' => $patientData['doctor_id'],
                    'telefono'  => $patientData['phone'],
                    'mensaje'   => "Hola {$patientData['name']} {$patientData['surname']}, le recordamos su cita médica de {$patientData['speciality_name']} hoy entre las {$hour_start_format} y {$hour_end_format}."
                ]);

                $appointment->update(["cron_state" => 2]);
            }
        }

        // 🚀 UN SOLO DISPARO HTTP PARA TODO EL LOTE (Ideal para Shared Hosting)
        if ($whatsappQueue->count() > 0) {
            try {
                // Enviamos el array completo a un endpoint bulk en tu Node.js externo
                Http::post('https://back-klyntic-envios.onrender.com', [
                    'recordatorios' => $whatsappQueue->toArray()
                ]);
                $this->info($whatsappQueue->count() . ' Recordatorios de WhatsApp enviados en lote a Node.');
            } catch (\Exception $e) {
                $this->error('Error enviando lote a Node: ' . $e->getMessage());
            }
        }

        // Procesamos el envío de correos tradicional (que suele ser asíncrono o rápido)
        if ($patients->count() > 0) {
            foreach ($patients as $key => $patient) {
                try {
                    Mail::to($patient["email"])->send(new NotificationAppoint($patient));
                } catch (\Exception $e) {
                    $this->error('Failed to send email to: ' . $patient["email"] . ' - ' . $e->getMessage());
                }
            }
        }

        return 0;
    }
}
