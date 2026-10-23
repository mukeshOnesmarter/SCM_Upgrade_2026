@php $pageTitle = 'Balance Sheet / ASSETS'; @endphp
@php $routeParams = ['simulation_id' => request('simulation_id'), 'quarter_id' => request('quarter_id') ]; @endphp
@extends('student.simulation.financial-reports.layout')
@section('title', 'Balance Sheet')
@section('downloadlink', route('student.simulation.download.balance-sheet', $routeParams))
@section('reportTemplate')
  <div class="balanceSheetReport" id="balanceSheetReport">
    <div id="balanceSheetChart" style="height: 500px;"></div>
    <div class="tableDataContainer">
      <h4>Assets</h4>
      <table class="tableData">
        <thead class="thead-dark">
          <tr>
            <th>Type</th>
            <th align="right" style="text-align: right">Amount ({{ config('currency.symbol') }})</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($data['assets'] as $key => $value)
            <tr>
              <td>{{ $key }}</td>
              <td align="right">{{ number_format($value, 2) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>

      @if (!empty($data['assets_breakdown']['Finished Goods Inventory']))
        <h5>Finished Goods Inventory Breakdown</h5>
        <table class="tableData">
          <thead>
            <tr>
              <th align="center" width="80">Region</th>
              <th>Product Name</th>
              <th width="120">Quantity</th>
              <th align="right" width="120" style="text-align: right">Cost / Unit ({{ config('currency.symbol') }})
              </th>
            </tr>
          </thead>
          <tbody>
            @foreach ($data['assets_breakdown']['Finished Goods Inventory'] as $fg)
              @foreach ($fg->products as $product)
                <tr>
                  <td align="center">{{ $fg->region_id }}</td>
                  <td>Product {{ $product->product_id }}: {{ $product->product_name }}</td>
                  <td>{{ $product->quantity }}</td>
                  <td align="right">{{ number_format($product->product_cost, 2) }}</td>
                </tr>
              @endforeach
            @endforeach
          </tbody>
        </table>
      @endif

      @if (!empty($data['assets_breakdown']['Procurement Inventory']))
        <h5>Procurement Inventory Breakdown</h5>
        <table class="tableData">
          <thead>
            <tr>
              <th align="center" width="80">Region</th>
              <th>Component Name</th>
              <th width="120">Quantity</th>
              <th align="right" width="120" style="text-align: right">Cost / Unit ({{ config('currency.symbol') }})
              </th>
            </tr>
          </thead>
          <tbody>
            @foreach ($data['assets_breakdown']['Procurement Inventory'] as $proc)
              @foreach ($proc->procurement as $component)
                <tr>
                  <td align="center">{{ $proc->region_id }}</td>
                  <td>{{ $component->name }}</td>
                  <td>{{ $component->units }}</td>
                  <td align="right">{{ number_format($component->cost, 2) }}</td>
                </tr>
              @endforeach
            @endforeach
          </tbody>
        </table>
      @endif
    </div>

    <div class="tableDataContainer">
      <h4>Liabilities & Equities
        <x-info-popover title="What Is Liabilities & Equities ?" chapter="11"
          route="{{ route('student.manual.chapter11') }}">
          Initial funding provided by shareholders at the start of the simulation.
        </x-info-popover>
      </h4>
      <table class="tableData">
        <thead class="thead-dark">
          <tr>
            <th>Type</th>
            <th align="right" style="text-align: right">Amount ({{ config('currency.symbol') }})</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($data['liabilities_and_equities'] as $key => $value)
            <tr>
              <td>{{ $key }}</td>
              <td align="right">{{ number_format($value, 2) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    @if (!empty($data['notes']))
      <div>
        <h5>Notes</h5>
        <ul>
          @foreach ($data['notes'] as $note)
            <li>{{ $note }}</li>
          @endforeach
        </ul>
      </div>
    @endif
  </div>
  @if ($reportAnalysis && $reportAnalysis->response)
    <div class="reportAnalysis">
      <div class="headingSection"> 
        <img src="{{ asset('images/ChatGPT-Logo.png')}}" alt="">
        <h2>Quarter {{ request('quarter_id') }} - Balance Sheet & Asset Overview</h2> 
        <p>Assessing the company’s financial position through assets, liabilities, and equity analysis.</p>
      </div>
      {!! $reportAnalysis->response !!}
    </div>
  @endif 
@endsection

@section('templateScript')
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/exporting.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // Prepare dynamic categories
    let assetKeys = @json(array_keys($data['assets']));
    let liabilityKeys = @json(array_keys($data['liabilities_and_equities']));

    // Merge categories (Assets first, then Liabilities & Equities)
    let categories = [...assetKeys, ...liabilityKeys];

    // Prepare values for Assets series (null for Liabilities categories)
    let assetValues = [
        ...Object.values(@json($data['assets'])),
        ...Array(liabilityKeys.length).fill(null)
    ];

    // Prepare values for Liabilities series (null for Asset categories)
    let liabilityValues = [
        ...Array(assetKeys.length).fill(null),
        ...Object.values(@json($data['liabilities_and_equities']))
    ];

    Highcharts.chart('balanceSheetChart', {
        chart: {
            type: 'column'
        },
        title: {
            text: 'Balance Sheet - Quarter {{ request("quarter_id") }}'
        },
        xAxis: {
            categories: categories,
            crosshair: true
        },
        yAxis: [{
            // Left axis for Assets
            title: {
                text: 'Assets ({{ config("currency.symbol") }})'
            },
            opposite: false
        }, {
            // Right axis for Liabilities & Equities
            title: {
                text: 'Liabilities & Equities ({{ config("currency.symbol") }})'
            },
            opposite: true
        }],
        tooltip: {
            shared: true
        },
        plotOptions: {
            column: {
                grouping: false,
                shadow: false,
                borderWidth: 0
            }
        },
        series: [{
            name: 'Assets',
            data: assetValues,
            yAxis: 0,
            color: '#2b908f',
            pointPadding: 0.1
        }, {
            name: 'Liabilities & Equities',
            data: liabilityValues,
            yAxis: 1,
            color: '#90ee7e',
            pointPadding: 0.3
        }]
    });
});
</script>
@endsection