<?php

declare(strict_types=1);

namespace Tprl;

use App\Models\Customer;
use App\Notifications\OrderCreatedNotification;
use Temporal\Activity;
use Temporal\Exception\IllegalStateException;

// @@@SNIPSTART php-hello-activity
class NotifyOrderCreatedActivity implements NotifyOrderCreatedActivityInterface
{
    public function notifyOrderCreated(string $customerUuid, string $orderUuid): string
    {
        $customer = Customer::where('uuid', $customerUuid)->firstOrFail();
        $customer->notify(new OrderCreatedNotification($orderUuid));

        return $orderUuid;
    }
}
// @@@SNIPEND
