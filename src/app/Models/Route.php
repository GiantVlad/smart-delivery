<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'sequence',
    ];

    protected $hidden = [];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function point()
    {
        return $this->belongsTo(Point::class);
    }
}
