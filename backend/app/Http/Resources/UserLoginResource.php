<?php

namespace App\Http\Resources;
use Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class UserLoginResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {

        return [
            'type' => 'user_login',
            'id' => (int)$this->id,
            'attributes' => [
                'user_id' => (int)$this->user_id,
                'created_at' => Carbon::parse($this->created_at),
                'ip' => (string)$this->ip,
                'device' => (string)$this->device,
            ]
        ];
    }
}
