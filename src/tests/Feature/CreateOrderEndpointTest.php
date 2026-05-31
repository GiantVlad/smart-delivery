<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Dto\OrderDto;
use App\Models\Customer;
use App\Models\Slot;
use App\Models\User;
use App\Temporal\CreateOrderWorkflowInterface;
use Illuminate\Support\Str;
use Mockery;
use Mockery\MockInterface;
use Temporal\Client\WorkflowClientInterface;
use Temporal\Workflow\WorkflowRunInterface;
use Tests\TestCase;

class CreateOrderEndpointTest extends TestCase
{
    public function test_guest_cannot_create_order(): void
    {
        $response = $this->postJson('/api/order', []);

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_submit_order_and_dispatch_workflow(): void
    {
        $user = User::create([
            'name' => 'Order User',
            'email' => 'order-user@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $customer = Customer::create([
            'name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'uuid' => (string) Str::uuid(),
        ]);

        $slotA = Slot::create([
            'from' => '08:00',
            'to' => '09:00',
            'capacity' => 10,
            'available' => 10,
            'date' => '2026-06-01',
        ]);
        $slotB = Slot::create([
            'from' => '09:00',
            'to' => '10:00',
            'capacity' => 10,
            'available' => 9,
            'date' => '2026-06-01',
        ]);

        $workflowStub = new \stdClass;
        $workflowRun = Mockery::mock(WorkflowRunInterface::class);

        $this->mock(WorkflowClientInterface::class, function (MockInterface $mock) use ($workflowStub, $workflowRun, $customer, $slotA, $slotB): void {
            $mock->shouldReceive('newWorkflowStub')
                ->once()
                ->with(CreateOrderWorkflowInterface::class, Mockery::type(\Temporal\Client\WorkflowOptions::class))
                ->andReturn($workflowStub);

            $mock->shouldReceive('start')
                ->once()
                ->with(
                    $workflowStub,
                    Mockery::on(function (OrderDto $dto) use ($customer, $slotA, $slotB): bool {
                        if ($dto->customerUuid !== $customer->uuid) {
                            return false;
                        }

                        if ($dto->from !== '08:00' || $dto->to !== '09:00') {
                            return false;
                        }

                        if (count($dto->timeRanges) !== 2) {
                            return false;
                        }

                        $slotIds = array_column($dto->timeRanges, 'slot_id');
                        sort($slotIds);

                        return $slotIds === [$slotA->id, $slotB->id];
                    })
                )
                ->andReturn($workflowRun);
        });

        $payload = [
            'customerEmail' => $customer->email,
            'unitType' => 'Medium',
            'startPoint' => [
                'address' => 'Pickup street 1',
                'lat' => 52.2297,
                'lng' => 21.0122,
            ],
            'endPoint' => [
                'address' => 'Destination street 5',
                'lat' => 52.22,
                'lng' => 21.03,
            ],
            'slotIds' => [$slotA->id, $slotB->id],
            'date' => '2026-06-01',
        ];

        $response = $this
            ->actingAs($user)
            ->postJson('/api/order', $payload);

        $response
            ->assertOk()
            ->assertJson(['data' => true]);

        $this->assertDatabaseHas('points', ['address' => 'Pickup street 1']);
        $this->assertDatabaseHas('points', ['address' => 'Destination street 5']);
    }
}
