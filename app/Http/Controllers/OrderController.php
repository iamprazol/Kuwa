<?php

namespace App\Http\Controllers;

use App\Order;
use App\Inventory;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\OrderResource as OrderResource;
use Illuminate\Support\Facades\Validator;
use App\Notification;

class OrderController extends Controller
{
    public function myOrder()
    {
        $user_id = Auth::id();
        $order = Order::where('user_id', $user_id)->get();

        if ($order != null && $order->status != 2) {
            if ($order->status == 3) {
                return response()->json(['message' => 'Your order has been rejected', 'status' => '403'], 403);
            } else {
                $data = new OrderResource($order);
                return response()->json(['data' => $data, 'message' => 'Your Lastest Order is listed', 'status' => 200], 200);
            }
        } else {
            return response()->json(['message' => 'Orders not found', 'status' => 200], 200);
        }
    }

    public function placeOrder(Request $r)
    {
        $user = Auth::user();
        $now = Carbon::now();
        $untilweek = Carbon::now()->addWeek();

        $validator = Validator::make($r->all(), [
            'delivery_date' => 'date|after:yesterday|before:'.$untilweek,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'status' => 400], 400);
        }

        $today = Carbon::now()->today()->toDateString();

        if(isset($r->delivery_date)){
            if($r->delivery_date > $today){
                $delivery_date = $r->delivery_date;
                $delivery_time = null;
            } else {
                return response()->json(['message' => 'You have to choose a time for delivery at current date', 'status' => 403], 403);
            }
        } else {
            $delivery_date = null;
            if($now->format('H:i') <= Carbon::parse('5 pm')->format('H:i')) {
                if ($r->delivery_time == "urgent") {
                    $delivery_time = $now->addMinutes(30);
                } elseif ($r->delivery_time == "after_hour") {
                    $delivery_time = Carbon::parse('1 pm')->format('H:i');
                } elseif ($r->delivery_time == "after_hours") {
                    $delivery_time = Carbon::parse('5 pm')->format('H:i');
                }
            } else {
                return response()->json(['message' => 'Ordering time for today is over. Please select tomorrow\'s date for delivery', 'status' => 403], 403);
            }
        }

        $order = Order::where('user_id', $user->id)->where('status', 0)->first();

        if($order != null){
            $order->quantity = $order->quantity + $r->quantity;
            $order->save();
        } else {
            $order = Order::create([
                'user_id' => $user->id,
                'address' => $user->address,
                'quantity' => $r->quantity,
                'delivery_date' => $delivery_date,
                'delivery_time' => $delivery_time,
            ]);
        }

        $title = 'Order Placed';
        $message = 'Your order has been recorded and is waiting for it to be verified by the admin';
        $this->addNotification($order, $message, $title);

        $data = new OrderResource($order);
        return $this->responser($order, $data, 'Items Ordered Successfully');
    }

    public function orderList()
    {
        $orders = Order::latest()->get();
        $data = OrderResource::collection($orders);
        return $this->responser($orders, $data, 'Latest Orders are listed');
    }

    public function verifyOrder(Request $r, $id)
    {
        $inventory = Auth::user()->inventory->first();
        if($inventory) {
            $remaining = $inventory->total - $inventory->sold;
            $order = Order::find($id);
            if ($order->quantity < $remaining) {
                if ($order->status == 0) {
                    $order->status = 1;
                    $order->save();

                    $inventory->sold = $inventory->sold + $order->quantity;
                    $inventory->save();

                    $title = 'Order ready for dispatch';
                    $message = 'Your order has been verified by the admin and is one the way to be delivered';
                    $this->addNotification($order, $message, $title);

                    $data = new OrderResource($order);
                    return $this->responser($order, $data, 'Order Has been successfully verified by the admin');
                } else {
                    return response()->json(['message' => 'Order has already been either verified or rejected', 'status' => 403], 403);
                }
            } else {
                return response()->json(['message' => 'You don\'t have enough item in the inventory to verify this order', 'status' => 403], 403);
            }
        } else {
            return response()->json(['message' => 'You don\'t have an inventory. So first create an inventory and add items in it', 'status' => 403], 403);
        }
    }

    public function orderDelivered(Request $r, $id)
    {
        $order = Order::find($id);
        if ($order->status == 1) {
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

            $title = 'Order Delivered';
            $message = 'Your order has been Delivered';
            $this->addNotification($order, $message, $title);

            $data = new OrderResource($order);
            return $this->responser($order, $data, 'Order Has been successfully delivered to the customer');

        } elseif($order->status == 2){
            return response()->json(['message' => 'Order has already been delivered', 'status' => 403], 403);
        } else {
            return response()->json(['message' => 'Order has not been verified yet', 'status' => 403], 403);
        }
    }

    public function rejectOrder(Request $r, $id)
    {
        $order = Order::find($id);
        if($order) {
            if ($order->status == 0) {
                $order->status = 3;
                $order->save();

                $title = 'Order Rejected';
                $message = 'Your order has been rejected. Please contact us for more details.';
                $this->addNotification($order, $message, $title);
                return response()->json(['message' => 'Order has been rejected Successfully', 'status' => '200'], 200);
            } else {
                return response()->json(['message' => 'Order has been verified already and cannot be rejected', 'status' => '403'], 403);
            }
        } else {
                return response()->json(['message' => 'No Order found', 'status' => 400], 400);
        }
    }

    public function rejectedList(){
        $orders = Order::latest()->where('status', 3)->get();
        $data = OrderResource::collection($orders);
        return $this->responser($orders, $data, 'Rejected Orders are listed');
    }

}
