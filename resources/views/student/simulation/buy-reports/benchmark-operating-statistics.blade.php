@php $pageTitle = 'Benchmark - Operating Statistics'; @endphp
@php $price = config('currency.symbol') . 2500; @endphp
@php $routeParams = ['simulation_id' => request('simulation_id'), 'quarter_id' => request('quarter_id') ]; @endphp

@extends('student.simulation.buy-reports.layout')
@section('title', $pageTitle)
@section('downloadlink', route('student.simulation.download.balance-sheet', $routeParams))
@section('reportTemplate')
  <div class="report-info">
    <h1>{{ $pageTitle }}</h1>
    <p>This study presents detailed performance metrics such as capacity utilization, efficiency ratios, and throughput.
      Comparing these indicators across firms helps pinpoint operational strengths and weaknesses in your production and
      supply chain systems.</p>
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
    <h5><img src="{{ asset('images/icon/excel.svg') }}" alt=""> Sample Output — Research Study (Benchmarking - Operating Statistics)</h5>
    <h6>P&amp;L Operating Statistics</h6>

    <table class="table table-striped">
      <thead class="thead-light">
        <tr>
          <th>Metric</th>
          <th class="text-right">Firm 2</th>
          <th class="text-right">Minimum</th>
          <th class="text-right">Average</th>
          <th class="text-right">Maximum</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Revenues</td>
          <td class="text-right">100.0%</td>
          <td class="text-right">100.0%</td>
          <td class="text-right">100.0%</td>
          <td class="text-right">100.0%</td>
        </tr>
        <tr>
          <td>Product Costs</td>
          <td class="text-right">50.1%</td>
          <td class="text-right">44.3%</td>
          <td class="text-right">49.1%</td>
          <td class="text-right">50.7%</td>
        </tr>
        <tr>
          <td>Replacement Parts</td>
          <td class="text-right">0.6%</td>
          <td class="text-right">0.5%</td>
          <td class="text-right">0.6%</td>
          <td class="text-right">0.7%</td>
        </tr>
        <tr>
          <td>Transportation Costs</td>
          <td class="text-right">10.2%</td>
          <td class="text-right">8.0%</td>
          <td class="text-right">9.7%</td>
          <td class="text-right">10.5%</td>
        </tr>
        <tr>
          <td>Duties &amp; Tariffs</td>
          <td class="text-right">7.9%</td>
          <td class="text-right">7.0%</td>
          <td class="text-right">8.0%</td>
          <td class="text-right">8.9%</td>
        </tr>
        <tr>
          <td>Gross Margin</td>
          <td class="text-right">30.5%</td>
          <td class="text-right">30.5%</td>
          <td class="text-right">32.6%</td>
          <td class="text-right">38.2%</td>
        </tr>
        <tr>
          <td>Administrative O/H</td>
          <td class="text-right">5.7%</td>
          <td class="text-right">5.6%</td>
          <td class="text-right">5.6%</td>
          <td class="text-right">6.0%</td>
        </tr>
        <tr>
          <td>Marketing</td>
          <td class="text-right">4.5%</td>
          <td class="text-right">3.8%</td>
          <td class="text-right">4.7%</td>
          <td class="text-right">6.0%</td>
        </tr>
        <tr>
          <td>Service</td>
          <td class="text-right">4.7%</td>
          <td class="text-right">3.6%</td>
          <td class="text-right">4.5%</td>
          <td class="text-right">4.9%</td>
        </tr>
        <tr>
          <td>Total Fixed Costs</td>
          <td class="text-right">25.7%</td>
          <td class="text-right">22.0%</td>
          <td class="text-right">24.9%</td>
          <td class="text-right">27.2%</td>
        </tr>
        <tr>
          <td>Operating Income</td>
          <td class="text-right">4.8%</td>
          <td class="text-right">4.8%</td>
          <td class="text-right">7.8%</td>
          <td class="text-right">13.7%</td>
        </tr>
        <tr>
          <td>Net Income</td>
          <td class="text-right">2.9%</td>
          <td class="text-right">2.9%</td>
          <td class="text-right">4.4%</td>
          <td class="text-right">7.3%</td>
        </tr>
      </tbody>
    </table>

    <h6>CSR Salary — Region 1</h6>
    <table class="table table-striped">
      <thead class="thead-light">
        <tr>
          <th>Firm 2</th>
          <th class="text-right">Minimum</th>
          <th class="text-right">Average</th>
          <th class="text-right">Maximum</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="text-right">2,000</td>
          <td class="text-right">1,850</td>
          <td class="text-right">1,956</td>
          <td class="text-right">2,125</td>
        </tr>
      </tbody>
    </table>

    <h6>CSR Calls Statistics — Region 1</h6>
    <table class="table table-striped">
      <thead class="thead-light">
        <tr>
          <th>Firm 2</th>
          <th class="text-right">Minimum</th>
          <th class="text-right">Average</th>
          <th class="text-right">Maximum</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="text-right">21,059</td>
          <td class="text-right">19,107</td>
          <td class="text-right">19,964</td>
          <td class="text-right">21,059</td>
        </tr>
      </tbody>
    </table>

    <h6>CSR $/Call Statistics — Region 1</h6>
    <table class="table table-striped">
      <thead class="thead-light">
        <tr>
          <th>Firm 2</th>
          <th class="text-right">Minimum</th>
          <th class="text-right">Average</th>
          <th class="text-right">Maximum</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="text-right">10.73</td>
          <td class="text-right">10.73</td>
          <td class="text-right">11.57</td>
          <td class="text-right">12.99</td>
        </tr>
      </tbody>
    </table>
  </div>
@endsection
