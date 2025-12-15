<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'tracking_number',
        'courier_code',
        'trackingmore_id',
        'delivery_status',
        'latest_event',
        'latest_checkpoint_time',
        'raw',
    ];

    protected $casts = [
        'raw' => 'array',
        'latest_checkpoint_time' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
