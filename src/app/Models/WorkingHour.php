<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkingHour extends Model
{
    use HasFactory;
    protected $fillable = [
        'courier_id',
        'date',
        'from',
        'to',
    ];

    protected $hidden = [];

    public function courier()
    {
        return $this->belongsTo(Courier::class);
    }
}
