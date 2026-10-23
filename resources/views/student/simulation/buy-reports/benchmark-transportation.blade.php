@php $pageTitle = 'Benchmark - Transportation'; @endphp
@php $price = config('currency.symbol') . 5000; @endphp
@php $routeParams = ['simulation_id' => request('simulation_id'), 'quarter_id' => request('quarter_id') ]; @endphp

@extends('student.simulation.buy-reports.layout')
@section('title', $pageTitle)
@section('downloadlink', route('student.simulation.download.balance-sheet', $routeParams))
@section('reportTemplate')
  <div class="report-info">
    <h1>{{ $pageTitle }}</h1>
    <p>This study focuses on transportation operations across firms, comparing cost structures, route efficiency, and
      service performance. Use this information to identify potential cost savings, optimize logistics decisions, and
      enhance delivery reliability.</p>
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
    <h5><img src="{{ asset('images/icon/excel.svg') }}" alt=""> Sample Output — Research Study (Benchmarking - Transportation)</h5>
    <h6>Transportation Cost Distribution</h6>

    <table class="table table-bordered table-striped w-100 mb-4">
      <thead class="thead-light">
        <tr>
          <th>Firm</th>
          <th class="text-right">Raw Materials</th>
          <th class="text-right">Sub-Assembly Component Shipments</th>
          <th class="text-right">Plant-To-Customer Shipments</th>
          <th class="text-right">DC-To-Customer Shipments</th>
          <th class="text-right">Replace Parts Shipments</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Firm 1 Transportation $s</td>
          <td class="text-right">0.0%</td>
          <td class="text-right">33.5%</td>
          <td class="text-right">13.8%</td>
          <td class="text-right">50.2%</td>
          <td class="text-right">2.5%</td>
        </tr>
        <tr>
          <td>Firm 2 Transportation $s</td>
          <td class="text-right">0.0%</td>
          <td class="text-right">33.5%</td>
          <td class="text-right">13.5%</td>
          <td class="text-right">50.7%</td>
          <td class="text-right">2.3%</td>
        </tr>
        <tr>
          <td colspan="6" class="text-center">… (other firms omitted for brevity) …</td>
        </tr>
      </tbody>
    </table>

    <h6>Plant-To-DC Carriers</h6>
    <table class="table table-striped mb-4">
      <thead class="thead-light">
        <tr>
          <th>Firm</th>
          <th>Carriers</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Firm 1</td>
          <td>J</td>
        </tr>
        <tr>
          <td>Firm 2</td>
          <td>I, J, N</td>
        </tr>
        <tr>
          <td colspan="2" class="text-center">… (other firms omitted for brevity) …</td>
        </tr>
      </tbody>
    </table>

    <h6>Shipments: Market Shares for Carriers I–N</h6>
    <table class="table table-striped">
      <thead class="thead-light">
        <tr>
          <th>Region</th>
          <th class="text-right">I</th>
          <th class="text-right">J</th>
          <th class="text-right">K</th>
          <th class="text-right">L</th>
          <th class="text-right">M</th>
          <th class="text-right">N</th>
          <th class="text-right">Total</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Region 2</td>
          <td class="text-right">0.0%</td>
          <td class="text-right">0.0%</td>
          <td class="text-right">22.7%</td>
          <td class="text-right">14.4%</td>
          <td class="text-right">50.5%</td>
          <td class="text-right">12.4%</td>
          <td class="text-right">100.0%</td>
        </tr>
        <tr>
          <td>Region 3</td>
          <td class="text-right">20.9%</td>
          <td class="text-right">39.0%</td>
          <td class="text-right">10.3%</td>
          <td class="text-right">9.6%</td>
          <td class="text-right">20.2%</td>
          <td class="text-right">0.0%</td>
          <td class="text-right">100.0%</td>
        </tr>
      </tbody>
    </table>
  </div>
@endsection
