<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Slot extends Model
{
    use HasFactory;

    public const MAX_CAPACITY = 500;

    protected $fillable = [
        'from',
        'to',
        'capacity',
        'available',
        'date',
    ];

    protected $hidden = [];
}
