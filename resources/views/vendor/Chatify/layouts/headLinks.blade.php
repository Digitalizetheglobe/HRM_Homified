@php
  $setting = \App\Models\Utility::colorset();
@endphp

<title>{{ config('chatify.name') }}</title>

{{-- Meta tags --}}
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="id" content="{{ $id }}">
<meta name="type" content="{{ $route }}">
<meta name="messenger-color" content="{{ $messengerColor }}">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="app-base" content="{{ url('/') }}">
<meta name="url" content="{{ url('') . '/' . config('chatify.routes.prefix') }}" data-user="{{ Auth::user()->id }}">

{{-- scripts --}}
<script src="{{ asset('js/jquery.min.js') }}"></script>
<script src="{{ asset('js/csrf-handler.js') }}"></script>
<script src="{{ asset('js/chatify/font.awesome.min.js') }}"></script>
<script src="{{ asset('js/chatify/autosize.js') }}"></script>
<script src="{{ asset('js/app.js') }}"></script>
<script src="{{ asset('js/nprogress-lite.js') }}"></script>

{{-- styles --}}
<link href="{{ asset('css/chatify/style.css') }}" rel="stylesheet" />
<link href="{{ asset('css/chatify/' . $dark_mode . '.mode.css') }}" rel="stylesheet" />

{{-- dark mode issue --}}
{{-- <link href="{{ asset('css/app.css') }}" rel="stylesheet" /> --}}

@if ($setting['cust_darklayout'] == 'on')
    <link rel="stylesheet" href="{{ asset('css/chatify/dark.mode.css') }}">
@else
    <link rel="stylesheet" href="{{ asset('css/chatify/light.mode.css') }}" id="main-style-link">
@endif

{{-- Messenger Color Style --}}
@include('Chatify::layouts.messengerColor')
