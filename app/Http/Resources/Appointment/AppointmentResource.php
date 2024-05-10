<?php

namespace App\Http\Resources\Appointment;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            "id" =>$this->resource->id,
            
            "doctor_id" =>$this->resource->doctor_id,
            "doctor" =>$this->resource->doctor ? 
                [
                    "id" =>$this->resource->doctor->id,
                    "full_name" =>$this->resource->doctor->name.' '.$this->resource->doctor->surname,
                    // "avatar"=> $this->resource->doctor->avatar ? env("APP_URL")."storage/".$this->resource->doctor->avatar : null,
                    "avatar"=> $this->resource->doctor->avatar ? env("APP_URL").$this->resource->doctor->avatar : null,
                    "speciality_id" => $this->resource->doctor->speciality_id,
                            "speciality"=>$this->resource->doctor->speciality ? [
                                "id"=> $this->resource->doctor->speciality->id,
                                "name"=> $this->resource->doctor->speciality->name,
                                "price"=> $this->resource->doctor->speciality->price,
                            ]:NULL,
                ]: NULL,
            "patient_id" =>$this->resource->patient_id,
            "patient" =>$this->resource->patient ?
                [
                    "id" =>$this->resource->patient->id,
                    "name" =>$this->resource->patient->name,
                    "surname" =>$this->resource->patient->surname,
                    "full_name" =>$this->resource->patient->name.' '.$this->resource->patient->surname,
                    "phone" =>$this->resource->patient->phone,
                    "n_doc" =>$this->resource->patient->n_doc,
                    "antecedent_alerg" =>$this->resource->patient->antecedent_alerg,
                    "name_companion" =>$this->resource->patient->person->name_companion,
                    "surname_companion" =>$this->resource->patient->person->surname_companion,
                ]: NUll,
            "date_appointment" =>$this->resource->date_appointment,
            "date_appointment_format" =>Carbon::parse($this->resource->date_appointment)->format("Y-m-d"),
            "speciality_id" =>$this->resource->speciality_id,
            "speciality"=>$this->resource->speciality ? 
                [
                    "id"=> $this->resource->speciality->id,
                    "name"=> $this->resource->speciality->name,
                    "price"=> $this->resource->speciality->price,
                ]: NULL,
            "doctor_schedule_join_hour_id" =>$this->resource->doctor_schedule_join_hour_id,
            "segment_hour"=>$this->resource->doctor_schedule_join_hour ? 
                [
                    "id" => $this->resource->doctor_schedule_join_hour->id,
                    "doctor_schedule_day_id" => $this->resource->doctor_schedule_join_hour->doctor_schedule_day_id,
                    "doctor_schedule_hour_id" => $this->resource->doctor_schedule_join_hour->doctor_schedule_hour_id,
                    // "is_appointment"=> $appointment ? true : false,
                    "format_segment"=>[
                        "id" => $this->resource->doctor_schedule_join_hour->doctor_schedule_hour->id,
                        "hour_start" => $this->resource->doctor_schedule_join_hour->doctor_schedule_hour->hour_start,
                        "hour_end" => $this->resource->doctor_schedule_join_hour->doctor_schedule_hour->hour_end,
                        "format_hour_start" => Carbon::parse(date("Y-m-d").' '.$this->resource->doctor_schedule_join_hour->doctor_schedule_hour->hour_start)->format("h:i A") ,
                        "format_hour_end" => Carbon::parse(date("Y-m-d").' '.$this->resource->doctor_schedule_join_hour->doctor_schedule_hour->hour_end)->format("h:i A"),
                        "hour" => $this->resource->doctor_schedule_join_hour->doctor_schedule_hour->hour,
                    ],
                ]: NULL,
            "user_id" =>$this->resource->user_id,
            "user" => $this->resource->user ? [
                "id" => $this->resource->doctor->id,
                "full_name" => $this->resource->doctor->name. ' '.$this->resource->doctor->surname
            ]: NULL,
            "amount" =>$this->resource->amount,
            "status_pay" =>$this->resource->status_pay,
            // "deuda" =>$this->resource->deuda,
            "status" =>$this->resource->status,
            "laboratory" =>$this->resource->laboratory,
            "date_attention" =>$this->resource->date_attention,
            "confimation" =>$this->resource->confimation,
            
            "created_at"=>$this->resource->created_at ? Carbon::parse($this->resource->created_at)->format("Y-m-d h:i A") : NULL,
        ];
    }
}
