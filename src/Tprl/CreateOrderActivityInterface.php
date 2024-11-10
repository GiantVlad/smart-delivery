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
use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;

#[ActivityInterface(prefix: 'CreateOrderActivity.')]
interface CreateOrderActivityInterface
{
    #[ActivityMethod(name: "CreateOrder")]
    public function createOrder(
        Customer $customer,
        string $unitType,
    ): string;
}
