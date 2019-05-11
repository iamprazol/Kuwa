<?php

namespace App\Http\Resources;

use App\Order;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

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
            'delivery_date' => Carbon::parse($this->delivery_date)->format('d/m/Y'),
            'delivery_time' => Carbon::parse($this->delivery_time)->format('H:i'),
            'status' => self::status()
        ];
    }

    public function status(){
        $status = $this->status;
        if($status == 0){
            return 'pending';
        } elseif($status == 1){
            return 'Dispatched';
        } elseif($status == 2) {
            return 'delivered';
        } else {
            return 'rejected';
        }
    }

}
