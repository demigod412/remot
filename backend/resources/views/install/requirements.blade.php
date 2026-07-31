@php($step = 2)
@extends('install.layout')
@section('title', 'Requirements')
@section('content')
    <div class="stepno">Step 2 of 5</div>
    <h1>Server requirements</h1>
    <p class="lead">Your server must meet the requirements below before continuing.</p>

    <div class="grp-title">PHP version</div>
    @foreach($groups['php'] as $c)
        <div class="check">
            <span>{{ $c['label'] }} <span style="color:var(--fg3)">— found {{ $c['current'] }}</span></span>
            <span class="pill {{ $c['passed'] ? 'ok' : 'bad' }}">{{ $c['passed'] ? 'OK' : 'Fail' }}</span>
        </div>
    @endforeach

    <div class="grp-title">PHP extensions</div>
    @foreach($groups['extensions'] as $c)
        <div class="check">
            <span>{{ $c['label'] }}</span>
            <span class="pill {{ $c['passed'] ? 'ok' : 'bad' }}">{{ $c['passed'] ? 'Loaded' : 'Missing' }}</span>
        </div>
    @endforeach

    <div class="grp-title">Folder permissions (writable)</div>
    @foreach($groups['permissions'] as $c)
        <div class="check">
            <span>{{ $c['label'] }}</span>
            <span class="pill {{ $c['passed'] ? 'ok' : 'bad' }}">{{ $c['passed'] ? 'Writable' : 'Not writable' }}</span>
        </div>
    @endforeach

    @if($passes)
        <a href="{{ route('install.license') }}" class="btn">Continue →</a>
    @else
        <div class="alert err" style="margin-top:20px">
            Please fix the failing items above, then reload this page.
        </div>
        <a href="{{ route('install.requirements') }}" class="btn ghost">Re-check</a>
    @endif
@endsection
