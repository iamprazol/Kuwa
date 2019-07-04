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
            'phone_number' => $this->user->phone,
            'quantity' => $this->quantity,
            'address' => $this->address,
            'delivery_date' => $this->delivery_date(),
            'delivery_time' => $this->delivery_time(),
	        'company_name' => $this->user->company_name,
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

    public function delivery_date(){
        if($this->delivery_date == null){
            return null;
        } else {
            return Carbon::parse($this->delivery_date)->format('d/m/Y');
        }
    }

    public function delivery_time(){
        if($this->delivery_time == null){
            return null;
        } else {
            return $this->delivery_time;
        }
    }

}
