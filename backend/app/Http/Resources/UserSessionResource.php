<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserSessionResource extends JsonResource
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
            'type' => 'user_session',
            'id' => (int)$this->id,
            'attributes' => [
                'user_id' => (int)$this->user_id,
                'created_at' => (string)$this->created_at,
                'session_count' => (int)$this->session_count,
                'ip_count' => (int)$this->ip_count,
                'ip_band_count' => (int)$this->ip_band_count,
                'device_count' => (int)$this->device_count,
                'mobile_count' => (int)$this->mobile_count,
                'session_data' => (string)$this->session_data,
            ]
        ];
    }
}
