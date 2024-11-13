<?php

declare(strict_types=1);

namespace App\Temporal;

use Illuminate\Support\Facades\Http;

class CreateOrderErpActivity implements CreateOrderErpActivityInterface
{
    public function createOrderInErp(string $orderUuid): string
    {
        Http::accept('application/json')
            ->post('http://go-server:8090/erp', ['orderUuid' => $orderUuid])
            ->throw();

        return $orderUuid;
    }
}
