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
            "id"=>$this->resource->id,
            "name"=>$this->resource->name,
            "surname"=>$this->resource->surname,
            "full_name"=> $this->resource->name.' '.$this->resource->surname,
            "email"=>$this->resource->email,
            "n_doc"=>$this->resource->n_doc,
            
            "phone"=>$this->resource->phone,
            // "avatar"=> $this->resource->avatar ? env("APP_URL")."storage/".$this->resource->avatar : null,
            "avatar"=> $this->resource->avatar ? env("APP_URL").$this->resource->avatar : null,
            "birth_date"=>$this->resource->birth_date ? Carbon::parse($this->resource->birth_date)->format("Y/m/d") : NULL,
            "gender"=>$this->resource->gender,
            "education"=>$this->resource->education,
            "address"=>$this->resource->address,
            "antecedent_family"=>$this->resource->antecedent_family,
            "antecedent_personal"=>$this->resource->antecedent_personal,
            "antecedent_alerg"=>$this->resource->antecedent_alerg,
            "ta"=>$this->resource->ta,
            "temperature"=>$this->resource->temperature,
            "location_id"=>$this->resource->location_id,
            // "location_id"=>$this->resource->location_id,
            //     "location"=>$this->resource->location ? [
            //         "title"=>$this->resource->location->title,
            //         "address"=>$this->resource->location->address,
            //         "email"=>$this->resource->location->email,
                    
            //     ]:NULL,
            "fc"=>$this->resource->fc,
            "fr"=>$this->resource->fr,
            "peso"=>$this->resource->peso,
            "current_desease"=>$this->resource->current_desease,
            // "avatar"=> $this->resource->avatar ? env("APP_URL")."storage/".$this->resource->avatar : null,
            "avatar"=> $this->resource->avatar ? env("APP_URL").$this->resource->avatar : null,
            "created_at"=>$this->resource->created_at ? Carbon::parse($this->resource->created_at)->format("Y-m-d h:i A") : NULL,
            
            "person"=>$this->resource->person ? [
                "id"=>$this->resource->person->id,
                "patient_id"=>$this->resource->person->patient_id,
                "name_companion"=>$this->resource->person->name_companion,
                "surname_companion"=>$this->resource->person->surname_companion,
                "mobile_companion"=>$this->resource->person->mobile_companion,
                "relationship_companion"=>$this->resource->person->relationship_companion,
                "name_responsable"=>$this->resource->person->name_responsable,
                "surname_responsable"=>$this->resource->person->surname_responsable,
                "mobile_responsable"=>$this->resource->person->mobile_responsable,
                "relationship_responsable"=>$this->resource->person->relationship_responsable,
            ]:NULL,
            "doctor_id"=>$this->resource->doctor_id,
            "doctor"=>$this->resource->doctor ? [
                "name"=>$this->resource->doctor->name,
                "surname"=>$this->resource->doctor->surname,
                "email"=>$this->resource->doctor->email,
                "address"=>$this->resource->doctor->address,
                "mobile"=>$this->resource->doctor->mobile,
                

            ]:NULL,
            "payments"=>$this->resource->payments ? [
                "id"=>$this->resource->payments->id,
            ]:NULL,

        ];
    }
}
