<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TaskStatusEnum;
use App\Models\Courier;
use App\Models\Task;
use App\Models\User;
use Temporal\Client\WorkflowClientInterface;
use Tests\TestCase;

class ActiveTasksTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'active-tasks-test@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $this->mock(WorkflowClientInterface::class);
    }

    private function createTask(string $status): Task
    {
        $courier = Courier::create([
            'name' => 'Courier Test',
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
        ]);

        $task = new Task();
        $task->uuid = (string) \Illuminate\Support\Str::uuid();
        $task->status = $status;
        $task->courier_id = $courier->id;
        $task->save();

        return $task;
    }

    public function test_get_active_tasks_returns_only_active_tasks(): void
    {
        $createdTask = $this->createTask(TaskStatusEnum::CREATED->value);
        $startedTask = $this->createTask(TaskStatusEnum::STARTED->value);
        $finishedTask = $this->createTask(TaskStatusEnum::FINISHED->value);
        $canceledTask = $this->createTask(TaskStatusEnum::CANCELED->value);

        $response = $this->actingAs($this->user)->getJson('/api/active-tasks');

        $response->assertOk();

        $uuids = collect($response->json('data'))->pluck('uuid')->all();

        $this->assertContains($createdTask->uuid, $uuids);
        $this->assertContains($startedTask->uuid, $uuids);
        $this->assertNotContains($finishedTask->uuid, $uuids);
        $this->assertNotContains($canceledTask->uuid, $uuids);
    }

    public function test_get_active_tasks_excludes_finished_and_canceled(): void
    {
        $this->createTask(TaskStatusEnum::FINISHED->value);
        $this->createTask(TaskStatusEnum::CANCELED->value);

        $response = $this->actingAs($this->user)->getJson('/api/active-tasks');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }

    public function test_guest_cannot_get_active_tasks(): void
    {
        $response = $this->getJson('/api/active-tasks');
        $response->assertUnauthorized();
    }
}
