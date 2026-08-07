<?php

declare(strict_types=1);

namespace App\Rules;

use App\Dto\OrderWorkflowState;
use App\Temporal\OrderWorkflowInterface;
use App\Temporal\TaskWorkflowInterface;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Collection;
use Illuminate\Translation\PotentiallyTranslatedString;
use Temporal\Client\WorkflowClientInterface;
use Temporal\Exception\Client\WorkflowNotFoundException;

class FirstLastRouteRule implements DataAwareRule, ValidationRule
{
    /**
     * All of the data under validation.
     *
     * @var array<string, mixed>
     */
    protected $data = [];

    public function __construct(
        private WorkflowClientInterface $workflowClient
    ) {}

    /**
     * Set the data under validation.
     *
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $taskUuid = $this->data['taskUuid'];

        try {
            $taskWorkflow = $this->workflowClient->newRunningWorkflowStub(
                TaskWorkflowInterface::class,
                'task:' . $taskUuid
            );
            $taskState = $taskWorkflow->getState();
        } catch (WorkflowNotFoundException $e) {
            $fail('Task workflow not found.');
            return;
        }

        if (! $taskState || empty($taskState->orderUuids)) {
            $fail('Task has no orders or workflow state is invalid.');
            return;
        }

        $ordersData = [];
        foreach ($taskState->orderUuids as $orderUuid) {
            try {
                $orderWorkflow = $this->workflowClient->newRunningWorkflowStub(
                    OrderWorkflowInterface::class,
                    'order:' . $orderUuid
                );
                /** @var OrderWorkflowState $orderState */
                $orderState = $orderWorkflow->getState();
                $ordersData[] = [
                    'uuid' => $orderUuid,
                    'start_point_id' => $orderState->startPointId,
                    'end_point_id' => $orderState->endPointId,
                ];
            } catch (WorkflowNotFoundException $e) {
                $fail("Order $orderUuid workflow not found.");
                return;
            }
        }

        $ordersCollection = new Collection($ordersData);
        $startPoints = $ordersCollection->map(static fn ($order) => $order['start_point_id']);
        $endPoints = $ordersCollection->map(static fn ($order) => $order['end_point_id']);

        if (! $startPoints->contains($value[0])) {
            $fail('Invalid first point in the route.');
        }

        if (! $endPoints->contains($value[count($value) - 1])) {
            $fail('Invalid last point in the route.');
        }

        // Validate that for each order, pickup comes before delivery
        $pointIds = collect($value);
        foreach ($ordersCollection as $order) {
            $pickupPosition = $pointIds->search(fn ($id) => (int) $id === (int) $order['start_point_id']);
            $deliveryPosition = $pointIds->search(fn ($id) => (int) $id === (int) $order['end_point_id']);

            if ($pickupPosition === false) {
                $fail('Pickup point for order '.$order['uuid'].' is missing from the route.');
            }

            if ($deliveryPosition === false) {
                $fail('Delivery point for order '.$order['uuid'].' is missing from the route.');
            }

            if ($pickupPosition !== false && $deliveryPosition !== false && $pickupPosition > $deliveryPosition) {
                $fail('Pickup point must come before delivery point for each order.');
            }
        }
    }
}
