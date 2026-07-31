@php($step = 3)
@extends('install.layout')
@section('title', 'Verify your purchase')
@section('content')
    <div class="stepno">Step 3 of 5</div>
    <h1>Verify your purchase</h1>
    <p class="lead">
        Confirm your CodeCanyon purchase. Enter your purchase code and a personal token
        generated on your own Envato account — the purchase is verified live with Envato.
    </p>

    <form method="POST" action="{{ route('install.license.verify') }}">
        @csrf
        <label for="purchase_code">Envato purchase code</label>
        <input type="text" id="purchase_code" name="purchase_code" value="{{ old('purchase_code') }}"
               placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" autocomplete="off" autofocus>
        @error('purchase_code')<div class="err-text">{{ $message }}</div>@enderror

        <label for="purchase_token" style="margin-top:16px">Envato personal token</label>
        <input type="text" id="purchase_token" name="purchase_token" value="{{ old('purchase_token') }}"
               placeholder="paste your Envato personal token" autocomplete="off">
        @error('purchase_token')<div class="err-text">{{ $message }}</div>@enderror

        <div class="alert info" style="margin-top:18px">
            <b>Purchase code —</b> CodeCanyon → <b>Downloads</b> → click <b>Download</b> next to this item →
            <b>License certificate &amp; purchase code</b>.<br><br>
            <b>Personal token —</b> open <b>build.envato.com/create-token</b>, give it a name
            (e.g. “Install”), enable the permissions
            <b>“View and search Envato sites”</b> and <b>“Download your purchased items”</b>,
            then create it and paste it above. You can delete the token again once setup is done.
        </div>

        <button type="submit" class="btn">Verify &amp; continue →</button>
    </form>
@endsection
