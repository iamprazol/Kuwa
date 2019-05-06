<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class inventory extends Model
{
    protected $fillable = [
        'id',
        'user_id',
        'total',
        'sold'
    ];

    public function user(){
        return $this->belongsTo('App\User');
    }
}
