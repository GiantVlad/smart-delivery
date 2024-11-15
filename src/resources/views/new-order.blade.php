@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8">
            <main class="mt-6">
                <div class="grid gap-6 lg:grid-cols-2 lg:gap-8">
                    <form method="POST" name="order" action="/order">
                        @csrf
                        <div class="mb-3">
                            <label for="customerEmail" class="form-label">Email address</label>
                            <select name="email" class="form-select" id="customerEmail">
                            @foreach($customerEmails as $email)
                                <option>{{ $email }}</option>
                            @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="unitType" class="form-label">Type of order</label>
                            <select id="unitType" name="unitType" class="form-select">
                                <option>Small</option>
                                <option>Medium</option>
                                <option>Large</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="startAddress" class="form-label">Pick up</label>
                            <select name="startAddressId" class="form-select" id="startAddress">
                                @foreach($points as $point)
                                    <option value="{{ $point->id }}">{{ $point->address }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="endAddress" class="form-label">Destination</label>
                            <select name="endAddressId" class="form-select" id="endAddress">
                                @foreach($points as $point)
                                    <option value="{{ $point->id }}">{{ $point->address }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                </div>
            </main>
        </div>
    </div>
</div>
@endsection
