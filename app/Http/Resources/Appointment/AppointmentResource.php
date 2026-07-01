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
            "id" => $this->resource->id,
            "doctor_id" => $this->resource->doctor_id,
            "doctor" => $this->resource->doctor ? [
                "id" => $this->resource->doctor->id,
                "address" => $this->resource->doctor->address,
                "email" => $this->resource->doctor->email,
                "mobile" => $this->resource->doctor->mobile,
                "precio_cita" => $this->resource->doctor->precio_cita,
                "full_name" => $this->resource->doctor->name . ' ' . $this->resource->doctor->surname,
                "avatar" => $this->resource->doctor->avatar ? env("APP_URL") . $this->resource->doctor->avatar : null,
                "speciality" => $this->resource->doctor->speciality ? [
                    "id" => $this->resource->doctor->speciality->id,
                    "name" => $this->resource->doctor->speciality->name,
                ] : null,
            ] : null,

            "patient_id" => $this->resource->patient_id,
            "patient" => $this->resource->patient ? [
                "id" => $this->resource->patient->id,
                "name" => $this->resource->patient->name,
                "surname" => $this->resource->patient->surname,
                "full_name" => $this->resource->patient->name . ' ' . $this->resource->patient->surname,
                "phone" => $this->resource->patient->phone,
                "n_doc" => $this->resource->patient->n_doc,
                "antecedent_alerg" => $this->resource->patient->antecedent_alerg,
                // Agregamos comprobación para evitar error si no hay acompañante
                "name_companion" => $this->resource->patient->person ? $this->resource->patient->person->name_companion : null,
                "surname_companion" => $this->resource->patient->person ? $this->resource->patient->person->surname_companion : null,
            ] : null,

            "date_appointment" => $this->resource->date_appointment,
            "date_appointment_format" => $this->resource->date_appointment ? Carbon::parse($this->resource->date_appointment)->format("Y-m-d") : null,
           
            "doctor_schedule_join_hour_id" => $this->resource->doctor_schedule_join_hour_id,
            "segment_hour" => $this->resource->doctor_schedule_join_hour ? [
               "id" => $this->resource->doctor_schedule_join_hour->id,
                "doctor_schedule_day_id" => $this->resource->doctor_schedule_join_hour->doctor_schedule_day_id,
                "doctor_schedule_hour_id" => $this->resource->doctor_schedule_join_hour->doctor_schedule_hour_id,
                "format_segment" => $this->resource->doctor_schedule_join_hour->doctor_schedule_hour ? [
                    "id" => $this->resource->doctor_schedule_join_hour->doctor_schedule_hour->id,
                    "hour_start" => $this->resource->doctor_schedule_join_hour->doctor_schedule_hour->hour_start,
                    "hour_end" => $this->resource->doctor_schedule_join_hour->doctor_schedule_hour->hour_end,
                    "format_hour_start" => Carbon::parse($this->resource->doctor_schedule_join_hour->doctor_schedule_hour->hour_start)->format("h:i A"),
                    "format_hour_end" => Carbon::parse($this->resource->doctor_schedule_join_hour->doctor_schedule_hour->hour_end)->format("h:i A"),
                    "hour" => $this->resource->doctor_schedule_join_hour->doctor_schedule_hour->hour,
                ] : NULL,
            ] : NULL,

            // CORRECCIÓN EN USER: Tenías $this->resource->doctor->id, debe ser el usuario logueado
            "user_id" => $this->resource->user_id,
            "user" => $this->resource->user ? [
                "id" => $this->resource->user->id,
                "full_name" => $this->resource->user->name . ' ' . $this->resource->user->surname,
                "email" => $this->resource->user->email,
            ] : null,

            // INYECTAMOS EL CONSULTORIO DEL DÍA DE LA CITA
            "consultorio" => ($this->doctor_schedule_join_hour && 
                              $this->doctor_schedule_join_hour->doctor_schedule_day && 
                              $this->doctor_schedule_join_hour->doctor_schedule_day->doctor_address) 
                ? [
                    "id" => $this->doctor_schedule_join_hour->doctor_schedule_day->doctor_address->id,
                    "name_consultorio" => $this->doctor_schedule_join_hour->doctor_schedule_day->doctor_address->name_consultorio,
                    "address" => $this->doctor_schedule_join_hour->doctor_schedule_day->doctor_address->address,
                    "is_active" => $this->doctor_schedule_join_hour->doctor_schedule_day->doctor_address->is_active,
                ] 
                : null,

            "amount" => $this->resource->amount,
            "status_pay" => $this->resource->status_pay,
            // "deuda" =>$this->resource->deuda,
            "status" => $this->resource->status,
            "laboratory" => $this->resource->laboratory,
            "date_attention" => $this->resource->date_attention,
            "confimation" => $this->resource->confimation,


            "created_at" => $this->resource->created_at ? Carbon::parse($this->resource->created_at)->format("Y-m-d h:i A") : null,
        ];


    }
}