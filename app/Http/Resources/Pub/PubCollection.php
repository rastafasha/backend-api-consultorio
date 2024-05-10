<?php

namespace App\Http\Resources\Pub;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PubCollection extends ResourceCollection
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
            "data"=> PubResource::collection($this->collection)
        ];
    }
}
