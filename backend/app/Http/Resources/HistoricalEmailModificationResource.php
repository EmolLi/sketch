<?php

namespace App\Http\Resources;
use Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class HistoricalEmailModificationResource extends JsonResource
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
            'type' => 'email_modification',
            'id' => (int)$this->id,
            'attributes' => [
                'token' => (string)$this->token,
                'user_id' => (int)$this->user_id,
                'old_email' => (string)$this->old_email,
                'new_email' => (string)$this->new_email,
                'ip_address' => (string)$this->ip_address,
                'created_at' => Carbon::parse($this->created_at),
                'old_email_verified_at' => Carbon::parse($this->old_email_verified_at),
                'email_changed_at' => Carbon::parse($this->email_changed_at),
                'admin_revoked_at' => Carbon::parse($this->admin_revoked_at),
            ]
        ];
    }
}
