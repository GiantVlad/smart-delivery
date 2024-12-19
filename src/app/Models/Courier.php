<?php

declare(strict_types=1);

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
        'name',
        'phone'
    ];

    protected $hidden = [];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
