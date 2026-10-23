@php $pageTitle = 'FGI - Finished Goods Inventory Report'; @endphp
@php $routeParams = ['simulation_id' => request('simulation_id'), 'quarter_id' => request('quarter_id') ]; @endphp
@extends('student.simulation.financial-reports.layout')
@section('title', 'Finished Goods Inventory Report')
@section('downloadlink', route('student.simulation.download.finished-goods', $routeParams))
@section('reportTemplate')
  <div class="procurementInventoryChart">
    <div id="fgiInventoryDonut"></div>
    <div id="fgiProductionDonut"></div>
  </div>
  <div class="finishedGoods">
    {{-- Header --}}
    <div class="headerContainer">
      <div class="item">Product 0 <small>Product "SHELL"</small></div>
      <div class="item">Product 01 <small>SMART HOME ASSISTANT</small></div>
      <div class="item">Product 02 <small>SMART THERMOSTAT</small></div>
    </div>

    @foreach ($dcCenters as $item)
      <div class="distributionCenter">
        <div class="regionTitle">
          DC-{{ str_pad($item->dc_number, '2', '0', STR_PAD_LEFT) }}: Region ({{ $item->region->name }})
          <small>{{ $item->dc_number === 1 ? 'Home Region & Manufacturing Plant' : 'Distribution Center' }}</small>
        </div>
        <div class="labelValueGroup">
          <div class="labelValue">
            <label><span class="hide">*</span> Beginning Inventory</label>
            <div class="inventoryContainer">
              <div class="item">{{ number_format($item->beginning_inventory->product_0 ?? 0, 0) }}</div>
              <div class="item">{{ number_format($item->beginning_inventory->product_01 ?? 0, 0) }}</div>
              <div class="item">{{ number_format($item->beginning_inventory->product_02 ?? 0, 0) }}</div>
            </div>
          </div>
          {{-- shipmentContainer --}}
          @if ($item->dc_number !== 1 && $item->region_id !== 1)
            <div class="shipmentContainer">
              <label for="title"><span class="addition"><i class="fa fa-plus" aria-hidden="true"></i></span></span>
                Shipment</label>
              <div class="shipmentInventryContainer">
                <div class="shipmentInventryContainer">
                  <div class="shipment">
                    <label>Surface</label>
                    <div class="shipmentInventry">
                      <div class="item">{{ $item->shipment->surface->product_0 }}</div>
                      <div class="item">{{ $item->shipment->surface->product_01 }}</div>
                      <div class="item">{{ $item->shipment->surface->product_02 }}</div>
                    </div>
                  </div>
                  <div class="shipment">
                    <label>Air </label>
                    <div class="shipmentInventry">
                      <div class="item">{{ $item->shipment->air->product_0 }}</div>
                      <div class="item">{{ $item->shipment->air->product_01 }}</div>
                      <div class="item">{{ $item->shipment->air->product_02 }}</div>
                    </div>
                  </div>
                  <div class="shipment">
                    <label>Emergency </label>
                    <div class="shipmentInventry">
                      <div class="item">{{ $item->shipment->emergency->product_0 }}</div>
                      <div class="item">{{ $item->shipment->emergency->product_01 }}</div>
                      <div class="item">{{ $item->shipment->emergency->product_02 }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          @endif

          @if ($item->dc_number === 1 && $item->region_id === 1)
            <div class="labelValue">
              <label><span class="addition"><i class="fa fa-plus" aria-hidden="true"></i></span>
                Regular Production</label>
              <div class="inventoryContainer">
                <div class="item">{{ number_format($item->regular_production->product_0 ?? 0, 0) }}</div>
                <div class="item">{{ number_format($item->regular_production->product_01 ?? 0, 0) }}</div>
                <div class="item">{{ number_format($item->regular_production->product_02 ?? 0, 0) }}</div>
              </div>
            </div>
            <div class="labelValue">
              <label><span class="addition"><i class="fa fa-plus" aria-hidden="true"></i></span>
                Emergency Production</label>
              <div class="inventoryContainer">
                <div class="item">{{ number_format($item->emergency_production->product_0 ?? 0, 0) }}</div>
                <div class="item">{{ number_format($item->emergency_production->product_01 ?? 0, 0) }}</div>
                <div class="item">{{ number_format($item->emergency_production->product_02 ?? 0, 0) }}</div>
              </div>
            </div>
            <div class="labelValue">
              <label><span class="addition"><i class="fa fa-plus" aria-hidden="true"></i></span>
                Postponed Production
                <x-info-popover title="What Is Postponed Production ?" chapter="04"
                  route="{{ route('student.manual.chapter04') }}">
                  Inventory of unfinished Product 0 units used for flexible final assembly.
                </x-info-popover>
              </label>
              <div class="inventoryContainer">
                <div class="item">{{ number_format($item->postponed_production->product_0 ?? 0, 0) }}</div>
                <div class="item">{{ number_format($item->postponed_production->product_01 ?? 0, 0) }}</div>
                <div class="item">{{ number_format($item->postponed_production->product_02 ?? 0, 0) }}</div>
              </div>
            </div>
          @endif

          <div class="labelValue highlight">
            <label>Available Inventory</label>
            <div class="inventoryContainer">
              <div class="item">{{ number_format($item->available_inventory->product_0, 0) }}</div>
              <div class="item">{{ number_format($item->available_inventory->product_01, 0) }}</div>
              <div class="item">{{ number_format($item->available_inventory->product_02, 0) }}</div>
            </div>
          </div>

          {{-- shipmentContainer --}}
          @if ($item->dc_number === 1 && $item->region_id === 1)
            <div class="shipmentContainer">
              <label for="title"><span class="subtraction"><i class="fa fa-minus" aria-hidden="true"></i></span>
                Total Shipments – Includes Delayed Surface Shipments</label>
              <div class="shipmentInventryContainer">
                <div class="shipmentInventryContainer">
                  <div class="shipment">
                    <label>Surface</label>
                    <div class="shipmentInventry">
                      <div class="item">{{ $item->shipment->surface->product_0 }}</div>
                      <div class="item">{{ $item->shipment->surface->product_01 }}</div>
                      <div class="item">{{ $item->shipment->surface->product_02 }}</div>
                    </div>
                  </div>
                  <div class="shipment">
                    <label>Air </label>
                    <div class="shipmentInventry">
                      <div class="item">{{ $item->shipment->air->product_0 }}</div>
                      <div class="item">{{ $item->shipment->air->product_01 }}</div>
                      <div class="item">{{ $item->shipment->air->product_02 }}</div>
                    </div>
                  </div>
                  <div class="shipment">
                    <label>Emergency </label>
                    <div class="shipmentInventry">
                      <div class="item">{{ $item->shipment->emergency->product_0 }}</div>
                      <div class="item">{{ $item->shipment->emergency->product_01 }}</div>
                      <div class="item">{{ $item->shipment->emergency->product_02 }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          @endif

          {{-- Postponed Conversion --}}
          @if ($item->dc_number !== 1 && $item->region_id !== 1)
            @if ($item->used_conversion->converted_p1 + $item->used_conversion->converted_p2 > 0)
              <div class="labelValue">
                <label>↳ Postponed Conversion (Used to Fulfill Sales)</label>
                <div class="inventoryContainer">
                  <div class="item">{{ $item->used_conversion->converted_p1 + $item->used_conversion->converted_p2 }}
                  </div>
                  <div class="item">{{ $item->used_conversion->converted_p1 }}</div>
                  <div class="item">{{ $item->used_conversion->converted_p2 }}</div>
                </div>
              </div>
            @endif
          @endif

          <div class="labelValue">
            <label><span class="subtraction"><i class="fa fa-minus" aria-hidden="true"></i></span> Sales</label>
            <div class="inventoryContainer">
              <div class="item">{{ number_format($item->sales->product_0 ?? 0, 0) }}</div>
              <div class="item">{{ number_format($item->sales->product_01 ?? 0, 0) }}</div>
              <div class="item">{{ number_format($item->sales->product_02 ?? 0, 0) }}</div>
            </div>
          </div>

          @if ($item->dc_number !== 1 && $item->region_id !== 1)
            <div class="labelValue">
              <label><span class="addition"><i class="fa fa-plus" aria-hidden="true"></i></span> Delayed
                Shipments</label>
              <div class="inventoryContainer">
                <div class="item">{{ number_format($item->delayed_shipments->product_0 ?? 0, 0) }}</div>
                <div class="item">{{ number_format($item->delayed_shipments->product_01 ?? 0, 0) }}</div>
                <div class="item">{{ number_format($item->delayed_shipments->product_02 ?? 0, 0) }}</div>
              </div>
            </div>
          @endif

          <div class="labelValue endingInventory">
            <label>Ending Inventory</label>
            <div class="inventoryContainer">
              <div class="item">{{ number_format(max(0, $item->ending_inventory->product_0 ?? 0), 0) }}</div>
              <div class="item">{{ number_format(max(0, $item->ending_inventory->product_01 ?? 0), 0) }}</div>
              <div class="item">{{ number_format(max(0, $item->ending_inventory->product_02 ?? 0), 0) }}</div>
            </div>
          </div>
        </div>
      </div>
    @endforeach
  </div>
  @if ($reportAnalysis && $reportAnalysis->response)
    <div class="reportAnalysis">
      <div class="headingSection">
        <img src="{{ asset('images/ChatGPT-Logo.png') }}" alt="">
        <h2>Quarter {{ request('quarter_id') }} - Finished Goods Inventory Analysis & Insights</h2>
        <p>Evaluating stock levels, valuation, and movement of finished goods across regions and products.</p>
      </div>
      {!! $reportAnalysis->response !!}
    </div>
  @endif
@endsection

@section('templateScript')
  <script src="https://code.highcharts.com/highcharts.js"></script>

  <script>
    Highcharts.chart('fgiInventoryDonut', {
      chart: {
        type: 'pie'
      },
      title: {
        text: 'Finished Goods Inventory Overview (All Regions)'
      },
      exporting: {
        enabled: false
      },
      credits: {
        enabled: false
      },
      plotOptions: {
        pie: {
          innerSize: '50%',
          dataLabels: {
            enabled: true,
            format: '<b>{point.name}</b>: {point.y}',
            style: {
              fontSize: '12px'
            }
          }
        }
      },
      tooltip: {
        pointFormat: '{series.name}: <b>{point.y}</b>'
      },
      series: [{
        name: 'Units',
        data: [{
            name: 'Beginning Inventory',
            y: {{ $dcCenters->sum(
                fn($dc) => ($dc->beginning_inventory->product_0 ?? 0) +
                    ($dc->beginning_inventory->product_01 ?? 0) +
                    ($dc->beginning_inventory->product_02 ?? 0),
            ) }},
            color: '#4e79a7'
          },
          {
            name: 'Available Inventory',
            y: {{ $dcCenters->sum(
                fn($dc) => ($dc->available_inventory->product_0 ?? 0) +
                    ($dc->available_inventory->product_01 ?? 0) +
                    ($dc->available_inventory->product_02 ?? 0),
            ) }},
            color: '#f28e2b'
          },
          {
            name: 'Ending Inventory',
            y: {{ $dcCenters->sum(
                fn($dc) => ($dc->ending_inventory->product_0 ?? 0) +
                    ($dc->ending_inventory->product_01 ?? 0) +
                    ($dc->ending_inventory->product_02 ?? 0),
            ) }},
            color: '#59a14f'
          }
        ]
      }]
    });
  </script>
  <script>
    Highcharts.chart('fgiProductionDonut', {
      chart: {
        type: 'pie'
      },
      title: {
        text: 'Finished Goods Production & Shipments'
      },
      exporting: {
        enabled: false
      },
      credits: {
        enabled: false
      },
      plotOptions: {
        pie: {
          innerSize: '50%',
          dataLabels: {
            enabled: true,
            format: '<b>{point.name}</b>: {point.y}',
            style: {
              fontSize: '12px'
            }
          }
        }
      },
      tooltip: {
        pointFormat: '{series.name}: <b>{point.y}</b>'
      },
      series: [{
        name: 'Units',
        data: [{
            name: 'Surface Shipments',
            y: {{ $dcCenters->sum(
                fn($dc) => ($dc->shipment->surface->product_0 ?? 0) +
                    ($dc->shipment->surface->product_01 ?? 0) +
                    ($dc->shipment->surface->product_02 ?? 0),
            ) }},
            color: '#4e79a7'
          },
          {
            name: 'Air Shipments',
            y: {{ $dcCenters->sum(
                fn($dc) => ($dc->shipment->air->product_0 ?? 0) +
                    ($dc->shipment->air->product_01 ?? 0) +
                    ($dc->shipment->air->product_02 ?? 0),
            ) }},
            color: '#f28e2b'
          },
          {
            name: 'Emergency Shipments',
            y: {{ $dcCenters->sum(
                fn($dc) => ($dc->shipment->emergency->product_0 ?? 0) +
                    ($dc->shipment->emergency->product_01 ?? 0) +
                    ($dc->shipment->emergency->product_02 ?? 0),
            ) }},
            color: '#e15759'
          },
          {
            name: 'Regular Production',
            y: {{ $dcCenters->sum(
                fn($dc) => ($dc->regular_production->product_0 ?? 0) +
                    ($dc->regular_production->product_01 ?? 0) +
                    ($dc->regular_production->product_02 ?? 0),
            ) }},
            color: '#76b7b2'
          },
          {
            name: 'Emergency Production',
            y: {{ $dcCenters->sum(
                fn($dc) => ($dc->emergency_production->product_0 ?? 0) +
                    ($dc->emergency_production->product_01 ?? 0) +
                    ($dc->emergency_production->product_02 ?? 0),
            ) }},
            color: '#9b59b6'
          }
        ]
      }]
    });
  </script>
@endsection
