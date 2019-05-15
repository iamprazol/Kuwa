<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\NotificationResource;

class NotificationController extends Controller
{
    public function myNotification(){
        $notifications = Auth::user()->notification->where('read_status', 0);
        foreach ($notifications as $notification){
            $notification->read_status = 1;
            $notification->save();
        }
        $data = NotificationResource::collection($notifications);
        return $this->responser($notifications, $data, 'User\'s all notification listed successfully');
    }
}
