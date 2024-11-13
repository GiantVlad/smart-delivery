<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Courier extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'uuid',
        'status',
    ];

    protected $hidden = [];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
