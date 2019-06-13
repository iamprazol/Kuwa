<?php

namespace App\Http\Controllers;

use App\Inventory;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\InventoryResource as InventoryResource;
use Illuminate\Support\Facades\Validator;


class InventoryController extends Controller
{
    public function myInventory(){
        $inventory = Auth::user()->inventory()->first();
        $data = new InventoryResource($inventory);
        return $this->responser($inventory, $data, 'User\'s Inventory Found Successfully');
    }

    public function removeFromInventory(Request $r)
    {
        $inventory = Auth::user()->inventory()->first();
        $remaining = $inventory->total - $inventory->sold;
        if($r->quantity <= $remaining) {
            $inventory->sold = $inventory->sold + $r->quantity;
            $inventory->save();

            if ($inventory->sold >= (80 * $inventory->total) / 100) {

                $title = 'Inventory being empited';
                $message = 'Your Inventory is about to be empited';
                $this->addNotification($inventory->user_id, $message, $title, $inventory->user->firebase_token);

                $data = new InventoryResource($inventory);
                return $this->responser($inventory, $data, 'Item From User\'s Inventory decremented by ' . $r->quantity . ' units successfully and it has less then 75% of total jars available');
            } else {
                $data = new InventoryResource($inventory);
                return $this->responser($inventory, $data, 'Item From User\'s Inventory decremented by ' . $r->quantity . ' units successfully');
            }
        } else {
            $data = new InventoryResource($inventory);
            return response()->json(['data' => $data, 'message' => 'You don\'t have enough item in the inventory', 'status' => 403], 403);
        }
    }

    public function listInventory(){
        $users = User::orderBy('name', 'asc')->where('admin',0)->get();
        foreach ($users as $user){
            $inventory = Inventory::where('user_id', $user->id)->first();
            if($inventory != null){
                $inv[] = $inventory;
            } else {
                $inv[] = null;
            }
        }
        $inventory = collect(array_filter($inv));

        $data = InventoryResource::collection($inventory);
        $count = count($inv);
        if($count > 0){
            $message = 'All User\'s Inventory Listed successfully';
            return response()->json([
                'data' => $data,
                'status' => 200,
                'message' => $message
            ], 200);
        } else {
            return response()->json([
                'data' => $inventory,
                'status' => 404,
                'message' => 'Item not found'
            ], 404);
        }
    }

    public function adminInventory(Request $r){
        $validator = Validator::make($r->all(), [
            'quantity' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'status' => 400], 400);
        }

        $inventory = Auth::user()->inventory()->first();
        if (!$inventory) {
            $inventory = Inventory::create([
                'user_id' => Auth::id(),
                'total' => $r->quantity,
            ]);
        } else {
            $remaining = $inventory->total - $inventory->sold;
            $inventory->total = $remaining + $r->quantity;
            $inventory->sold = 0;
            $inventory->save();
        }

        $title = 'Inventory is updated';
        $message = $r->quantity.' Items has been added to your inventory';
        $this->addNotification($inventory->user_id, $message, $title, $inventory->user->firebase_token);

        $data = new InventoryResource($inventory);
        return $this->responser($inventory, $data,  $r->quantity .'Items has been added to admin\'s inventory');

    }
}
