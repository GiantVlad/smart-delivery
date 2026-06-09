<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\TaskStatusEnum;
use App\Models\Courier;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Point;
use App\Models\Task;
use App\Rules\FirstLastRouteRule;
use Illuminate\Support\Str;
use Tests\TestCase;

class FirstLastRouteRuleTest extends TestCase
{
    private Point $pickupA;

    private Point $deliveryA;

    private Point $pickupB;

    private Point $deliveryB;

    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pickupA = Point::create(['address' => 'Pickup A', 'lat' => 52.22, 'long' => 21.01]);
        $this->deliveryA = Point::create(['address' => 'Delivery A', 'lat' => 52.23, 'long' => 21.02]);
        $this->pickupB = Point::create(['address' => 'Pickup B', 'lat' => 52.24, 'long' => 21.03]);
        $this->deliveryB = Point::create(['address' => 'Delivery B', 'lat' => 52.25, 'long' => 21.04]);

        $customer = Customer::create([
            'name' => 'Test',
            'last_name' => 'Customer',
            'email' => 'firstlastrule-test@example.com',
            'uuid' => (string) Str::uuid(),
        ]);

        $courier = Courier::create([
            'name' => 'Courier Test',
            'uuid' => (string) Str::uuid(),
        ]);

        $this->task = new Task;
        $this->task->uuid = (string) Str::uuid();
        $this->task->status = TaskStatusEnum::CREATED->value;
        $this->task->courier_id = $courier->id;
        $this->task->save();

        // Order A: pickup_A -> delivery_A
        $order1 = new Order;
        $order1->customer_id = $customer->id;
        $order1->unit_type = 'Medium';
        $order1->uuid = (string) Str::uuid();
        $order1->status = 'assigned';
        $order1->task_id = $this->task->id;
        $order1->start_point_id = $this->pickupA->id;
        $order1->end_point_id = $this->deliveryA->id;
        $order1->date = '2026-06-10';
        $order1->save();

        // Order B: pickup_B -> delivery_B
        $order2 = new Order;
        $order2->customer_id = $customer->id;
        $order2->unit_type = 'Medium';
        $order2->uuid = (string) Str::uuid();
        $order2->status = 'assigned';
        $order2->task_id = $this->task->id;
        $order2->start_point_id = $this->pickupB->id;
        $order2->end_point_id = $this->deliveryB->id;
        $order2->date = '2026-06-10';
        $order2->save();
    }

    public function test_invalid_route_delivery_b_before_pickup_b(): void
    {
        // Route: pickup_A, delivery_B, pickup_B, delivery_A
        // delivery_B (pos 1) comes before pickup_B (pos 2) — INVALID
        $rule = new FirstLastRouteRule;
        $rule->setData(['taskUuid' => $this->task->uuid]);

        $errors = [];
        $rule->validate('points', [
            $this->pickupA->id,
            $this->deliveryB->id,
            $this->pickupB->id,
            $this->deliveryA->id,
        ], function ($message) use (&$errors) {
            $errors[] = $message;
        });

        $this->assertNotEmpty($errors, 'Expected validation to fail');
        $this->assertTrue(
            collect($errors)->contains(fn ($e) => str_contains($e, 'Pickup point must come before delivery point')),
            'Expected pickup-before-delivery error. Errors: '.implode(', ', $errors)
        );
    }

    public function test_invalid_route_delivery_a_before_pickup_a(): void
    {
        // Route: pickup_B, delivery_A, pickup_A, delivery_B
        // delivery_A (pos 1) comes before pickup_A (pos 2) — INVALID
        $rule = new FirstLastRouteRule;
        $rule->setData(['taskUuid' => $this->task->uuid]);

        $errors = [];
        $rule->validate('points', [
            $this->pickupB->id,
            $this->deliveryA->id,
            $this->pickupA->id,
            $this->deliveryB->id,
        ], function ($message) use (&$errors) {
            $errors[] = $message;
        });

        $this->assertNotEmpty($errors, 'Expected validation to fail');
        $this->assertTrue(
            collect($errors)->contains(fn ($e) => str_contains($e, 'Pickup point must come before delivery point')),
            'Expected pickup-before-delivery error. Errors: '.implode(', ', $errors)
        );
    }

    public function test_valid_route_all_pickups_before_deliveries(): void
    {
        // Route: pickup_A, pickup_B, delivery_B, delivery_A — VALID
        $rule = new FirstLastRouteRule;
        $rule->setData(['taskUuid' => $this->task->uuid]);

        $errors = [];
        $rule->validate('points', [
            $this->pickupA->id,
            $this->pickupB->id,
            $this->deliveryB->id,
            $this->deliveryA->id,
        ], function ($message) use (&$errors) {
            $errors[] = $message;
        });

        $this->assertEmpty($errors, 'Expected validation to pass. Errors: '.implode(', ', $errors));
    }

    public function test_valid_route_interleaved_valid_ordering(): void
    {
        // Route: pickup_A, pickup_B, delivery_A, delivery_B — VALID
        $rule = new FirstLastRouteRule;
        $rule->setData(['taskUuid' => $this->task->uuid]);

        $errors = [];
        $rule->validate('points', [
            $this->pickupA->id,
            $this->pickupB->id,
            $this->deliveryA->id,
            $this->deliveryB->id,
        ], function ($message) use (&$errors) {
            $errors[] = $message;
        });

        $this->assertEmpty($errors, 'Expected validation to pass. Errors: '.implode(', ', $errors));
    }

    public function test_invalid_first_point(): void
    {
        $rule = new FirstLastRouteRule;
        $rule->setData(['taskUuid' => $this->task->uuid]);

        $errors = [];
        $rule->validate('points', [
            $this->deliveryA->id, // not a valid pickup point
            $this->pickupA->id,
            $this->pickupB->id,
            $this->deliveryB->id,
        ], function ($message) use (&$errors) {
            $errors[] = $message;
        });

        $this->assertNotEmpty($errors, 'Expected validation to fail');
    }

    public function test_invalid_last_point(): void
    {
        $rule = new FirstLastRouteRule;
        $rule->setData(['taskUuid' => $this->task->uuid]);

        $errors = [];
        $rule->validate('points', [
            $this->pickupA->id,
            $this->deliveryA->id,
            $this->pickupB->id,
            $this->pickupB->id, // not a valid delivery point (it's a pickup)
        ], function ($message) use (&$errors) {
            $errors[] = $message;
        });

        $this->assertNotEmpty($errors, 'Expected validation to fail');
    }
}
