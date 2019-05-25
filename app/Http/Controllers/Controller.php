<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Notification;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function responser($item, $data, $message)
    {
        if($item != null){
            $num = $item->count();
        } else {
            $num = 0;
        }

        if($num > 0){
            return response()->json([
                'data' => $data,
                'status' => 200,
                'message' => $message
            ], 200);
        } else {
            return response()->json([
                'data' => $item,
                'status' => 404,
                'message' => 'Item not found'
            ], 404);
        }
    }

    function sendNotification($devicetoken, $mesg, $title)
    {

        $registrationIds = $devicetoken;
        #prep the bundle
        $msg = array
        (
            "body" => $mesg,
            "title" => $title,
            "sound" => "mySound",

        );
        $fields = array
        (
            'to' => $registrationIds,
            'notification' => $msg,
            'priority' => 'high',
        );
        $headers = array
        (
            'Authorization: key= AAAAFAFMQ2o:APA91bGcXXPYFEay6P0UudTAqBVzcw0fPTuz3cw_gKC-r6wD2sTKahSIRKpOfV__vtbd-zcrFjyCMNQvhVc1rimAGeV-apJ-29jiujhMGxjmlNOlKQXzu8MTHCMdf_3QUVXBXt4h0phN',
            'Content-Type: application/json'
        );
        #Send Reponse To FireBase Server
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);
        curl_close($ch);
        $cur_message = json_decode($result);
        if ($cur_message->success == 1)
            return $result;
        else
            return $result;
    }

    public function addNotification($item, $message, $title){
        Notification::create([
            'user_id' => $item->user_id,
            'message' => $message,
            'title' => $title,
        ]);

        return $this->sendNotification($item->user->firebase_token, $message, $title);

    }


    public function getOtp($user){
        $code = rand(10000, 99999);
        $api_url = "http://api.sparrowsms.com/v2/sms/?".
            http_build_query(array(
                'token' => 'W9mxiMTWaRQUiMf7YQJA',
                'from'  => 'Demo',
                'to'    => $user->phone,
                'text'  => 'This is your verification code for activating kuwa account 
                            Code: '.$code));

        $curl_handle=curl_init();
        curl_setopt($curl_handle, CURLOPT_URL,$api_url);
        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_USERAGENT, 'Kuwa');
        $query = curl_exec($curl_handle);
        curl_close($curl_handle);

        $user->code = $code;
        $user->save();
    }

    public function sendOtp(Request $request){
        $phone = $request->phone;

        $args = http_build_query(array(
            'token' => 'xwu5onGreNPXp3dOCQJo',
            'from'  => 'Kuwa',
            'to'    => $phone,
            'text'  => 'This is your verification code'));

        $url = "http://api.sparrowsms.com/v2/sms/";

        # Make the call using API.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$args);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // Response
        $response = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    }
}
