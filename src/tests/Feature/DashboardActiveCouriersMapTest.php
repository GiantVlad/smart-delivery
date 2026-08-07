<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CourierStatusEnum;
use App\Enums\TaskStatusEnum;
use App\Models\Courier;
use App\Models\Point;
use App\Models\Route as DeliveryRoute;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardActiveCouriersMapTest extends TestCase
{
    public function test_it_returns_on_task_couriers_at_latest_active_route_point(): void
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret123'),
        ]);
        $courier = Courier::factory()->create([
            'name' => 'Active Courier',
            'status' => CourierStatusEnum::OT->value,
        ]);
        Courier::factory()->create([
            'name' => 'Ready Courier',
            'status' => CourierStatusEnum::RD->value,
        ]);

        $task = new Task();
        $task->courier_id = $courier->id;
        $task->uuid = Str::uuid()->toString();
        $task->status = TaskStatusEnum::STARTED->value;
        $task->save();

        $pickupPoint = Point::factory()->create([
            'address' => 'Pickup address',
            'lat' => 53.9,
            'long' => 27.5,
        ]);
        $latestPoint = Point::factory()->create([
            'address' => 'Delivery address',
            'lat' => 53.95,
            'long' => 27.6,
        ]);

        $pickupRoute = new DeliveryRoute();
        $pickupRoute->task_id = $task->id;
        $pickupRoute->point_id = $pickupPoint->id;
        $pickupRoute->sequence = 1;
        $pickupRoute->point_type = 'start';
        $pickupRoute->save();

        $latestRoute = new DeliveryRoute();
        $latestRoute->task_id = $task->id;
        $latestRoute->point_id = $latestPoint->id;
        $latestRoute->sequence = 2;
        $latestRoute->point_type = 'finish';
        $latestRoute->save();

        $response = $this
            ->actingAs($user)
            ->getJson('/api/dashboard/active-couriers-map');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.courierName', 'Active Courier')
            ->assertJsonPath('data.0.taskUuid', $task->uuid)
            ->assertJsonPath('data.0.pointAddress', 'Delivery address')
            ->assertJsonPath('data.0.sequence', 2)
            ->assertJsonPath('data.0.lat', 53.95)
            ->assertJsonPath('data.0.lng', 27.6);
    }
}
