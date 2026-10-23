@php $pageTitle = 'Customer Satisfaction'; @endphp
@php $price = config('currency.symbol') . 10000; @endphp
@php $routeParams = ['simulation_id' => request('simulation_id'), 'quarter_id' => request('quarter_id') ]; @endphp

@extends('student.simulation.buy-reports.layout')
@section('title', $pageTitle)
@section('downloadlink', route('student.simulation.download.balance-sheet', $routeParams))
@section('reportTemplate')
  <div class="report-info">
    <h1>{{ $pageTitle }}</h1>
    <p>This research examines customer satisfaction scores for all firms, identifying how product quality, pricing, and
      service affect brand perception. It’s essential for aligning operations with customer expectations.</p>
    <p>Cost: {{ $price }}</p>
    <div class="download">
      <a href="{{ route('student.simulation.download.balance-sheet', $routeParams) }}">Buy Report</a>
    </div>
  </div>
@endsection
