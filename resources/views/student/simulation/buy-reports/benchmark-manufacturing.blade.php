@php $pageTitle = 'Benchmark - Manufacturing'; @endphp
@php $price = config('currency.symbol') . 5000; @endphp
@php $routeParams = ['simulation_id' => request('simulation_id'), 'quarter_id' => request('quarter_id') ]; @endphp

@extends('student.simulation.buy-reports.layout')
@section('title', $pageTitle)
@section('downloadlink', route('student.simulation.download.balance-sheet', $routeParams))
@section('reportTemplate')
  <div class="report-info">
    <h1>{{ $pageTitle }}</h1>
    <p>This study evaluates manufacturing performance, focusing on production efficiency, cost control, and quality
      outcomes. It highlights which competitors operate most efficiently and identifies areas where your processes can be
      optimized for productivity and lower unit costs.</p>
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
          <button>Buy Report</button>
        </form>
      @endif
    </div>
  </div>

  <div class="report-sample">
    <h5><img src="{{ asset('images/icon/excel.svg') }}" alt=""> Sample Output — Research Study (Benchmarking - Manufacturing)</h5>

    <table class="table table-striped">
      <thead class="thead-light">
        <tr>
          <th>Firm</th>
          <th class="text-right">Postponed Production</th>
          <th class="text-right">Regular Production</th>
          <th class="text-right">Emergency Production</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Firm 1</td>
          <td class="text-right">18.6%</td>
          <td class="text-right">74.3%</td>
          <td class="text-right">7.2%</td>
        </tr>
        <tr>
          <td>Firm 2</td>
          <td class="text-right">0.0%</td>
          <td class="text-right">100.0%</td>
          <td class="text-right">0.0%</td>
        </tr>
        <tr>
          <td colspan="4" class="text-center">… (other firms omitted for brevity) …</td>
        </tr>
      </tbody>
    </table>
  </div>
@endsection
