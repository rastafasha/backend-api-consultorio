<?php

namespace App\Http\Resources\Location;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
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
            "title"=>$this->resource->title,
            "address"=>$this->resource->address,
            "phone1"=>$this->resource->phone1,
            "phone2"=>$this->resource->phone2,
            "email"=>$this->resource->email,
            "city"=>$this->resource->city,
            "state"=>$this->resource->state,
            "zip"=>$this->resource->zip,
            "avatar"=> $this->resource->avatar ? env("APP_URL")."storage/".$this->resource->avatar : null,
            // "avatar"=> $this->resource->avatar ? env("APP_URL").$this->resource->avatar : null,
            
            
            
            "created_at"=>$this->resource->created_at ? Carbon::parse($this->resource->created_at)->format("Y-m-d h:i A") : NULL,            

        ];
    }
}
