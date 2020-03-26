<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AdministrationResource extends JsonResource
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
            'type' => 'administration',
            'id' => (int)$this->id,
            'attributes' => [
                'user_id' => (int)$this->user_id,
                'task' => (string)$this->task,
                'reason' => (string)$this->reason,
                'record' => (string)$this->record,
                'created_at' => (string)$this->created_at,
                'deleted_at' => (string)$this->deleted_at,
                'administratee_id' => (int)$this->administratee_id,
                'administratable_type' => (string)$this->administratable_type,
                'administratable_id' => (int)$this->administratable_id,
                'is_public' => (boolean)$this->is_public,
                'summary' => (string)$this->summary,
                'report_post_id' => (int)$this->report_post_id,
            ],
        ];
    }
}
