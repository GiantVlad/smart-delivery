@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8">
            <table class="table">
                <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">UUID</th>
                    <th scope="col">Type</th>
                    <th scope="col">Status</th>
                    <th scope="col">Customer UUID</th>
                    <th scope="col">Courier</th>
                    <th scope="col">Date</th>
                </tr>
                </thead>
                <tbody>
                @foreach($orders as $order)
                <tr>
                    <th scope="row">{{ $order->id }}</th>
                    <td>{{ $order->uuid }}</td>
                    <td>{{ $order->unit_type }}</td>
                    <td>{{ $order->status }}</td>
                    <td>{{ $order->customer->uuid }}</td>
                    <td>{{ $order->courier ? $order->courier->name : 'UNASSIGNED'}}</td>
                    <td>{{ $order->updated_at }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
