<?php

declare(strict_types=1);

namespace App\Temporal;

use App\Dto\CreateOrderCommand;
use App\Dto\OrderWorkflowState;
use App\Enums\OrderStatusEnum;
use App\Facades\CentrifugoFacade;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Task;
use Illuminate\Support\Facades\Log;

class OrderProjectionActivity implements OrderProjectionActivityInterface
{
    public function create(CreateOrderCommand $command): string
    {
        $customer = Customer::where('uuid', $command->customerUuid)->firstOrFail();
        $primaryRange = collect($command->timeRanges)
            ->first(static fn (array $range): bool => isset($range['date']));

        $order = Order::firstOrNew(['uuid' => $command->orderUuid]);
        $order->customer_id = $customer->id;
        $order->unit_type = $command->unitType;
        $order->start_point_id = $command->startPointId;
        $order->end_point_id = $command->endPointId;
        $order->date = $primaryRange['date'] ?? now()->toDateString();
        $order->time_ranges = $command->timeRanges;
        $order->status = OrderStatusEnum::NEW->value;
        $order->task_id = null;
        $order->delivered_at = null;
        $order->save();

        $this->publishStatus($order->uuid, $order->status);

        return $order->uuid;
    }

    public function update(OrderWorkflowState $state): string
    {
        $order = Order::where('uuid', $state->orderUuid)->firstOrFail();
        $order->status = $state->status;
        $order->task_id = $this->resolveTaskId($state->taskUuid);
        $order->delivered_at = $state->status === OrderStatusEnum::DELIVERED->value ? now() : null;
        $order->save();

        $this->publishStatus($order->uuid, $order->status);

        return $order->uuid;
    }

    private function resolveTaskId(?string $taskUuid): ?int
    {
        if ($taskUuid === null) {
            return null;
        }

        return Task::where('uuid', $taskUuid)->value('id');
    }

    private function publishStatus(string $orderUuid, string $status): void
    {
        try {
            CentrifugoFacade::publish('order_status', ['order' => $orderUuid, 'status' => $status]);
        } catch (\Throwable $error) {
            Log::error('Failed to publish order_status event', [
                'order' => $orderUuid,
                'status' => $status,
                'error' => $error->getMessage(),
            ]);
        }
    }
}
