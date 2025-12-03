<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\HolidayReason;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class CourierHoliday extends Model
{
    use HasFactory;

    protected $fillable = [
        'courier_id',
        'date',
        'reason_code',
    ];

    protected $hidden = [];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'reason_code' => 'integer',
    ];

    /**
     * Get the reason as an enum instance.
     */
    public function reason(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => HolidayReason::from($attributes['reason_code']),
            set: fn (HolidayReason $value) => ['reason_code' => $value->value],
        );
    }

    /**
     * Get the reason text.
     */
    public function getReasonTextAttribute(): string
    {
        return HolidayReason::getLabel($this->reason);
    }

    public function courier()
    {
        return $this->belongsTo(Courier::class);
    }
}
