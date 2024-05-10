<?php

namespace App\Http\Resources\Appointment\Pay;

use Illuminate\Http\Resources\Json\ResourceCollection;

class AppointmentPayCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            "data"=> AppointmentPayResource::collection($this->collection)
        ];
    }
}
