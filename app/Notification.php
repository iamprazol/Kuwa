<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'id',
        'user_id',
        'title',
        'message',
        'read_status'
    ];

    public function user(){
        return $this->belongsTo('App\User');
    }
}
