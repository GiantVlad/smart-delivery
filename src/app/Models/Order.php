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

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function startPoint()
    {
        return $this->belongsTo(Point::class);
    }

    public function endPoint()
    {
        return $this->belongsTo(Point::class);
    }
}
