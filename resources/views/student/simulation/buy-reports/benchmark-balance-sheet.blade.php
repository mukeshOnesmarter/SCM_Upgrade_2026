@php $pageTitle = 'Benchmark - Balance Sheets'; @endphp
@php $price = config('currency.symbol') . 1000; @endphp
@php $routeParams = ['simulation_id' => request('simulation_id'), 'quarter_id' => request('quarter_id') ]; @endphp

@extends('student.simulation.buy-reports.layout')
@section('title', $pageTitle)
@section('downloadlink', route('student.simulation.download.balance-sheet', $routeParams))
@section('reportTemplate')
  <div class="report-info">
    <h1>{{ $pageTitle }}</h1>
    <p>This study provides a comparison of competitors’ financial positions through their balance sheets. It helps you
      understand how other firms manage assets, liabilities, and equity. Use this report to evaluate capital structure,
      liquidity, and debt management practices across your industry. Knowing where your firm stands financially helps
      guide
      decisions on investments, funding, and resource allocation.</p>
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
    <h5><img src="{{ asset('images/icon/excel.svg') }}" alt=""> Sample Output — Research Study (Benchmarking - Balance Sheets)</h5>
    <h6>Firm 2 Balance Sheet</h6>

    <table class="table table-striped">
      <thead class="thead-light">
        <tr>
          <th>ASSETS</th>
          <th class="text-right">Amount ($)</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Cash</td>
          <td class="text-right">1,686,016</td>
        </tr>
        <tr>
          <td>Marketable Securities</td>
          <td class="text-right">24,186,533</td>
        </tr>
        <tr>
          <td>Finished Goods and Postponed Production Inventory</td>
          <td class="text-right">25,661,228</td>
        </tr>
        <tr>
          <td>Plant Investment</td>
          <td class="text-right">50,000,000</td>
        </tr>
        <tr>
          <td>Procurement Inventories</td>
          <td class="text-right">1,398,909</td>
        </tr>
        <tr class="font-weight-bold">
          <td>Total Assets</td>
          <td class="text-right">102,932,686</td>
        </tr>
      </tbody>
    </table>

    <h6 class="mt-4">Liabilities and Equities</h6>
    <p class="text-muted">[Data continues in full report]</p>
  </div>

@endsection
