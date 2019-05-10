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
        $inventory->sold = $inventory->sold + $r->quantity;
        $inventory->save();

        if ($inventory->sold >= (75 * $inventory->total) / 100) {
            $this->sendNotification($r->device_token, 'Your order has been Delivered', 'Order Delivered');
            $data = new InventoryResource($inventory);
            return $this->responser($inventory, $data, 'Item From User\'s Inventory decremented by ' . $r->quantity . ' units successfully and it has less then 75% of total jars available');
        } else {
            $data = new InventoryResource($inventory);
            return $this->responser($inventory, $data, 'Item From User\'s Inventory decremented by ' . $r->quantity . ' units successfully');
        }
    }

    public function listInventory(){
        $inventory = Inventory::paginate(15);
        $data = InventoryResource::collection($inventory);
        return $this->responser($inventory, $data, 'All User\'s Inventory Listed successfully');
    }
}
