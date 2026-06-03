<?php

declare(strict_types=1);

namespace Tests\Integration\Temporal;

use App\Dto\CreateOrderCommand;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Point;
use App\Temporal\OrderProjectionActivity;
use Illuminate\Support\Str;
use Tests\TestCase;

class CreateOrderActivityTest extends TestCase
{
    public function test_create_order_activity_creates_order_with_time_ranges(): void
    {
        $customer = Customer::create([
            'name' => 'John',
            'last_name' => 'Doe',
            'email' => 'create-order-activity@example.com',
            'uuid' => (string) Str::uuid(),
        ]);

        $startPoint = Point::create([
            'address' => 'Start Address',
            'lat' => 52.2297,
            'long' => 21.0122,
        ]);
        $endPoint = Point::create([
            'address' => 'End Address',
            'lat' => 52.24,
            'long' => 21.03,
        ]);

        $ranges = [
            [
                'slot_id' => 101,
                'date' => '2026-06-01',
                'from' => '08:00',
                'to' => '09:00',
            ],
            [
                'slot_id' => 102,
                'date' => '2026-06-01',
                'from' => '09:00',
                'to' => '10:00',
            ],
        ];

        $orderUuid = (string) Str::uuid();
        $command = new CreateOrderCommand(
            orderUuid: $orderUuid,
            customerUuid: $customer->uuid,
            unitType: 'Medium',
            startPointId: $startPoint->id,
            endPointId: $endPoint->id,
            timeRanges: $ranges,
        );

        $activity = new OrderProjectionActivity;
        $activity->create($command);

        $order = Order::query()
            ->where('uuid', $orderUuid)
            ->firstOrFail();

        $this->assertSame($customer->id, $order->customer_id);
        $this->assertSame($startPoint->id, $order->start_point_id);
        $this->assertSame($endPoint->id, $order->end_point_id);
        $this->assertSame('2026-06-01', $order->date);
        $this->assertSame($ranges, $order->time_ranges);
    }
}
