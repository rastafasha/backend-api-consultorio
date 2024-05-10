<?php

namespace App\Http\Resources\Laboratory;

use Illuminate\Http\Resources\Json\ResourceCollection;

class LaboratoryCollection extends ResourceCollection
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
            "data"=> LaboratoryResource::collection($this->collection)
        ];
    }
}
