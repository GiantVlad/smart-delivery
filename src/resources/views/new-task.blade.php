@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8">
            <main class="mt-6">
                <div class="grid gap-6 lg:grid-cols-2 lg:gap-8">
                    <form method="POST" name="task" action="/task">
                        @csrf
                        <div class="mb-3">
                            <label for="courierUuid" class="form-label">Courier</label>
                            <select name="courierUuid" class="form-select" id="courierUuid">
                            @foreach($couriers as $courier)
                                <option value="{{ $courier->uuid }}">{{ $courier->name }}</option>
                            @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="multiple-select-orders" class="form-label">Orders</label>
                            <select class="form-select" id="multiple-select-orders" data-placeholder="Choose orders" multiple>
                                    <option></option>
                                @foreach($orders as $order)
                                    <option value="{{ $order->id }}">{{ $order->uuid }}</option>
                                @endforeach
                            </select>
                        </div>
                        <input type="hidden" name="orderIds" id="select-orders" value="">
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                </div>
            </main>
        </div>
    </div>

</div>
@endsection
@push('scripts')
    <script>
        $(document).ready(function () {
            $('#multiple-select-orders').select2({
                theme: "bootstrap-5",
                width: $( this ).data( 'width' ) ? $( this ).data( 'width' ) : $( this ).hasClass( 'w-100' ) ? '100%' : 'style',
                placeholder: $( this ).data( 'placeholder' ),
                closeOnSelect: false,
            })
            $('#multiple-select-orders').on('change', function () {
                const values = $(this).val().join(",");
                $('#select-orders').val(values);
            });
        })
    </script>
@endpush
