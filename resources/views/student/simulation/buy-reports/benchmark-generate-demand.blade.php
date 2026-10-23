@php $pageTitle = 'Benchmark - Generate Demand'; @endphp
@php $price = config('currency.symbol') . 5000; @endphp
@php $routeParams = ['simulation_id' => request('simulation_id'), 'quarter_id' => request('quarter_id') ]; @endphp

@extends('student.simulation.buy-reports.layout')
@section('title', $pageTitle)
@section('downloadlink', route('student.simulation.download.balance-sheet', $routeParams))
@section('reportTemplate')
  <div class="report-info">
    <h1>{{ $pageTitle }}</h1>
    <p>This report examines how effectively each firm’s marketing and sales activities generate customer demand. It
      compares
      advertising, pricing, and sales strategies to reveal what drives market success.</p>
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
    <h5><img src="{{ asset('images/icon/excel.svg') }}" alt=""> Sample Output — Research Study (Benchmarking - Generate Demand)</h5>
    <h6>SMART HOME ASSISTANT — Region 1 (min / ave / max)</h6>

    <table class="table table-striped mb-4">
      <thead class="thead-light">
        <tr>
          <th>Channel</th>
          <th>Metric</th>
          <th class="text-right">QTR 04</th>
          <th class="text-right">QTR 05</th>
          <th class="text-right">QTR 06</th>
          <th class="text-right">QTR 07</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td rowspan="2">Channel 1</td>
          <td>Price [$]</td>
          <td class="text-right">435 / 520 / 657</td>
          <td class="text-right">431 / 554 / 689</td>
          <td class="text-right">437 / 542 / 662</td>
          <td class="text-right">429 / 542 / 662</td>
        </tr>
        <tr>
          <td>Mktg [$K]</td>
          <td class="text-right">100 / 161 / 300</td>
          <td class="text-right">0 / 183 / 300</td>
          <td class="text-right">0 / 157 / 300</td>
          <td class="text-right">0 / 181 / 326</td>
        </tr>
        <tr>
          <td rowspan="2">Channel 2</td>
          <td>Price [$]</td>
          <td class="text-right">440 / 495 / 540</td>
          <td class="text-right">440 / 496 / 550</td>
          <td class="text-right">440 / 499 / 550</td>
          <td class="text-right">440 / 496 / 550</td>
        </tr>
        <tr>
          <td>Mktg [$K]</td>
          <td class="text-right">0 / 85 / 150</td>
          <td class="text-right">75 / 134 / 282</td>
          <td class="text-right">0 / 139 / 299</td>
          <td class="text-right">0 / 147 / 326</td>
        </tr>
      </tbody>
    </table>

    <h6>SMART THERMOSTAT — Region 1 (min / ave / max)</h6>
    <p class="text-muted">[Additional data continues in full report]</p>
  </div>
@endsection
