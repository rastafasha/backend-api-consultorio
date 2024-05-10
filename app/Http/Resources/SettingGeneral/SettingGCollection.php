<?php

namespace App\Http\Resources\SettingGeneral;

use Illuminate\Http\Resources\Json\ResourceCollection;

class SettingGCollection extends ResourceCollection
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
            "data"=> SettingGResource::collection($this->collection)
        ];
    }
}
