<?php

namespace App\Http\Controllers;

use App\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\InventoryResource as InventoryResource;

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
        $firebase_token = Auth::user()->firebase_token;
        $remaining = $inventory->total - $inventory->sold;
        if($r->quantity <= $remaining) {
            $inventory->sold = $inventory->sold + $r->quantity;
            $inventory->save();

            if ($inventory->sold >= (75 * $inventory->total) / 100) {

                $title = 'Inventory being empited';
                $message = 'Your Inventory is about to be empited';
                $this->addNotification($inventory, $message, $title);

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
        $inventory = Inventory::get();
        $data = InventoryResource::collection($inventory);
        return $this->responser($inventory, $data, 'All User\'s Inventory Listed successfully');
    }
}
