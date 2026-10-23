@php $pageTitle = 'Other Decision Variables Report'; @endphp
@php $routeParams = ['simulation_id' => request('simulation_id'), 'quarter_id' => request('quarter_id') ]; @endphp
@extends('student.simulation.financial-reports.layout')
@section('title', 'Other Decision Variables Report')
@section('downloadlink', route('student.simulation.download.decision-vars', $routeParams))
@section('reportTemplate')
  <div class="procurementInventoryChart">
    <div id="manufacturingChart"></div>
    <div id="subAssemblyChart" style="height: 500px"></div>
  </div>
  <div class="decisionVars">
    {{-- RAW MATERIALS --}}
    <div class="decisionSection">
      <div class="sectionheader">
        <label for="title">Procurement - Raw Materials</label>
        <div class="headerContainer">
          @foreach ($procurementRawMaterial as $component)
            <div class="item">{{ $component->name }}</div>
          @endforeach
        </div>
      </div>
      <div class="labelValueGroup">
        <div class="labelValue">
          <label>Manufacturing Plant & DC1</label>
          <div class="manufacturing">
            @foreach ($procurementRawMaterial as $component)
              <div class="item">{{ number_format($component->quantity, 0) }}</div>
            @endforeach
          </div>


        </div>
      </div>
    </div>
    {{-- SUB ASSEMBLY  --}}
    <div class="decisionSection">
      <div class="sectionheader">
        <label for="title">Procurement - Sub-Assembly Components</label>
      </div>
      <div class="dcCenterContainer">
        <div class="supplierHeader">
          @foreach ($suppliers as $item)
            <div class="supplier"> Supplier <small> ({{ $item->name }})</small> </div>
          @endforeach
        </div>
        @foreach ($procurementSubAssembly as $item)
          <div class="dcCenter">
            <label for="title">DC{{ $item->region_id }}</label>
            <div class="subAssembly">
              @foreach ($item->subAssembly as $component)
                <div class="components">
                  <label for="subtitle">{{ $component->name }}</label>
                  <div class="inventry">
                    @foreach ($component->suppliers as $supplier)
                      <div class="item">{{ number_format($supplier->procurement, 0) }}</div>
                    @endforeach
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        @endforeach
      </div>
    </div>
    {{-- MANUFACTURING --}}
    <div class="decisionSection">
      <div class="sectionheader">
        <label for="title">Manufacturing</label>
        <div class="headerContainer">
          <div class="item">Product 0</div>
          <div class="item">Product 1</div>
          <div class="item">Product 2</div>
        </div>
      </div>
      <div class="labelValueGroup">
        @foreach ($manufacturing as $key => $productManufacturing)
          <div class="labelValue">
            <label>{{ ucwords(str_replace('_', ' ', $key)) }} </label>
            <div class="manufacturing">
              <div class="item">{{ number_format($productManufacturing->product_0, 0) }}</div>
              <div class="item">{{ number_format($productManufacturing->product_1, 0) }}</div>
              <div class="item">{{ number_format($productManufacturing->product_2, 0) }}</div>
            </div>
          </div>
        @endforeach
      </div>
    </div>

    {{-- TRANSPORTATION --}}
    <div class="decisionSection">
      <div class="sectionheader">
        <label for="title">Transportation - Plant/DC1 Shipments To Other DCs</label>
        <div class="headerContainer">
          <div class="item">Product 0</div>
          <div class="item">Product 1</div>
          <div class="item">Product 2</div>
        </div>
      </div>
      <div class="dcCenterContainer">
        @foreach ($shipments as $item)
          <div class="dcCenter">
            <label for="title">DC{{ $item->distribution_center_id }}</label>
            <div class="carrierContainer">
              @foreach ($item->carriers as $carrier)
                <div class="carrier">
                  <label for="subtitle">Carrier {{ $carrier->name }}</label>
                  <div class="inventry">
                    @foreach ($carrier->products as $quantity)
                      <div class="item">{{ number_format($quantity, 0) }}</div>
                    @endforeach
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        @endforeach
      </div>
    </div>
    {{-- INFORMATION TECHNOLOGY --}}
    <div class="decisionSection" style="display: none">
      <div class="sectionheader">
        <label for="title">Information Technology</label>
      </div>
      <div class="labelValueGroup">
        <div class="labelValue">
          <label>IT Synchronization With Carriers?</label>
          <strong>0</strong>
        </div>
        <div class="labelValue">
          <label>IT Synchronization With Suppliers? </label>
          <strong>0</strong>
        </div>
        <div class="labelValue">
          <label>Procurement Transactions Report?</label>
          <strong>0</strong>
        </div>
        <div class="labelValue">
          <label>Product Cost Report?</label>
          <strong>0</strong>
        </div>
        <div class="labelValue">
          <label>Replacement Parts Demand Report?</label>
          <strong>0</strong>
        </div>
        <div class="labelValue">
          <label>Retail Pipeline Report?</label>
          <strong>0</strong>
        </div>
        <div class="labelValue">
          <label>Transportation Cost Report?</label>
          <strong>0</strong>
        </div>
        <div class="labelValue">
          <label>Transportation Report?</label>
          <strong>0</strong>
        </div>
      </div>
    </div>
  </div>
  @if ($reportAnalysis && $reportAnalysis->response)
    <div class="reportAnalysis">
      <div class="headingSection">
        <img src="{{ asset('images/ChatGPT-Logo.png') }}" alt="">
        <h2>Quarter {{ request('quarter_id') }} - Decision Variables Analysis & Insights</h2>
        <p>Reviewing strategic and operational variables impacting overall business performance.</p>
      </div>
      {!! $reportAnalysis->response !!}
    </div>
  @endif
@endsection

@php
  $prod1_regular = 0;
  $prod2_regular = 0;
  $prod1_emergency = 0;
  $prod2_emergency = 0;
  $prod1_flex = 0;
  $prod2_flex = 0;

  foreach ($manufacturing as $m) {
      $prod1_regular += $m->regular_production->product_1 ?? 0;
      $prod2_regular += $m->regular_production->product_2 ?? 0;

      $prod1_emergency += $m->emergency_production->product_1 ?? 0;
      $prod2_emergency += $m->emergency_production->product_2 ?? 0;

      $prod1_flex += $m->production_flexibility->product_1 ?? 0;
      $prod2_flex += $m->production_flexibility->product_2 ?? 0;
  }
@endphp
@section('templateScript')
  <script src="https://code.highcharts.com/highcharts.js"></script>
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      Highcharts.chart('manufacturingChart', {
        chart: {
          type: 'column'
        },
        title: {
          text: 'Manufacturing Report'
        },
        xAxis: {
          categories: ['Product 1', 'Product 2'],
          crosshair: true
        },
        yAxis: {
          min: 0,
          title: {
            text: 'Units'
          }
        },
        tooltip: {
          shared: true
        },
        plotOptions: {
          column: {
            grouping: true,
            pointPadding: 0.1,
            borderWidth: 0
          }
        },
        series: [{
            name: 'Regular Production',
            data: [
              {{ $manufacturing->Production->product_1 ?? 0 }},
              {{ $manufacturing->Production->product_2 ?? 0 }}
            ]
          },
          {
            name: 'Emergency Production',
            data: [
              {{ $manufacturing->emergency_production->product_1 ?? 0 }},
              {{ $manufacturing->emergency_production->product_2 ?? 0 }}
            ]
          },
          {
            name: 'Production Flexibility',
            data: [
              {{ $manufacturing->production_flexibility->product_1 ?? 0 }},
              {{ $manufacturing->production_flexibility->product_2 ?? 0 }}
            ]
          }
        ]
      });
    });
  </script>
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      Highcharts.chart('subAssemblyChart', {
        chart: {
          type: 'pie'
        },
        title: {
          text: 'Procurement - Sub-Assembly Components'
        },
        plotOptions: {
          pie: {
            innerSize: '50%', // donut style
            dataLabels: {
              enabled: true,
              format: '{point.name}: {point.y}'
            }
          }
        },
        series: [{
          name: 'Units',
          colorByPoint: true,
          data: [
            @foreach ($procurementSubAssembly as $sa)
              @foreach ($sa->subAssembly as $comp)
                @foreach ($comp->suppliers as $sup)
                  @if (!empty($sup->procurement) && $sup->procurement > 0)
                    {
                      name: "{{ $comp->name }} ({{ $sup->name }})",
                      y: {{ $sup->procurement }}
                    },
                  @endif
                @endforeach
              @endforeach
            @endforeach
          ]
        }]
      });
    });
  </script> 
@endsection
