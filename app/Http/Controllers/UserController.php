<?php

namespace App\Http\Controllers;

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
        $credentials = $request->only('email', 'password');
        $user = User::where('email', $request->email)->first();
            try {
                if (!$token = JWTAuth::attempt($credentials)) {
                    return response()->json(['error' => 'invalid_credentials', 'status' => 400], 400);
                } elseif ($user->is_verified == 0){
                    return response()->json(['error' => 'User is not verified', 'status' => 400], 400);
                }
            } catch (JWTException $e) {
                return response()->json(['error' => 'could_not_create_token', 'status' => 500 ], 500);
            }
            $data = new UserResource($user);
            return response()->json(['data' => $data,'token' => $token, 'message' => 'Users details listed successfully', 'status' => 200], 200);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|min:2',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'required|regex:/^\+?(977)?(98)[0-9]{8}?/|max:14',
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

        $token = JWTAuth::fromUser($user);
        $data = new UserResource($user);
        return response()->json(['data' => $data, 'token' => $token, 'message' => 'User has been created successfully', 'status' => '201'],201);
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
        $user = Auth::user();
        $user->update($r->all());

        $data = new UserResource($user);
        return $this->responser($user, $data, "Your details has been updated successfully");
    }

    public function customerList(){
        $customers = User::where('admin', 0)->orderBy('name', 'asc')->paginate(15);
        $data = UserResource::collection($customers);
        return $this->responser($customers, $data, 'Customers Listed Successfully');
    }

    public function pendingCustomer(){
        $pending = User::where(['admin'=> 0, 'is_verified' => 0])->paginate(15);
        $data = UserResource::collection($pending);
        return $this->responser($pending, $data, 'Customers Pending To Be Verified Listed Successfully');
    }

}
