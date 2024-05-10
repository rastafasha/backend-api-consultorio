<?php

namespace App\Http\Resources\SettingGeneral;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingGResource extends JsonResource
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
            "name" =>$this->resource->name,
            "address" =>$this->resource->address,
            "city" =>$this->resource->city,
            "state" =>$this->resource->state,
            "zip" =>$this->resource->zip,
            "country" =>$this->resource->country,
            "phone" =>$this->resource->phone,
            "avatar"=> $this->resource->avatar ? env("APP_URL")."storage/".$this->resource->avatar : null,
            // "avatar"=> $this->resource->avatar ? env("APP_URL").$this->resource->avatar : null,
            "created_at"=>$this->resource->created_at ? Carbon::parse($this->resource->created_at)->format("Y-m-d h:i A") : NULL,
        ];
    }
}
