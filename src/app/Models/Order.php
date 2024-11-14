<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'unit_type',
        'uuid',
        'status',
    ];

    protected $hidden = [];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function courier()
    {
        return $this->belongsTo(Courier::class);
    }
}
