<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'status',
    ];

    protected $hidden = [];

    public function courier()
    {
        return $this->belongsTo(Courier::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
