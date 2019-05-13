<?php

namespace App\Http\Controllers;

use App\Order;
use App\Inventory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\OrderResource as OrderResource;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function myOrder(){
        $user_id = Auth::id();
        $order = Order::where('user_id', $user_id)->latest()->first();

        if($order != null && $order->status != 2) {
            if ($order->status == 3) {
                return response()->json(['message' => 'Your order has been rejected', 'status' => '403'], 403);
            } else {
                $data = new OrderResource($order);
                return response()->json(['data' => $data, 'message' => 'Your Lastest Order is listed', 'status' => 200], 200);
            }
        }else {
            return response()->json(['message' => 'Orders not found', 'status' => 200],200);
        }
    }

    public function placeOrder(Request $r){
        $user = Auth::user();

        $validator = Validator::make($r->all(), [
            'delivery_date' => 'required|date|after:yesterday',
        ]);

        if($validator->fails()){
            return response()->json(['errors' => $validator->errors(), 'status' => 400], 400);
        }
        $now = Carbon::now();
        if($r->delivery_time == 'urgent'){
            $delivery_time = $now-> addMinutes(30);
        } elseif($r->delivery_time == "after_hour"){
            $delivery_time = $now->addHour();
        } else {
            $delivery_time = $now->addHours(5);
        }

        $order = Order::create([
            'user_id' => $user->id,
            'address' => $user->address,
            'quantity' => $r->quantity,
            'delivery_date' => $r->delivery_date,
            'delivery_time' => $delivery_time,
        ]);

        $this->sendNotification($user->firebase_token, 'Your order has been recorded and is waiting for it to be verified by the admin','Order Placed');
        $data = new OrderResource($order);
        return $this->responser($order, $data, 'Items Ordered Successfully');
    }

    public function orderList(){
        $orders = Order::latest()->where('status', '!=', 2)->where('status', '!=', 3)->get();
        $data = OrderResource::collection($orders);
        return $this->responser($orders, $data, 'Lastest Orders are listed');
    }

    public function verifyOrder(Request $r, $id){
        $order = Order::find($id);
        if($order->status != 3){
            $order->status = 1;
            $order->save();
            $this->sendNotification($order->user->firebase_token, 'Your order has been verified by the admin and is one the way to be delivered','Order Pending');
            $data = new OrderResource($order);
            return $this->responser($order, $data, 'Order Has been successfully verified by the admin');
        } else {
            return response()->json(['message' => 'Order has already been rejected', 'status' => 403], 403);
        }
    }

    public function orderDelivered(Request $r, $id){
        $order = Order::find($id);
        if($order->status != 3) {
            $order->status = 2;
            $order->save();

            $user_id = $order->user_id;
            $user = Inventory::where('user_id', $user_id)->first();
            if (!$user) {
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

            $this->sendNotification($order->user->firebase_token, 'Your order has been Delivered', 'Order Delivered');
            $data = new OrderResource($order);
            return $this->responser($order, $data, 'Order Has been successfully delivered to the customer');

        } else {
            return response()->json(['message' => 'Order has already been rejected', 'status' => 403], 403);
        }
    }

    public function rejectOrder(Request $r, $id){
        $order = Order::find($id);
        $order->status = 3;
        $order->save();

        $this->sendNotification($order->user->firebase_token, 'Your order has been rejected. Please contact us for more details.','Order Rejected');
        return response()->json(['message' => 'Order has been rejected Successfully', 'status' => '200'],200);
    }
}
