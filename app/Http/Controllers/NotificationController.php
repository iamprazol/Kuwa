<?php

namespace App\Http\Controllers;

use App\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\NotificationResource;

class NotificationController extends Controller
{
    public function myNotification(){
        $notifications = Auth::user()->notification->sortByDesc('created_at');
        $data = NotificationResource::collection($notifications);
        return $this->responser($notifications, $data, 'User\'s all notification listed successfully');
    }

    public function readNotification($id){
        $notification = Notification::find($id);
        $notification->read_status = 1;
        $notification->save();

        $data = new NotificationResource($notification);
        return $this->responser($notification, $data, 'Notification with specific id has been read successfully');
    }
}
