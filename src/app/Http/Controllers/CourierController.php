<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Dto\AssignOrderDto;
use App\Enums\OrderStatusEnum;
use App\Http\Requests\AddOrderRequest;
use App\Http\Requests\UnassignOrderRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderToAssignResource;
use App\Models\Courier;
use App\Models\Order;
use App\Models\Task;
use App\Temporal\AssignOrderWorkflowInterface;
use App\Temporal\UnassignOrderWorkflowInterface;
use Carbon\CarbonInterval;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Temporal\Client\WorkflowOptions;

use function PHPUnit\Framework\isEmpty;

class CourierController extends Controller
{
    public function get(?string $statuses = ''): JsonResource
    {
        $statuses = explode(',', $statuses ?? '');
        $couriers = Courier::query();
        if (count($statuses) > 0) {
            $couriers = $couriers->whereIn('status', $statuses);
        }

        $couriers->get();

        return CourierResource::collection($couriers);
    }
}
