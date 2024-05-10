<?php

namespace App\Http\Resources\Appointment\Payment;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
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
            "referencia" =>$this->resource->referencia,
            "metodo" =>$this->resource->metodo,
            "bank_name" =>$this->resource->bank_name,
            "monto" =>$this->resource->monto,
            "nombre" =>$this->resource->nombre,
            "email" =>$this->resource->email,
            "appointment_id" =>$this->resource->appointment_id,
            "status" =>$this->resource->status,
            "fecha" =>$this->resource->fecha,
            "patient_id" =>$this->resource->patient_id,
            "doctor_id" =>$this->resource->doctor_id,
            "created_at"=>$this->resource->created_at ? Carbon::parse($this->resource->created_at)->format("Y-m-d h:i A") : NULL,
        ];
    }
}
