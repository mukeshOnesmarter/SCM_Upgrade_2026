@php $pageTitle = 'Cash Flow Analysis Report'; @endphp
@php $routeParams = ['simulation_id' => request('simulation_id'), 'quarter_id' => request('quarter_id') ]; @endphp
@extends('student.simulation.financial-reports.layout')
@section('title', 'Cash Flow Analysis Report')
@section('downloadlink', route('student.simulation.download.cash-flow', $routeParams))
@section('reportTemplate')
  <div id="cashFlowDonut" style="height: 450px;"></div>
  <div class="cashFlowReport" id="cashFlowReport"> 
    <table class="tableData">
      <tbody>
        <tr>
          <td>Starting "Cash" Balance (Final "Cash" Balance, Q{{ $quarter_id - 1 }})</td>
          <td class="text-right">{{ number_format($data['starting_cash'], 0) }}</td>
        </tr>
        <tr>
          <td>+ Marketable Securities (Converted To "Cash" In Q{{ $quarter_id - 1 }})</td>
          <td class="text-right">{{ number_format($data['marketable_securities_converted'], 0) }}</td>
        </tr>
        <tr>
          <td>- "Loans" (Liquidated During Q{{ $quarter_id - 1 }})</td>
          <td class="text-right">{{ number_format($data['loans_repaid'], 0) }}</td>
        </tr>

        <tr>
          <td colspan="2" class="font-semibold pt-4">+ "Finished Goods Inventory" Changes</td>
        </tr>
        <tr>
          <td>Product Inventory Delta</td>
          <td class="text-right">{{ number_format($data['finished_goods_inventory_delta'], 0) }}</td>
        </tr>

        <tr>
          <td colspan="2" class="font-semibold pt-4">+ "Procurement Inventory" Changes</td>
        </tr>
        <tr>
          <td>Procurement Inventory Delta</td>
          <td class="text-right">{{ number_format($data['procurement_inventory_delta'], 0) }}</td>
        </tr>

        <tr>
          <td class="pt-4">+ "Plant Investment" Changes</td>
          <td class="text-right pt-4">{{ number_format($data['plant_investment_delta'], 0) }}</td>
        </tr>
        <tr>
          <td>+ "Net Income"
            <x-info-popover title="What Is Net Income ?" chapter="11" route="{{ route('student.manual.chapter11') }}">
              Initial funding provided by shareholders at the start of the simulation.
            </x-info-popover>
          </td>
          <td class="text-right">{{ number_format($data['net_income'], 0) }}</td>
        </tr>
        <tr class="border-t">
          <td>= Preliminary "Cash" Balance</td>
          <td class="text-right font-semibold">{{ number_format($data['preliminary_cash'], 0) }}</td>
        </tr>

        <tr>
          <td>- "Dividends" (Paid at End of Q{{ $quarter_id }})</td>
          <td class="text-right">{{ number_format($data['dividends'], 0) }}</td>
        </tr>
        <tr class="border-t">
          <td>= Actual "Cash" Balance (End of Q{{ $quarter_id }})</td>
          <td class="text-right font-semibold">{{ number_format($data['actual_cash'], 0) }}</td>
        </tr>

        <tr>
          <td>- Operating "Cash" Excess (To "Marketable Securities")</td>
          <td class="text-right">{{ number_format($data['cash_excess'], 0) }}</td>
        </tr>
        <tr>
          <td>+ Operating "Cash" Deficit (From "Loans")
            <x-info-popover title="What Is Operating Cash Deficit ?" chapter="11"
              route="{{ route('student.manual.chapter11') }}">
              If cash falls below a target threshold, a loan is taken to restore liquidity.
            </x-info-popover>
          </td>
          <td class="text-right">{{ number_format($data['cash_deficit'], 0) }}</td>
        </tr>

        <tr class="border-t">
          <td>= Final "Cash" Balance (End of Q{{ $quarter_id }})</td>
          <td class="text-right font-bold text-green-700">{{ number_format($data['final_cash'], 0) }}</td>
        </tr>
      </tbody>
    </table>

    <div class="note">
      <p>(1) "Marketable Securities" and "Loans" refer to values from last quarter's balance sheet.</p>
      <p>(2) Inventory and investment changes can be positive (cash outflow), negative (cash inflow), or zero.</p>
      <p>(3) Only one of "Operating Cash Excess" or "Deficit" will be non-zero. Cash is auto-balanced between 5% and 10%
        of revenue.</p>
    </div>
  </div>
  @if ($reportAnalysis && $reportAnalysis->response)
    <div class="reportAnalysis">
      <div class="headingSection">
        <img src="{{ asset('images/ChatGPT-Logo.png') }}" alt="">
        <h2>Quarter {{ request('quarter_id') }} - Cash Flow Analysis & Insights</h2>
        <p>Tracking cash inflows and outflows to evaluate liquidity, operational efficiency, and investment capacity.</p>
      </div>
      {!! $reportAnalysis->response !!}
    </div>
  @endif
@endsection

@section('templateScript')
<script src="https://code.highcharts.com/highcharts.js"></script>

<script>
Highcharts.chart('cashFlowDonut', {
    chart: {
        type: 'pie'
    },
    title: {
        text: 'Quarter {{ $quarter_id }} Cash Flow Overview'
    },
    exporting: {
        enabled: false // removes the navigation/export icon
    },
    credits: {
        enabled: false // removes "Highcharts.com" credit
    },
    plotOptions: {
        pie: {
            innerSize: '50%',
            dataLabels: {
                enabled: true,
                format: '<b>{point.name}</b>: ${point.y:,.0f}', // format with comma separators
                style: { fontSize: '12px' }
            }
        }
    },
    tooltip: {
        pointFormat: '{series.name}: <b>${point.y:,.0f}</b>'
    },
    series: [{
        name: 'Amount',
        data: [
    { name: 'Starting "Cash" Balance', y: {{ $data['starting_cash'] }}, color: '#4e79a7' },       // blue
    { name: 'Marketable Securities', y: {{ $data['marketable_securities_converted'] }}, color: '#f28e2b' }, // orange
    { name: 'Loans', y: {{ $data['loans_repaid'] }}, color: '#e15759' },                           // red
    { name: 'Finished Goods Inventory Δ', y: {{ $data['finished_goods_inventory_delta'] }}, color: '#76b7b2' }, // teal
    { name: 'Procurement Inventory Δ', y: {{ $data['procurement_inventory_delta'] }}, color: '#59a14f' },     // green
    { name: 'Plant Investment Δ', y: {{ $data['plant_investment_delta'] }}, color: '#9b59b6' },               // purple
    { name: 'Net Income', y: {{ $data['net_income'] }}, color: '#2ecc71' },                                  // green highlight
    { name: 'Preliminary "Cash" Balance', y: {{ $data['preliminary_cash'] }}, color: '#f39c12' },            // orange highlight
    { name: 'Dividends (Paid at End of Q{{ $quarter_id }})', y: {{ $data['dividends'] }}, color: '#f1c40f' }, // yellow
    { name: 'Actual "Cash" Balance', y: {{ $data['actual_cash'] }}, color: '#4e79a7' },                       // blue repeat
    { name: 'Operating "Cash" Excess (To Securities)', y: {{ $data['cash_excess'] }}, color: '#f28e2b' },    // orange repeat
    { name: 'Operating "Cash" Deficit (From Loans)', y: {{ $data['cash_deficit'] }}, color: '#e15759' },    // red repeat
    { name: 'Final "Cash" Balance', y: {{ $data['final_cash'] }}, color: '#76b7b2' }                        // teal repeat
]
    }]
});
</script>
@endsection
