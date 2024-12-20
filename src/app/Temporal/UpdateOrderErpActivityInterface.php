<?php

declare(strict_types=1);

namespace App\Temporal;

use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;

#[ActivityInterface(prefix: 'UpdateOrderErpActivity.')]
interface UpdateOrderErpActivityInterface
{
    #[ActivityMethod(name: "UpdateOrderInErp")]
    public function updateOrderInErp(): array;
}
