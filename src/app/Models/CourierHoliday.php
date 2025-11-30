<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourierHoliday extends Model
{
    use HasFactory;
    protected $fillable = [
        'courier_id',
        'date_from',
        'date_to',
    ];

    protected $hidden = [];

    public function courier()
    {
        return $this->belongsTo(Courier::class);
    }
}
