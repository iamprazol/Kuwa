<?php

namespace App\Http\Controllers;

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

    public function removeFromInventory(Request $r){
        $inventory = Auth::user()->inventory()->first();
        $inventory->sold = $inventory->sold + $r->quantity;
        $inventory->save();

        $data = new InventoryResource($inventory);
        return $this->responser($inventory, $data, 'Item From User\'s Inventory decremented by '.$r->quantity.' units successfully');
    }
}
