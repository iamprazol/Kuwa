<?php

namespace App\Http\Resources;

use App\Order;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'quantity' => $this->quantity,
            'address' => $this->address,
            'delivery_date' => $this->delivery_date,
            'delivery_time' => $this->delivery_time,
            'status' => self::status()
        ];
    }

    public function status(){
        $status = $this->status;
        if($status == 0){
            return 'pending';
        } elseif($status == 1){
            return 'Dispatched';
        } else {
            return 'delivered';
        }
    }

}
