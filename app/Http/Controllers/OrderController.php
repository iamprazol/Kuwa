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
                return response()->json(['data' => $data, 'message' => 'Your Latest Order is listed', 'status' => 200], 200);
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
            'quantity' => 'integer|min:0',
            'delivery_date' => 'date|after:yesterday|before:'.$untilweek,
            'delivery_time' => 'string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'status' => 400], 400);
        }

        $today = Carbon::now()->today();
        $days = Carbon::parse($r->delivery_date)->diffInDays($today);

        if(isset($r->delivery_date)){
            if($days > 0){
                $delivery_date = $r->delivery_date;
                $delivery_time = null;
            } elseif($days == 0) {
                $delivery_date = null;
                if($now->format('H:i') <= Carbon::parse('5 pm')->format('H:i')){
                    $delivery_time = $r->delivery_time;
                } else {
                    return response()->json(['message' => 'Ordering time for today is over. Please select tomorrow\'s date for delivery', 'status' => 403], 403);

                }
            }
        } else {
            return response()->json(['message' => 'Please select a delivery date', 'status' => 403], 403);
        }


        $order = Order::where('user_id', $user->id)->where('status', 0)->where('delivery_date', $delivery_date)->first();


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
        $this->addNotification($order->user_id, $message, $title, $order->user->firebase_token);

        $newmessage = 'A new Order has been placed';
        $admins = User::where('admin', 1)->get();

        foreach ($admins as $admin) {
            $this->addNotification($admin->id, $newmessage, $title, $admin->firebase_token);
        }

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
                    $this->addNotification($order->user_id, $message, $title, $order->user->firebase_token);

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
            $this->addNotification($order->user_id, $message, $title, $order->user->firebase_token);

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
            if ($order->status != 2) {
                $order->status = 3;
                $order->save();

                $title = 'Order Rejected';
                $message = 'Your order has been rejected. Please contact us for more details.';
                $this->addNotification($order->user_id, $message, $title, $order->user->firebase_token);
                return response()->json(['message' => 'Order has been rejected Successfully', 'status' => '200'], 200);
            } else {
                return response()->json(['message' => 'Order has been already delivered', 'status' => '403'], 403);
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

    public function salesReport(){
        $yesterday = Carbon::yesterday()->toDateString();
        $today = Carbon::today()->toDateString();

        $admins = User::where('admin', 1)->get();
        foreach ($admins as $admin){
            $ids[] = $admin->id;
        }

        $totalSalesInTwoDays = Order::where('updated_at', '>=', $yesterday)->where('status' ,2)->get();
        $twoDaysSales = 0;
        foreach ($totalSalesInTwoDays as $two){
            $twoDaysSales  += $two->quantity;
        }

        $totalSalesYesterday = Order::where('updated_at', 'like', '%'.$yesterday.'%')->where('status', 2)->get();
        $yesterdaySales = 0;
        $yesterdayLog = [];
        foreach ($totalSalesYesterday as $two){
            $yesterdaySales  += $two->quantity;
            $yesterdayLog[] = [
                'user_name' => $two->user->name,
                'jars_sold' => $two->quantity
            ];
        }

        $totalSalesToday = Order::where('updated_at', 'like', '%'.$today.'%')->where('status' ,2)->get();
        $todaySales = 0;
        $todayLog = [];
        foreach ($totalSalesToday as $two){
            $todaySales  += $two->quantity;
            $todayLog[] = [
                'user_name' => $two->user->name,
                'phone' => $two->user->phone,
                'address' => $two->user->address,
                'jars_sold' => $two->quantity
            ];
        }

        $data = [
            'total_sales_in_2_days' => $twoDaysSales,
            'total_sales_today_upto_now' => $todaySales,
            'today_log' => array_filter($todayLog),
            'total_sales_yesterday' => $yesterdaySales,
            'yesterday_log' => array_filter($yesterdayLog)
        ];

        if($data != null){
            return response()->json(['data' => $data, 'message' => 'Report is Listed', 'status' => 200], 200);
        } else {
            return response()->json(['message' => 'Report not found', 'status' => 404], 404);
        }

    }

}
