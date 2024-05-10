<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Models\Appointment\Appointment;

class NotificationAppointmentWhatsapp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:notification-appointment-whatsapp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notificar al cliente una hora antes de su cita medica por whatsapp';

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
        $simulate_hour_number =date("2023-12-20 09:20:00"); //strtotime(date("2023-12-15 8:00:35"));
        $appointments = Appointment::whereDate("date_appointment", "2023-12-20")//now()->format("Y-m-d")
                                    ->where("status",1)
                                    ->get();  

        $now_time_number = strtotime($simulate_hour_number); //now()->format("Y-m-d h:i:s")
        $patients = collect([]);
        
        foreach($appointments as $key => $appointment){
            
            $hour_start = $appointment->doctor_schedule_join_hour->doctor_schedule_hour->hour_start;
            $hour_end = $appointment->doctor_schedule_join_hour->doctor_schedule_hour->hour_end;
            
            // $hour_start = strtotime(Carbon::parse(date("Y-m-d")." ".$hour_start)->subHour());
            // $hour_end = strtotime(Carbon::parse(date("Y-m-d")." ".$hour_end)->subHour());
            $hour_start = strtotime(Carbon::parse(date("2023-12-20")." ".$hour_start)->subHour());
            $hour_end = strtotime(Carbon::parse(date("2023-12-20")." ".$hour_end)->subHour());
            // error_log($hour_start.' '.$hour_end.' '.$simulate_hour_number );
            if( $hour_start <= $now_time_number && $hour_end >= $now_time_number ){
                $patients->push([
                    "name"=> $appointment->patient->name,
                    "surname"=> $appointment->patient->surname,
                    "avatar"=> $appointment->avatar ? env("APP_URL").$this->resource->avatar : null,
                    // "avatar"=> $appointment->avatar ? env("APP_URL")."storage/".$this->resource->avatar : null,
                    "email"=> $appointment->patient->email,
                    "speciality_name"=> $appointment->speciality->name,
                    "doctor_full_name"=> $appointment->doctor->name.' '.$appointment->doctor->surname,
                    "phone"=> $appointment->patient->phone,
                    "n_doc"=> $appointment->patient->n_doc,
                    "hour_start_format"=> Carbon::parse(date("Y-m-d")." ".$appointment->doctor_schedule_join_hour->doctor_schedule_hour->hour_start)->format("h:i A"),
                    "hour_end_format"=> Carbon::parse(date("Y-m-d")." ".$appointment->doctor_schedule_join_hour->doctor_schedule_hour->hour_end)->format("h:i A"),
                ]);
            }
        }

        foreach ($patients as $key => $patient) {
            $accessToken = 'EAAMkvhEgKcABOydvuU7hZCCSmHjv1Pyw3UAZCR6m1XYMY8lmAlhRj0YIpq4SDApZCi0LPHobToDzvZCnBZCVc5ZC2CGITJYSyZB1YlJngSgPCW2Se0mBCZB3DM4fi7JInxf7i0TtJ2zcFK4lBPFZCyUHGjKXxnROGZBcPnTc5teXQUig1sHonNqDqZCdl8rB2R2ddBeYMOzuraMKD0XZCQxOuqikfjO4ybaKc6i5';
         
            $fbApiUrl = 'https://graph.facebook.com/v17.0/143982372142697/messages';
            
            $data = [
                'messaging_product' => 'whatsapp',
                'to' => '584241874370',
                'type' => 'template',
                'template' => [
                    'name' => 'recordatorio',
                    'language' => [
                        'code' => 'es_MX',
                    ],
                    "components"=>  [
                        [
                            "type" =>  "header",
                            "parameters"=>  [
                                [
                                    "type"=>  "text",
                                    "text"=>  $patient["name"].' '.$patient["surname"],
                                ]
                            ]
                        ],
                        [
                            "type" => "body",
                            "parameters" => [
                                [
                                    "type"=> "text",
                                    "text"=>  $patient["hour_start_format"]. ' hasta '.$patient["hour_end_format"],
                                ],
                                [
                                    "type"=> "text",
                                    "text"=>  $patient["doctor_full_name"]
                                ],
                            ] 
                        ],
                    ],
                ],
            ];
            
            $headers = [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
            ];
            
            $ch = curl_init($fbApiUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            curl_close($ch);
            
            echo "HTTP Code: $httpCode\n";
            echo "Response:\n$response\n";
        }
        
        dd($patients);
    }
}
