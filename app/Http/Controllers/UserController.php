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

        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'invalid_credentials'], 400);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'could_not_create_token'], 500);
        }

        return response()->json(compact('token'));
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }

        $user = User::create([
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'password' => Hash::make($request->get('password')),
            'phone' => $request->phone,
            'address' => $request->address,
            'company_name' => $request->company_name
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json(compact('user','token'),201);
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

    public function edit(Request $r){
        $user = Auth::user();
        $user->name = $r->name;
        $user->email = $r->email;
        $user->address = $r->address;
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
