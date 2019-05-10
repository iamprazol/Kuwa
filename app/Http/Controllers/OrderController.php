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
            'address' => 'required|string|max:255|min:2',
            'delivery_date' => 'required|date|after:yesterday',
        ]);
        $order = Order::create([
            'user_id' => $user_id,
            'address' => $r->address,
            'quantity' => $r->quantity,
            'delivery_date' => $r->delivery_date,
            'delivery_time' => $r->delivery_time,
        ]);

        $this->sendNotification($r->device_token, 'Your order has been recorded and is waiting for it to be verified by the admin','Order Placed');
        $data = new OrderResource($order);
        return $this->responser($order, $data, 'Items Ordered Successfully');
    }

    public function orderList(){
        $orders = Order::latest()->where('status', '!=', 2)->get();
        $data = OrderResource::collection($orders);
        return $this->responser($orders, $data, 'Lastest Orders are listed');
    }

    public function verifyOrder(Request $r, $id){
        $order = Order::find($id);
        $order->status = 1;
        $order->save();

        $this->sendNotification($r->device_token, 'Your order has been verified by the admin and is one the way to be delivered','Order Pending');
        $data = new OrderResource($order);
        return $this->responser($order, $data, 'Order Has been successfully verified by the admin');
    }

    public function orderDelivered(Request $r, $id){
        $order = Order::find($id);
        $order->status = 2;
        $order->save();

        $user_id = $order->user_id;
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

        $this->sendNotification($r->device_token, 'Your order has been Delivered','Order Delivered');
        $data = new OrderResource($order);
        return $this->responser($order, $data, 'Order Has been successfully delivered to the customer');
    }
}
