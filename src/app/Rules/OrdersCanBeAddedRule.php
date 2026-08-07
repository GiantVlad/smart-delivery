<?php

declare(strict_types=1);

namespace App\Rules;

use App\Enums\OrderStatusEnum;
use App\Dto\OrderWorkflowState;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Temporal\Client\WorkflowClientInterface;
use Temporal\Exception\Client\WorkflowNotFoundException;

class OrdersCanBeAddedRule implements ValidationRule
{
    private WorkflowClientInterface $workflowClient;

    public function __construct(WorkflowClientInterface $workflowClient)
    {
        $this->workflowClient = $workflowClient;
    }

    /**
     * Run the validation rule.
     * @param  \Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            $fail('Invalid list of orders.');
            return;
        }

        foreach ($value as $orderUuid) {
            // Query the Order workflow directly for current status
            $workflow = $this->workflowClient->newRunningWorkflowStub(
                \App\Temporal\OrderWorkflowInterface::class,
                'order:' . $orderUuid,
            );

            try {
                /** @var OrderWorkflowState|null $state */
                $state = $workflow->getState();

                if (!$state) {
                    $fail("Order $orderUuid workflow not found.");
                    continue;
                }

                // Check if order is in ACCEPTED status (can be added to task)
                if ($state->status !== OrderStatusEnum::ACCEPTED->value) {
                    $fail("Order $orderUuid with status {$state->status} can not be added to task.");
                    continue;
                }

                // Check if order is already assigned to a task
                if ($state->taskUuid !== null) {
                    $fail("Order $orderUuid already assigned to a task.");
                    continue;
                }
            } catch (WorkflowNotFoundException $e) {
                $fail("Order $orderUuid workflow is not running. Please recreate the order.");
                continue;
            } catch (\Throwable $e) {
                $fail("Order $orderUuid workflow error: " . $e->getMessage());
                continue;
            }
        }
    }
}
