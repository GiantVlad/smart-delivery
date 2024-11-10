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
use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

#[WorkflowInterface]
interface CreateOrderWorkflowInterface
{
    #[WorkflowMethod(name: "CreateOrderActivity.create")]
    public function create(Customer $customer, string $unitType);
}
