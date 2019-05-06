<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'id',
        'user_id',
        'address',
        'quantity',
        'delivery_date',
        'delivery_time',
        'total_price',
        'status',
    ];

    public function user(){
        return $this->belongsTo('App\User');
    }

    const STATUS_PLACED = 0;
    const STATUS_PENDING = 1;
    const STATUS_DISPATCHED  = 2;

    public static function listStatus()
    {
        return [
            self::STATUS_PLACED => 'placed',
            self::STATUS_PENDING => 'Pending',
            self::STATUS_DISPATCHED  => 'Dispached'
        ];
    }

    public function statusLabel()
    {
        $list = self::listStatus();

        // little validation here just in case someone mess things
        // up and there's a ghost status saved in DB
        return isset($list[$this->status])
            ? $list[$this->status]
            : $this->status;
    }

}
