<?php

namespace App\Http\Resources\Patient;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
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
            "mongo_user_id" => $this->resource->mongo_user_id,
            "name" => $this->resource->name,
            "surname" => $this->resource->surname,
            "full_name" => $this->resource->name . ' ' . $this->resource->surname,
            "email" => $this->resource->email,
            "n_doc" => $this->resource->n_doc,

            "phone" => $this->resource->phone,
            "birth_date" => $this->resource->birth_date ? Carbon::parse($this->resource->birth_date)->format("Y/m/d") : NULL,
            "gender" => $this->resource->gender,
            "education" => $this->resource->education,
            "address" => $this->resource->address,
            "antecedent_family" => $this->resource->antecedent_family,
            "antecedent_personal" => $this->resource->antecedent_personal,
            "antecedent_alerg" => $this->resource->antecedent_alerg,
            "ta" => $this->resource->ta,
            "temperature" => $this->resource->temperature,
            "location_id" => $this->resource->location_id,
            // "location_id"=>$this->resource->location_id,
            //     "location"=>$this->resource->location ? [
            //         "title"=>$this->resource->location->title,
            //         "address"=>$this->resource->location->address,
            //         "email"=>$this->resource->location->email,

            //     ]:NULL,
            "fc" => $this->resource->fc,
            "fr" => $this->resource->fr,
            "peso" => $this->resource->peso,
            "talla" => $this->resource->talla,
            "historia_enfermedad" => $this->resource->historia_enfermedad,
            "enfermedad_actual" => $this->resource->enfermedad_actual,
            "tratamiento" => $this->resource->tratamiento,
            "examen_fisico" => $this->resource->examen_fisico,
            "reporte_laboratorio" => $this->resource->reporte_laboratorio,
            "evolucion" => $this->resource->evolucion ? json_decode($this->resource->evolucion) : NULL,
            "vacunas" => $this->resource->vacunas ? json_decode($this->resource->vacunas) : NULL,
            "peso_al_nacer" => $this->resource->peso_al_nacer,
            "talla_al_nacer" => $this->resource->talla_al_nacer,
            "current_desease" => $this->resource->current_desease,
            // "avatar"=> $this->resource->avatar ? env("APP_URL")."storage/".$this->resource->avatar : null,
            // "avatar" => $this->resource->avatar ? env("APP_URL") . $this->resource->avatar : null,
            "avatar" => $this->resource->avatar  ? (str_starts_with($this->resource->avatar, 'http') ? $this->resource->avatar : env("APP_URL") . $this->resource->avatar) : null,
            "created_at" => $this->resource->created_at ? Carbon::parse($this->resource->created_at)->format("Y-m-d h:i A") : NULL,

            "person" => $this->resource->person ? [
                "id" => $this->resource->person->id,
                "patient_id" => $this->resource->person->patient_id,
                "name_companion" => $this->resource->person->name_companion,
                "surname_companion" => $this->resource->person->surname_companion,
                "mobile_companion" => $this->resource->person->mobile_companion,
                "relationship_companion" => $this->resource->person->relationship_companion,
                "name_responsable" => $this->resource->person->name_responsable,
                "surname_responsable" => $this->resource->person->surname_responsable,
                "mobile_responsable" => $this->resource->person->mobile_responsable,
                "relationship_responsable" => $this->resource->person->relationship_responsable,
            ] : NULL,
            "doctor_id" => $this->resource->doctors->first()?->id,
            "doctor" => $this->resource->doctors->first() ? [
                "id" => $this->resource->doctors->first()->id,
                "full_name" => $this->resource->doctors->first()->name . ' ' . $this->resource->doctors->first()->surname,
            ] : null,
            "doctors" => $this->resource->appointments ? $this->resource->appointments->map(function ($appointment) {
                // IMPORTANTE: Aquí usamos $appointment->doctor porque es la relación de la cita
                return [
                    "doctor_id" => $appointment->doctor->id,
                    "full_name" => $appointment->doctor->name . ' ' . $appointment->doctor->surname,
                    "name" => $appointment->doctor->name,
                    "surname" => $appointment->doctor->surname,
                    "email" => $appointment->doctor->email,
                    "address" => $appointment->doctor->address,
                    "mobile" => $appointment->doctor->mobile,
                    "speciality" => $appointment->speciality->name ?? null,
                    "last_appointment" => $appointment->date_appointment,
                ];
            })->unique('doctor_id')->values() : [], // Quitamos duplicados si ha tenido varias citas con el mismo
            "payments" => $this->resource->payments ? [
                "id" => $this->resource->payments->id,
            ] : NULL,

        ];
    }
}
