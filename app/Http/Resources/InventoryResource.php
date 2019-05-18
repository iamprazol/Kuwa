<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InventoryResource extends JsonResource
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
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->user->name,
            'email' => $this->user->email,
            'phone' => $this->user->phone,
            'company_name' => $this->user->company_name,
            'address' => $this->user->address,
            'total_items' => $this->total,
            'available' => $this->total - $this->sold,
            'unavailable' => $this->sold,
        ];
    }
}
