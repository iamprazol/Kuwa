<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'full_name' => $this->name,
            'email' => $this->email,
            'address' => $this->address,
            'phone' => $this->phone,
            'company_name' => $this->company_name,
            'is_admin' => $this->is_admin(),
            'is_verified' => $this->verified()
        ];
    }

    public function verified(){
        $status = $this->is_verified;
        if($status == 0){
            return 'not verified';
        } else {
            return 'verified';
        }
    }

    public function is_admin(){
        $admin = $this->admin;
        if($admin == 0){
            return 'Customer';
        } else {
            return 'Admin';
        }
    }
}
