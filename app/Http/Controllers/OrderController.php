<?php

namespace App\Http\Controllers;

use App\Order;
use App\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\OrderResource as OrderResource;

class OrderController extends Controller
{
    public function myOrder(){
        $order = Auth::user()->order->where('status', '!=', 2)->first();
        $data = new OrderResource($order);
        return $this->responser($order, $data, 'Orders Listed Successfully');
    }

    public function placeOrder(Request $r){
        $user_id = Auth::id();

        $this->validate($r, [
            'address' => 'string|max:255|min:2',
            'delivery_date' => 'required|date|after:yesterday',
        ]);
        $order = Order::create([
            'user_id' => $user_id,
            'address' => $r->address,
            'quantity' => $r->quantity,
            'delivery_date' => $r->delivery_date,
            'delivery_time' => $r->delivery_time,
        ]);

        $user = Inventory::where('user_id', $user_id)->first();
        if(!$user){
            Inventory::create([
                'user_id' => $user_id,
                'total' => $order->quantity,
            ]);
        } else {
            $remaining = $user->total - $user->sold;
            $user->total = $remaining + $order->quantity;
            $user->sold = 0;
            $user->save();
        }

        $this->sendNotification($r->device_token, 'Your order has been recorded and is waiting for it to be verified by the admin','Order Pending');
        $data = new OrderResource($order);
        return $this->responser($order, $data, 'Items Ordered Successfully');
    }
}
