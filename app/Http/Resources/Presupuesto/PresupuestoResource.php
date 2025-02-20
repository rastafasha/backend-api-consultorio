<?php

namespace App\Http\Resources\Presupuesto;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class PresupuestoResource extends JsonResource
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
                    "email" =>$this->resource->patient->email,
                    "antecedent_alerg" =>$this->resource->patient->antecedent_alerg,
                    "name_companion" =>$this->resource->patient->person->name_companion,
                    "surname_companion" =>$this->resource->patient->person->surname_companion,
                ]: NUll,
            "date_presupuesto" =>$this->resource->date_presupuesto,
            "date_presupuesto_format" =>Carbon::parse($this->resource->date_presupuesto)->format("Y-m-d"),
            "speciality_id" =>$this->resource->speciality_id,
            "speciality"=>$this->resource->speciality ? 
                [
                    "id"=> $this->resource->speciality->id,
                    "name"=> $this->resource->speciality->name,
                    "price"=> $this->resource->speciality->price,
                ]: NULL,
            
            "user_id" =>$this->resource->user_id,
            "user" => $this->resource->user ? [
                "id" => $this->resource->doctor->id,
                "full_name" => $this->resource->doctor->name. ' '.$this->resource->doctor->surname
            ]: NULL,
            "amount" =>$this->resource->amount,
            // "deuda" =>$this->resource->deuda,
            "status" =>$this->resource->status,
            "laboratory" =>$this->resource->laboratory,
            "confimation" =>$this->resource->confimation,
            "description" =>$this->resource->description,
            
            "created_at"=>$this->resource->created_at ? Carbon::parse($this->resource->created_at)->format("Y-m-d h:i A") : NULL,
        ];
    }
}
