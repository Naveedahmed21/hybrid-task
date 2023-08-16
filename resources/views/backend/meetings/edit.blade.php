@extends('layouts.app')

@section('title', '| Edit Meeting')

@section('breadcrumb')
    <div class="page-header">
        <h1 class="page-title">Edit Meeting</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit Meeting</li>
            </ol>
        </div>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-header justify-content-between">
            <h3 class="card-title font-weight-bold">Edit Meetings</h3>
        </div>
        <div class="card-body">
            @include('backend.meetings.form')
        </div>
    </div>
@endsection
