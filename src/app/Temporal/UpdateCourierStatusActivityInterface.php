<?php

declare(strict_types=1);

namespace App\Temporal;

use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;

#[ActivityInterface(prefix: 'UpdateCourierStatusActivity.')]
interface UpdateCourierStatusActivityInterface
{
    #[ActivityMethod(name: "UpdateCourierStatus")]
    public function updateCourierStatus (
        string $courierUuid,
        string $status,
    ): string;
}
