<?php

namespace App\Http\Resources\RLaboratory;

use Illuminate\Http\Resources\Json\JsonResource;

class RLaboratoryResource extends JsonResource
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
            'id' => $this->resource->id,
            'patient_id' => $this->resource->patient_id,
            'comentario' => $this->resource->comentario,
            'name_file' => $this->resource->name_file,
            'size' => $this->resource->size,
            'resolution' => $this->resource->resolution,
            "file" => $this->resource->avatar ? (str_starts_with($this->resource->file, 'http') ? $this->resource->file : env("APP_URL") . $this->resource->file) : null,

            'type' => $this->resource->type,
        ];
    }
}
