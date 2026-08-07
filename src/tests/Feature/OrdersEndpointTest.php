<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Courier;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Point;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use Temporal\Client\WorkflowClientInterface;
use Tests\TestCase;

class OrdersEndpointTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(WorkflowClientInterface::class, function (MockInterface $mock): void {});
    }

    public function test_guest_cannot_access_orders_endpoint(): void
    {
        $this->getJson('/api/orders')->assertUnauthorized();
    }

    public function test_orders_endpoint_returns_latest_30_orders_sorted_and_with_time_ranges(): void
    {
        $user = User::create([
            'name' => 'Orders User',
            'email' => 'orders@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $customer = Customer::create([
            'name' => 'Test',
            'last_name' => 'Customer',
            'email' => 'customer@example.com',
            'uuid' => (string) Str::uuid(),
        ]);

        $pointA = Point::create([
            'address' => 'Point A',
            'lat' => 52.2297,
            'long' => 21.0122,
        ]);
        $pointB = Point::create([
            'address' => 'Point B',
            'lat' => 52.24,
            'long' => 21.02,
        ]);

        $courier = Courier::create([
            'name' => 'Courier One',
            'uuid' => (string) Str::uuid(),
            'status' => 'new',
        ]);
        $task = new Task();
        $task->courier_id = (string) $courier->id;
        $task->uuid = (string) Str::uuid();
        $task->status = 'new';
        $task->save();

        $base = Carbon::parse('2026-05-31 10:00:00');
        $legacyOrderUuid = null;

        for ($i = 1; $i <= 35; $i++) {
            $order = new Order();
            $order->customer_id = $customer->id;
            $order->unit_type = 'Medium';
            $order->uuid = (string) Str::uuid();
            $order->status = 'new';
            $order->task_id = $task->id;
            $order->start_point_id = $pointA->id;
            $order->end_point_id = $pointB->id;
            $order->date = '2026-05-31';
            $order->time_ranges = [[
                'slot_id' => $i,
                'date' => '2026-05-31',
                'from' => '08:00',
                'to' => '09:00',
            ]];
            $order->created_at = $base->copy()->addMinutes($i);
            $order->updated_at = $base->copy()->addMinutes($i);
            $order->save();

            if ($i === 35) {
                $legacyOrderUuid = $order->uuid;
            }
        }

        $response = $this->actingAs($user)->getJson('/api/orders');

        $response->assertOk();
        $data = $response->json('data');

        $this->assertCount(30, $data);
        $this->assertSame($legacyOrderUuid, $data[0]['uuid']);
        $this->assertSame('08:00', $data[0]['timeRanges'][0]['from']);
        $this->assertSame('09:00', $data[0]['timeRanges'][0]['to']);
        $this->assertSame('2026-05-31', $data[0]['timeRanges'][0]['date']);

        $updatedAt = array_column($data, 'updated_at');
        $sorted = $updatedAt;
        rsort($sorted);
        $this->assertSame($sorted, $updatedAt);
    }
}
