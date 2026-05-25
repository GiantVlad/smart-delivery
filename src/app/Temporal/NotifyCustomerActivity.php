<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Models\Customer;
use Illuminate\Notifications\Notification;

class NotifyCustomerActivity implements NotifyCustomerActivityInterface
{
    /**
     * @param  class-string<Notification>  $notificationClass
     */
    public function notifyCustomer(string $customerUuid, string $orderUuid, string $notificationClass): string
    {
        $customer = Customer::where('uuid', $customerUuid)->firstOrFail();
        $customer->notify(new $notificationClass($orderUuid));

        return $orderUuid;
    }
}
