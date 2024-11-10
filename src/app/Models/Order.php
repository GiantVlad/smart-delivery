<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Order extends Authenticatable
{
    protected $fillable = [
        'unit_type',
        'uuid',
        'last_name',
    ];

    protected $hidden = [];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
