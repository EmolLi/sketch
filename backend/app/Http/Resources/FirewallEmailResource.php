<?php

namespace App\Http\Resources;
use Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class FirewallEmailResource extends JsonResource
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
            'type' => 'firewall-email',
            'id' => (int)$this->id,
            'attributes' => [
                'email' => (string)$this->email,
            ]
        ];
    }
}
