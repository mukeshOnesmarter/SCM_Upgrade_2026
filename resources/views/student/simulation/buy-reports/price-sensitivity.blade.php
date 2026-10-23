@php $pageTitle = 'Price Sensitivity Analysis'; @endphp
@php $price = config('currency.symbol') . 20000; @endphp
@php $routeParams = ['simulation_id' => request('simulation_id'), 'quarter_id' => request('quarter_id') ]; @endphp

@extends('student.simulation.buy-reports.layout')
@section('title', $pageTitle)
@section('downloadlink', route('student.simulation.download.balance-sheet', $routeParams))
@section('reportTemplate')
  <div class="report-info">
    <h1>{{ $pageTitle }}</h1>
    <p>This advanced study measures how customers respond to changes in price for each product, region, and channel. It
      helps determine optimal pricing strategies to balance demand and profitability.</p>
    <p>Cost: {{ $price }} per product per region per channel</p>
  </div>
@endsection
