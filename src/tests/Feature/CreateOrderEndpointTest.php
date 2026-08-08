<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Dto\CreateOrderCommand;
use App\Models\Customer;
use App\Models\Slot;
use App\Models\User;
use App\Temporal\OrderWorkflowInterface;
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
        config()->set('features.erp_order_acceptance_enabled', false);

        $deliveryDate = now()->addDay()->toDateString();

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
            'date' => $deliveryDate,
        ]);
        $slotB = Slot::create([
            'from' => '09:00',
            'to' => '10:00',
            'capacity' => 10,
            'available' => 9,
            'date' => $deliveryDate,
        ]);

        $workflowStub = new \stdClass();
        $workflowRun = Mockery::mock(WorkflowRunInterface::class);

        $this->mock(WorkflowClientInterface::class, function (MockInterface $mock) use ($workflowStub, $workflowRun, $customer, $slotA, $slotB): void {
            $mock->shouldReceive('newWorkflowStub')
                ->once()
                ->with(OrderWorkflowInterface::class, Mockery::type(\Temporal\Client\WorkflowOptions::class))
                ->andReturn($workflowStub);

            $mock->shouldReceive('start')
                ->once()
                ->with(
                    $workflowStub,
                    Mockery::on(function (CreateOrderCommand $command) use ($customer, $slotA, $slotB): bool {
                        if ($command->orderUuid === '' || $command->customerUuid !== $customer->uuid) {
                            return false;
                        }

                        if (count($command->timeRanges) !== 2) {
                            return false;
                        }

                        $slotIds = array_column($command->timeRanges, 'slot_id');
                        sort($slotIds);

                        return $slotIds === [$slotA->id, $slotB->id]
                            && $command->erpAcceptanceEnabled === false;
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
            'date' => $deliveryDate,
        ];

        $response = $this
            ->actingAs($user)
            ->postJson('/api/order', $payload);

        $response
            ->assertOk()
            ->assertJsonStructure(['data' => ['uuid']]);

        $this->assertDatabaseHas('points', ['address' => 'Pickup street 1']);
        $this->assertDatabaseHas('points', ['address' => 'Destination street 5']);
    }
}
