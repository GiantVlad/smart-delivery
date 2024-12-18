<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CreateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    public function createCustomer(CreateCustomerRequest $request): JsonResource
    {
        $customer = new Customer();
        $customer->uuid = Str::uuid();
        $customer->name = $request->get('first_name');
        $customer->last_name = $request->get('last_name');
        $customer->email = $request->get('email');
        $customer->save();

        return CustomerResource::make($customer);
    }

    public function get(?int $limit = null): JsonResource
    {
        $customers = Customer::query();
        if ($limit) {
            $customers = $customers->limit($limit);
        }
        $customers = $customers->orderBy('id', 'desc')->get();

        return CustomerResource::collection($customers);
    }
}
