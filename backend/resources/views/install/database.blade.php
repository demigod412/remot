@php($step = 4)
@extends('install.layout')
@section('title', 'Database')
@section('content')
    <div class="stepno">Step 4 of 5</div>
    <h1>Database connection</h1>
    <p class="lead">Enter your MySQL/MariaDB details. We'll test the connection before saving.</p>

    <form method="POST" action="{{ route('install.database.save') }}">
        @csrf
        <div class="row">
            <div>
                <label for="db_host">Database host</label>
                <input type="text" id="db_host" name="db_host" value="{{ old('db_host', '127.0.0.1') }}">
            </div>
            <div style="max-width:140px">
                <label for="db_port">Port</label>
                <input type="text" id="db_port" name="db_port" value="{{ old('db_port', '3306') }}">
            </div>
        </div>

        <label for="db_database">Database name</label>
        <input type="text" id="db_database" name="db_database" value="{{ old('db_database') }}" placeholder="jobstation">

        <div class="row">
            <div>
                <label for="db_username">Database username</label>
                <input type="text" id="db_username" name="db_username" value="{{ old('db_username') }}" placeholder="root">
            </div>
            <div>
                <label for="db_password">Database password</label>
                <input type="password" id="db_password" name="db_password" value="" autocomplete="off" placeholder="••••••••">
            </div>
        </div>

        <button type="submit" class="btn">Test &amp; continue →</button>
    </form>
@endsection
