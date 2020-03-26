<?php

namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;

class HistoricalPasswordResetResource extends JsonResource
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
            'type' => 'password_reset',
            'id' => (int)$this->id,
            'attributes' => [
                'user_id' => (int)$this->user_id,
                'ip_address' => (string)$this->ip_address,
                'created_at' => (string)$this->created_at,
                'old_password' => (string)$this->old_password,
                'admin_reset' => (string)$this->admin_reset,
            ]
        ];
    }
}
