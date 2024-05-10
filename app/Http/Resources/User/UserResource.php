<?php

namespace App\Http\Resources\User;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request): array
    {
        $HOUR_SCHEDULES = collect([]);
        $days_week = [];
        $days_week["Lunes"] = "table-primary";
        $days_week["Martes"] = "table-secondary";
        $days_week["Miercoles"] = "table-success";
        $days_week["Jueves"] = "table-warning";
        $days_week["Viernes"] = "table-info";
        $days_name = "";
        foreach ($this->resource->schedule_days as $key => $schedule_day) {
            $days_name .= ($schedule_day->day."-");
            foreach ($schedule_day->schedule_hours as $key => $schedule_hour) {
                $HOUR_SCHEDULES->push([
                    "day"=> [
                        "day"=> $schedule_day->day,
                        "class"=> $days_week[$schedule_day->day],
                    ],
                    "day_name"=> $schedule_day->day,
                    "hours_day"=> [
                        "hour" => $schedule_hour->doctor_schedule_hour->hour,
                        "format_hour" => Carbon::parse(date("Y-m-d").' '.$schedule_hour->doctor_schedule_hour->hour.":00:00")->format("h:i A"),
                        "items" => []
                    ],
                    "hour"=> $schedule_hour->doctor_schedule_hour->hour,
                    "grupo"=> "all",
                    "item"=> [
                        "id" => $schedule_hour->doctor_schedule_hour->id,
                        "hour_start" => $schedule_hour->doctor_schedule_hour->hour_start,
                        "hour_end" => $schedule_hour->doctor_schedule_hour->hour_end,
                        "format_hour_start" => Carbon::parse(date("Y-m-d").' '.$schedule_hour->doctor_schedule_hour->hour_start)->format("h:i A") ,
                        "format_hour_end" => Carbon::parse(date("Y-m-d").' '.$schedule_hour->doctor_schedule_hour->hour_end)->format("h:i A"),
                        "hour" => $schedule_hour->doctor_schedule_hour->hour,
                    ],
                ]);
            }
        }

        return [
            "id"=>$this->resource->id,
            "name"=>$this->resource->name,
            "surname"=>$this->resource->surname,
            "n_doc"=>$this->resource->n_doc,
            "full_name"=> $this->resource->name.' '.$this->resource->surname,
            "email"=>$this->resource->email,
            "password"=>$this->resource->password,
            "rolename"=>$this->resource->rolename,
            "mobile"=>$this->resource->mobile,
            "birth_date"=>$this->resource->birth_date ? Carbon::parse($this->resource->birth_date)->format("Y/m/d") : NULL,
            "gender"=>$this->resource->gender,
            "education"=>$this->resource->education,
            "location_id"=>$this->resource->location_id,
            "designation"=>$this->resource->designation,
            "address"=>$this->resource->address,
            // "avatar"=> $this->resource->avatar ? env("APP_URL")."storage/".$this->resource->avatar : null,
            "avatar"=> $this->resource->avatar ? env("APP_URL").$this->resource->avatar : null,
            "roles"=>$this->resource->roles->first(),
            "speciality_id" => $this->resource->speciality_id,
            "speciality"=>$this->resource->speciality ? [
                "id"=> $this->resource->speciality->id,
                "name"=> $this->resource->speciality->name,
            ]:NULL,
            "created_at"=>$this->resource->created_at ? Carbon::parse($this->resource->created_at)->format("Y/m/d") : NULL,
            "schedule_selecteds"=> $HOUR_SCHEDULES,
            "days_name"=> $days_name,
        ];
    }
}
