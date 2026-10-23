@extends('errors.layout')
@section('title', 'No access')
@section('code', '403')
@section('heading', 'You can’t open this page')
@section('message'){{ $exception->getMessage() ?: 'Your account doesn’t have access to this page.' }}@endsection
