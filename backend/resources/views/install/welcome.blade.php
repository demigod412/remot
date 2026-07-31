@php($step = 1)
@extends('install.layout')
@section('title', 'Welcome')
@section('content')
    <div class="stepno">Step 1 of 5</div>
    <h1>Welcome to Job Station</h1>
    <p class="lead">
        Thanks for purchasing Job Station — the work, hire &amp; earn marketplace.
        This wizard will check your server, verify your CodeCanyon purchase code,
        connect your database, and create your admin account. It takes about two minutes.
    </p>

    <div class="alert info">
        Before you start, make sure you have your <b>Envato purchase code</b> and your
        <b>database name, username and password</b> ready.
    </div>

    <a href="{{ route('install.requirements') }}" class="btn">Get started →</a>
@endsection
