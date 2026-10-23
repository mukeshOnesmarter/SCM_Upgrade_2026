@php $pageTitle = 'Benchmark - Earnings'; @endphp
@php $price = config('currency.symbol') . 500; @endphp
@php $routeParams = ['simulation_id' => request('simulation_id'), 'quarter_id' => request('quarter_id') ]; @endphp

@extends('student.simulation.buy-reports.layout')
@section('title', $pageTitle)
@section('reportTemplate')
  <div class="report-info">
    <h1>{{ $pageTitle }}</h1>
    <p>This report compares your firm’s overall profitability with competitors in the same industry. It highlights total
      revenue, expenses, and net income differences to show how effectively you convert sales into profit. Understanding
      where you stand in relation to others helps identify whether low profits stem from high costs, poor pricing, or weak
      demand. Use this study to monitor your progress over time and evaluate the financial impact of your strategic
      decisions.</p>
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
    <h5><img src="{{ asset('images/icon/excel.svg') }}" alt=""> Sample Output — Research Study (Benchmarking - Earnings)</h5>

    <table class="table table-striped">
      <thead class="thead-light">
        <tr>
          <th>Firm</th>
          <th class="text-right">Current Net Income</th>
          <th class="text-right">Cumulative Net Income</th>
          <th class="text-right">Current Dividends</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Firm 1</td>
          <td class="text-right">2,974,292</td>
          <td class="text-right">5,788,265</td>
          <td class="text-right">892,287</td>
        </tr>
        <tr>
          <td>Firm 2</td>
          <td class="text-right">3,472,461</td>
          <td class="text-right">6,234,171</td>
          <td class="text-right">1,041,738</td>
        </tr>
        <tr>
          <td colspan="4" class="text-center">… (other firms omitted for brevity) …</td>
        </tr>
      </tbody>
    </table>

    <!-- Financial market statistics table -->
    <h6 class="mt-4">Financial Market Statistics</h6>
    <table class="table table-bordered table-striped">
      <thead class="thead-light">
        <tr>
          <th>Metric</th>
          <th class="text-right">Firm 1</th>
          <th class="text-right">Firm 2</th>
          <th class="text-right">Firm 3</th>
          <th class="text-right">Firm 4</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Stock Price</td>
          <td class="text-right">120.00</td>
          <td class="text-right">131.80</td>
          <td class="text-right">117.63</td>
          <td class="text-right">123.96</td>
        </tr>
        <tr>
          <td>Shares (millions)</td>
          <td class="text-right">2.0M</td>
          <td class="text-right">2.0M</td>
          <td class="text-right">2.0M</td>
          <td class="text-right">2.0M</td>
        </tr>
        <tr>
          <td>EPS</td>
          <td class="text-right">1.49</td>
          <td class="text-right">1.74</td>
          <td class="text-right">1.44</td>
          <td class="text-right">1.57</td>
        </tr>
        <tr>
          <td>DPS</td>
          <td class="text-right">0.45</td>
          <td class="text-right">0.52</td>
          <td class="text-right">0.43</td>
          <td class="text-right">0.47</td>
        </tr>
        <tr>
          <td>Market Cap</td>
          <td class="text-right">240M</td>
          <td class="text-right">264M</td>
          <td class="text-right">235M</td>
          <td class="text-right">248M</td>
        </tr>
      </tbody>
    </table>
  </div>
@endsection
