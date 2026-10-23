@php $pageTitle = 'Benchmark - Procurement'; @endphp
@php $price = config('currency.symbol') . 3000; @endphp
@php $routeParams = ['simulation_id' => request('simulation_id'), 'quarter_id' => request('quarter_id') ]; @endphp

@extends('student.simulation.buy-reports.layout')
@section('title', $pageTitle)
@section('downloadlink', route('student.simulation.download.balance-sheet', $routeParams))
@section('reportTemplate')
  <div class="report-info">
    <h1>{{ $pageTitle }}</h1>
    <p>This report analyzes procurement efficiency across all firms. It provides insights into supplier selection,
      purchasing costs, and material management. Comparing your procurement strategies to industry leaders helps improve
      sourcing reliability, reduce input costs, and strengthen supplier relationships.</p>
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
    <h5><img src="{{ asset('images/icon/excel.svg') }}" alt=""> Sample Output — Research Study (Benchmarking - Procurement)</h5>
    <h6>Sub-Assembly Component Suppliers</h6>

    <table class="table table-striped">
      <thead class="thead-light">
        <tr>
          <th>Firm</th>
          <th>Suppliers</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Firm 1</td>
          <td>A, B, D</td>
        </tr>
        <tr>
          <td>Firm 2</td>
          <td>D, G</td>
        </tr>
        <tr>
          <td>Firm 3</td>
          <td>D</td>
        </tr>
        <tr>
          <td colspan="2" class="text-center">… (other firms omitted for brevity) …</td>
        </tr>
      </tbody>
    </table>

    <h6 class="mt-4">SAC Procurements: Market Shares for Suppliers A–G</h6>
    <table class="table table-striped">
      <thead class="thead-light">
        <tr>
          <th>Supplier</th>
          <th class="text-right">A</th>
          <th class="text-right">B</th>
          <th class="text-right">C</th>
          <th class="text-right">D</th>
          <th class="text-right">E</th>
          <th class="text-right">F</th>
          <th class="text-right">G</th>
          <th class="text-right">Total</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Audio Modal</td>
          <td class="text-right">28.0%</td>
          <td class="text-right">25.3%</td>
          <td class="text-right">46.6%</td>
          <td class="text-right">0.0%</td>
          <td class="text-right">–</td>
          <td class="text-right">–</td>
          <td class="text-right">–</td>
          <td class="text-right">100.0%</td>
        </tr>
        <tr>
          <td>Control Interface</td>
          <td class="text-right">9.4%</td>
          <td class="text-right">12.8%</td>
          <td class="text-right">0.0%</td>
          <td class="text-right">38.4%</td>
          <td class="text-right">39.4%</td>
          <td class="text-right">–</td>
          <td class="text-right">–</td>
          <td class="text-right">100.0%</td>
        </tr>
        <tr>
          <td>Motherboard</td>
          <td class="text-right">–</td>
          <td class="text-right">12.0%</td>
          <td class="text-right">35.7%</td>
          <td class="text-right">33.4%</td>
          <td class="text-right">18.9%</td>
          <td class="text-right">–</td>
          <td class="text-right">–</td>
          <td class="text-right">100.0%</td>
        </tr>
      </tbody>
    </table>
  </div>
@endsection
