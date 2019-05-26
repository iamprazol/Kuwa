<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\User;
use App\Http\Resources\UserResource as UserResource;

class UserController extends Controller
{
    public function authenticate(Request $request)
    {
        $credentials = $request->only('phone', 'password');
        $user = User::where('phone', $request->phone)->first();
            try {
                if (!$token = JWTAuth::attempt($credentials)) {
                    return response()->json(['error' => 'invalid_credentials', 'status' => 400], 400);
                } elseif ($user->is_verified == 0){
                    return response()->json(['error' => 'User is not verified', 'status' => 401], 401);
                }
            } catch (JWTException $e) {
                return response()->json(['error' => 'could_not_create_token', 'status' => 500 ], 500);
            }
            $data = new UserResource($user);
            return response()->json(['data' => $data,'token' => $token, 'message' => 'Users details listed successfully', 'status' => 200], 200);
    }

    public function regi(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|min:2',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'required|regex:/^\+?(977)?(98)[0-9]{8}?/|max:14|unique:users',
            'firebase_token' => 'required'
        ]);

        if($validator->fails()){
            return response()->json(['errors' => $validator->errors(), 'status' => 400], 400);
        }

        $user = User::create([
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'password' => Hash::make($request->get('password')),
            'phone' => $request->phone,
            'address' => $request->address,
            'company_name' => $request->company_name,
            'firebase_token' => $request->firebase_token
        ]);
        $this->getOtp($user);

        $token = JWTAuth::fromUser($user);
        $data = new UserResource($user);
        return response()->json(['data' => $data, 'token' => $token, 'message' => 'User has been created successfully', 'status' => '201'],201);
    }

    public function verifyUser(Request $request, $id){
        $code = $request->code;
        $user = User::find($id);

        $updated_at = Carbon::parse($user->updated_at)->addMinutes(5)->format('H:i');
        $now = Carbon::now()->format('H:i');

        if($now <= $updated_at) {
            if ($code == $user->code) {
                $user->is_verified = 1;
                $user->code = null;
                $user->save();

                $data = new UserResource($user);
                return response()->json(['data' => $data, 'message' => 'User has been verified successfully', 'status' => '200'], 200);

            } else {
                return response()->json(['message' => 'verification code doesn\'t match', 'status' => 400], 400);
            }
        } else {
            $user->code = null;
            $user->save();
            return response()->json(['message' => 'verification code has Expired', 'status' => 400], 400);
        }
    }

    public function resendVerification($id){
        $user = User::find($id);
        if($user != null) {
            $this->getOtp($user);
            return response()->json(['message' => 'A verification code has been sent to user', 'status' => 200], 200);
        } else {
            return response()->json(['message' => 'No user found', 'status' => 400], 400);
        }
    }

    public function passwordResetRequest(Request $request){
        $user = User::where('email', $request->email)->where('phone', $request->phone)->first();
        if($user != null){
            $this->getOtp($user);
            return response()->json(['message' => 'A verification code has been sent to user', 'status' => 200], 200);
        } else {
            return response()->json(['message' => 'No user found', 'status' => 400], 400);
        }
    }

    public function changePassword(Request $request, $id){
        $code = $request->code;
        $user = User::find($id);
        $validator = Validator::make($request->all(), [
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        if($validator->fails()){
            return response()->json(['errors' => $validator->errors(), 'status' => 400], 400);
        }

        $updated_at = Carbon::parse($user->updated_at)->addMinutes(3)->format('H:i');
        $now = Carbon::now()->format('H:i');

        if($now <= $updated_at) {
            if ($code == $user->code) {
                $user->password = bcrypt($request->new_password);
                $user->code = null;
                $user->save();

                $data = new UserResource($user);
                return response()->json(['data' => $data, 'message' => 'User password has been changed successfully', 'status' => '200'], 200);
            } else {
                return response()->json(['message' => 'verification code doesn\'t match', 'status' => 400], 400);
            }
        } else {
            $user->code = null;
            $user->save();
            return response()->json(['message' => 'verification code has Expired', 'status' => 400], 400);
        }
    }

    public function getAuthenticatedUser()
    {
        $user = Auth::user();
        $data = new UserResource($user);
        return $this->responser($user, $data, 'User details listed successfully');
    }

    public function searchCustomer(Request $r){
        $user = User::where('admin', 0)->where('name', 'like', '%'.$r->name.'%')->get();
        $data = UserResource::collection($user);
        return $this->responser($user, $data, 'Users found successfully');
    }

    public function update(Request $r){
	 $validator = Validator::make($r->all(), [
            'name' => 'string|max:255|min:2',
            'email' => 'string|email|max:255|unique:users',
            'password' => 'string|min:6|confirmed',
            'phone' => 'regex:/^\+?(977)?(98)[0-9]{8}?/|max:14|unique:users',
        ]);

        if($validator->fails()){
            return response()->json(['errors' => $validator->errors(), 'status' => 400], 400);
        }    
	
	$user = Auth::user();
        $user->update($r->all());

        $data = new UserResource($user);
        return $this->responser($user, $data, "Your details has been updated successfully");
    }

    public function customerList(){
        $customers = User::where('admin', 0)->orderBy('name', 'asc')->get();
        $data = UserResource::collection($customers);
        return $this->responser($customers, $data, 'Customers Listed Successfully');
    }

    public function pendingCustomer(){
        $pending = User::where(['admin'=> 0, 'is_verified' => 0])->get();
        $data = UserResource::collection($pending);
        return $this->responser($pending, $data, 'Customers Pending To Be Verified Listed Successfully');
    }

    public function logout(Request $request)
    {
        try {
            JWTAuth::invalidate($request->header('Authorization'));
            return response()->json(['success' => true, 'message' => "You have successfully logged out."]);
        } catch (JWTException $e) {
            // something went wrong whilst attempting to encode the token
            return response()->json(['success' => false, 'error' => 'Failed to logout, please try again.'], 500);
        }
    }


}
