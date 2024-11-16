@extends('layouts.admin')

@section('content')
    <noscript>
        <strong
        >We're sorry but this app doesn't work properly without JavaScript enabled. Please enable it
            to continue.</strong
        >
    </noscript>
    <script>
        if (localStorage.getItem('sidebar-expanded') == 'true') {
            document.querySelector('body').classList.add('sidebar-expanded');
        } else {
            document.querySelector('body').classList.remove('sidebar-expanded');
        }
    </script>
    <div id="app"></div>
@endsection
