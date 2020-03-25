<?php

namespace App\Http\Resources;
use Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class DonationRecordResource extends JsonResource
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
            'type' => 'donation_record',
            'id' => (int)$this->id,
            'attributes' => [
                'user_id' => (int)$this->user_id,
                'donation_email' => (string)$this->donation_email,
                'donated_at' => Carbon::parse($this->donated_at),
                'donation_amount' => (int)$this->donation_amount,
                'show_amount' => (boolean)$this->show_amount,
                'is_anonymous' => (boolean)$this->is_anonymous,
                'donation_majia' => (string)$this->donation_majia,
                'donation_message' => (string)$this->donation_message,
                'donation_kind' => (string)$this->donation_kind,
                'is_claimed' => (boolean)$this->is_claimed,
            ]
        ];
    }
}
