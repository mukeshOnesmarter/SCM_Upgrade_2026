@php $pageTitle = 'Benchmark - Distribution'; @endphp
@php $price = config('currency.symbol') . 5000; @endphp
@php $routeParams = ['simulation_id' => request('simulation_id'), 'quarter_id' => request('quarter_id') ]; @endphp

@extends('student.simulation.buy-reports.layout')
@section('title', $pageTitle)
@section('downloadlink', route('student.simulation.download.balance-sheet', $routeParams))
@section('reportTemplate')
  <div class="report-info">
    <h1>{{ $pageTitle }}</h1>
    <p>The distribution benchmarking report compares the logistics performance of all firms. It highlights differences in
      warehousing, transportation, and delivery efficiency. Understanding these comparisons helps you refine your
      distribution network to lower costs and improve customer service.</p>
    <p>Cost: {{ $price }}</p>

    <div class="download">
      @if ($hasPurchased)
        <div>This report has already been purchased for Quarter {{ $quarter }}.</div>
      @else
        <form action="{{ route('student.simulation.buyreports.buyConsultingReport', $routeParams) }}" method="POST">
          @csrf
          <input type="hidden" name="simulation_id" value="{{ $simulation_id }}">
          <input type="hidden" name="quarter" value="{{ $quarter }}">
          <input type="hidden" name="consulting_fee_id" value="{{ $consulting_fee_id }}">
          <button class="btn btn-primary btn-sm">Buy Report</button>
        </form>
      @endif
    </div>
  </div>

  <div class="report-sample">
    <h5><img src="{{ asset('images/icon/excel.svg') }}" alt=""> Sample Output — Research Study (Benchmarking - Distribution)</h5>

    <h6>Distribution Centers (DCs) by Region</h6>
    <table class="table table-striped mb-4">
      <thead class="thead-light">
        <tr>
          <th>Firm</th>
          <th class="text-center">Region 1</th>
          <th class="text-center">Region 2</th>
          <th class="text-center">Region 3</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Firm 1 DCs?</td>
          <td class="text-center">Yes</td>
          <td class="text-center">Yes</td>
          <td class="text-center">Yes</td>
        </tr>
        <tr>
          <td>Firm 2 DCs?</td>
          <td class="text-center">Yes</td>
          <td class="text-center">Yes</td>
          <td class="text-center">No</td>
        </tr>
        <tr>
          <td colspan="4" class="text-center">… (other firms omitted for brevity) …</td>
        </tr>
      </tbody>
    </table>

    <h6>Postponed Production by Region</h6>
    <table class="table table-striped">
      <thead class="thead-light">
        <tr>
          <th>Firm</th>
          <th class="text-center">Region 1</th>
          <th class="text-center">Region 2</th>
          <th class="text-center">Region 3</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Firm 1 Postponed?</td>
          <td class="text-center">Yes</td>
          <td class="text-center">No</td>
          <td class="text-center">Yes</td>
        </tr>
        <tr>
          <td>Firm 2 Postponed?</td>
          <td class="text-center">No</td>
          <td class="text-center">No</td>
          <td class="text-center">No</td>
        </tr>
        <tr>
          <td colspan="4" class="text-center">… (other firms omitted for brevity) …</td>
        </tr>
      </tbody>
    </table>
  </div>
@endsection
