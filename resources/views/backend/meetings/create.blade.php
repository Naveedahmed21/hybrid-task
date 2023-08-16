@extends('layouts.app')

@section('title', '| Create Meeting')

@section('breadcrumb')
    <div class="page-header">
        <h1 class="page-title">Create Meeting</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Create Meeting</li>
            </ol>
        </div>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-header justify-content-between">
            <h3 class="card-title font-weight-bold">Create Meetings</h3>
        </div>
        <div class="card-body">
            @include('backend.meetings.form')
        </div>
    </div>
@endsection
