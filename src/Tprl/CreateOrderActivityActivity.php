<?php

/**
 * This file is part of Temporal package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tprl;

use App\Models\Customer;
use App\Models\Order;
use Temporal\Activity;
use Temporal\Exception\IllegalStateException;

// @@@SNIPSTART php-hello-activity
class CreateOrderActivityActivity implements CreateOrderActivityInterface
{
    public function createOrder(Customer $customer, string $unitType): string
    {
        $order = new Order();
        $order->customer_id = $customer->id;
        $order->unit_type = $unitType;
        $order->save();
        return $order->uuid;
    }
}
// @@@SNIPEND
