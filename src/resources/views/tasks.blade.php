@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8">
            <a href="/new-task" class="btn btn-info" role="button">Create task</a>
            <table class="table">
                <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">UUID</th>
                    <th scope="col">Status</th>
                    <th scope="col">Courier</th>
                    <th scope="col">Number of orders</th>
                    <th scope="col">Date</th>
                </tr>
                </thead>
                <tbody>
                @foreach($tasks as $task)
                <tr>
                    <th scope="row">{{ $task->id }}</th>
                    <td>{{ $task->uuid }}</td>
                    <td>{{ $task->status }}</td>
                    <td>{{ $task->courier->name }}</td>
                    <td>{{ $task->orders()->count() }}</td>
                    <td>{{ $task->updated_at }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
