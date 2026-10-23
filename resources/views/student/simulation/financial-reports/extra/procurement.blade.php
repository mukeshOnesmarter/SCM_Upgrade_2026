@php $pageTitle = 'Procurement Inventory Report'; @endphp
@php $routeParams = ['simulation_id' => request('simulation_id'), 'quarter_id' => request('quarter_id') ]; @endphp
@extends('student.simulation.financial-reports.layout')
@section('title', 'Procurement Inventory Report')
@section('downloadlink', route('student.simulation.download.procurement', $routeParams))
@section('reportTemplate')
  <div class="procurementInventoryChart">
    <div id="inventoryDonut" style="height:400px;"></div>
    <div id="purchasesDonut" style="height:400px;"></div>
  </div>
  <div class="procurementInventory">
    {{-- Header --}}
    <div class="procurementType">
      <div class="item">
        Distribution Center(s)
      </div>
      @foreach ($procurementInventory as $type)
        <div class="item">
          <label for="title">{{ $type->name }} </label>
          <small>{{ $type->name === 'Plastics' || $type->name === 'Casings' ? 'KG' : 'Unit(s)' }}</small>
        </div>
      @endforeach 
    </div>
    <div class="motherboardAlert">
      <p>Note: The motherboards procured this quarter will only become available for use in the next quarter.</p>
      {{-- <small>Only the beginning inventory can be used in the current quarter.</small>  --}}
    </div>
    @foreach ($dcCenters as $item)
      <div class="distributionCenter">
        <div class="regionTitle">
          DC-{{ str_pad($item->dc_number, '2', '0', STR_PAD_LEFT) }}: Region ({{ $item->region->name }}) -
          {{ $item->region->custome_name }}
          <small>{{ $item->dc_number === 1 ? 'Home Region & Manufacturing Plant' : 'Distribution Center' }}</small>
        </div>
        <div class="labelValueGroup">
          <div class="labelValue">
            <label><span class="hide">*</span> Beginning Inventory</label>
            <div class="inventoryContainer">
              @if ($item->dc_number === 1 && $item->region_id === 1)
                <div class="item">{{ number_format($item->beginning_inventory->plastic ?? 0, 0) }}</div>
                <div class="item">{{ number_format($item->beginning_inventory->casing ?? 0, 0) }}</div>
              @else
                <div class="item">&nbsp;</div>
                <div class="item">&nbsp;</div>
              @endif
              <div class="item">{{ number_format($item->beginning_inventory->control_interface ?? 0, 0) }}</div>
              <div class="item">{{ number_format($item->beginning_inventory->audio_modal ?? 0, 0) }}</div>
              <div class="item">{{ number_format($item->beginning_inventory->motherboard ?? 0, 0) }}</div>
            </div>
          </div>
          <div class="labelValue">
            <label><span class="addition"><i class="fa fa-plus" aria-hidden="true"></i></span> Purchases Surface</label>
            <div class="inventoryContainer">
              @if ($item->dc_number === 1 && $item->region_id === 1)
                <div class="item">{{ number_format($item->purchases_surface->plastic ?? 0, 0) }}</div>
                <div class="item">{{ number_format($item->purchases_surface->casing ?? 0, 0) }}</div>
              @else
                <div class="item">&nbsp;</div>
                <div class="item">&nbsp;</div>
              @endif
              <div class="item">{{ number_format($item->purchases_surface->control_interface ?? 0, 0) }}</div>
              <div class="item">{{ number_format($item->purchases_surface->audio_modal ?? 0, 0) }}</div>
              <div class="item motherboard">{{ number_format($item->purchases_surface->motherboard ?? 0, 0) }}</div>
            </div>
          </div>
          <div class="labelValue">
            <label><span class="addition"><i class="fa fa-plus" aria-hidden="true"></i></span> Purchases Air</label>
            <div class="inventoryContainer">
              @if ($item->dc_number === 1 && $item->region_id === 1)
                <div class="item">{{ number_format(optional($item->purchases_air)->plastic ?? 0, 0) }}</div>
                <div class="item">{{ number_format(optional($item->purchases_air)->casing ?? 0, 0) }}</div>
              @else
                <div class="item">&nbsp;</div>
                <div class="item">&nbsp;</div>
              @endif
              <div class="item">{{ number_format(optional($item->purchases_air)->control_interface ?? 0, 0) }} </div>
              <div class="item">{{ number_format(optional($item->purchases_air)->audio_modal ?? 0, 0) }} </div>
              <div class="item motherboard">{{ number_format(optional($item->purchases_air)->motherboard ?? 0, 0) }}</div>
            </div>
          </div>
          <div class="labelValue">
            <label><span class="addition"><i class="fa fa-plus" aria-hidden="true"></i></span> Purchases Emergency</label>
            <div class="inventoryContainer">
              @if ($item->dc_number === 1 && $item->region_id === 1)
                <div class="item">{{ number_format($item->purchases_emergency->plastic ?? 0, 0) }}</div>
                <div class="item">{{ number_format($item->purchases_emergency->casing ?? 0, 0) }}</div>
              @else
                <div class="item">&nbsp;</div>
                <div class="item">&nbsp;</div>
              @endif
              <div class="item">{{ number_format($item->purchases_emergency->control_interface ?? 0, 0) }}</div>
              <div class="item">{{ number_format($item->purchases_emergency->audio_modal ?? 0, 0) }}</div>
              <div class="item motherboard">{{ number_format($item->purchases_emergency->motherboard ?? 0, 0) }}</div>
            </div>
          </div>
          <div class="labelValue highlight">
            <label>Available Inventory</label>
            <div class="inventoryContainer">
              @if ($item->dc_number === 1 && $item->region_id === 1)
                <div class="item">{{ number_format($item->available_inventory->plastic ?? 0, 0) }}</div>
                <div class="item">{{ number_format($item->available_inventory->casing ?? 0, 0) }}</div>
              @else
                <div class="item">&nbsp;</div>
                <div class="item">&nbsp;</div>
              @endif
              <div class="item">{{ number_format($item->available_inventory->control_interface ?? 0, 0) }}</div>
              <div class="item">{{ number_format($item->available_inventory->audio_modal ?? 0, 0) }}</div>
              <div class="item">{{ number_format($item->available_inventory->motherboard ?? 0, 0) }}</div>
            </div>
          </div>
          @if ($item->dc_number === 1 && $item->region_id === 1)
            <div class="productionContainer">
              <label for="productiontype"><span class="subtraction"><i class="fa fa-minus" aria-hidden="true"></i></span>
                Production:</label>
              <div class="productContainer">
                @foreach ($item->production as $product)
                  <div class="product">
                    <label>Product {{ $product->product_id }} <small>{{ $product->product_name }}</small></label>
                    <div class="item">{{ number_format($product->plastic ?? 0, 0) }}</div>
                    <div class="item">{{ number_format($product->casing ?? 0, 0) }}</div>
                    <div class="item">{{ number_format($product->control_interface ?? 0, 0) }}</div>
                    <div class="item">{{ number_format($product->audio_modal ?? 0, 0) }}</div>
                    <div class="item">{{ number_format($product->motherboard ?? 0, 0) }}</div>
                  </div>
                @endforeach
              </div>
              <label for="productiontype"><span class="subtraction"><i class="fa fa-minus" aria-hidden="true"></i></span>
                Emergency Production:</label>
              <div class="productContainer">
                @foreach ($item->emergency_production as $product)
                  <div class="product">
                    <label>Product {{ $product->product_id }} <small>{{ $product->product_name }}</small></label>
                    <div class="item">{{ number_format($product->plastic ?? 0, 0) }}</div>
                    <div class="item">{{ number_format($product->casing ?? 0, 0) }}</div>
                    <div class="item">{{ number_format($product->control_interface ?? 0, 0) }}</div>
                    <div class="item">{{ number_format($product->audio_modal ?? 0, 0) }}</div>
                    <div class="item">{{ number_format($product->motherboard ?? 0, 0) }}</div>
                  </div>
                @endforeach
              </div>
            </div>
          @endif 

          <div class="labelValue">
            <label><span class="subtraction"><i class="fa fa-minus" aria-hidden="true"></i></span> Postponed Production</label>
            <div class="inventoryContainer">
              @if ($item->dc_number === 1 && $item->region_id === 1)
                <div class="item">{{ number_format($item->postponed_production->plastic ?? 0, 0) }}</div>
                <div class="item">{{ number_format($item->postponed_production->casing ?? 0, 0) }}</div>
              @else
                <div class="item">&nbsp;</div>
                <div class="item">&nbsp;</div>
              @endif
              <div class="item">{{ number_format($item->postponed_production->control_interface ?? 0, 0) }}</div>
              <div class="item">{{ number_format($item->postponed_production->audio_modal ?? 0, 0) }}</div>
              <div class="item">{{ number_format($item->postponed_production->motherboard ?? 0, 0) }}</div>
            </div>
          </div>

          <div class="productionContainer">
            <label for="productiontype"><span class="subtraction"><i class="fa fa-minus" aria-hidden="true"></i></span>
              Postpond Conversion:</label>
            <div class="productContainer">
              @foreach ($item->postpondConversion as $product)
                <div class="product">
                  <label>Product {{ $product->product_id }} <small>{{ $product->product_name }}</small></label>
                  <div class="item">{{ number_format($product->plastic ?? 0, 0) }}</div>
                  <div class="item">{{ number_format($product->casing ?? 0, 0) }}</div>
                  <div class="item">{{ number_format($product->control_interface ?? 0, 0) }}</div>
                  <div class="item">{{ number_format($product->audio_modal ?? 0, 0) }}</div>
                  <div class="item">{{ number_format($product->motherboard ?? 0, 0) }}</div>
                </div>
              @endforeach
            </div> 
          </div>  

          <div class="labelValue">
            <label><span class="subtraction"><i class="fa fa-minus" aria-hidden="true"></i></span> Replacement
              Parts</label>
            <div class="inventoryContainer">
              @if ($item->dc_number === 1 && $item->region_id === 1)
                <div class="item">{{ number_format($item->replacement_parts->plastic, 0) }}</div>
                <div class="item">{{ number_format($item->replacement_parts->casing, 0) }}</div>
              @else
                <div class="item">&nbsp;</div>
                <div class="item">&nbsp;</div>
              @endif
              <div class="item">{{ number_format(optional($item->replacement_parts)->control_interface ?? 0, 0) }}
              </div>
              <div class="item">{{ number_format(optional($item->replacement_parts)->audio_modal ?? 0, 0) }}</div>
              <div class="item">{{ number_format(optional($item->replacement_parts)->motherboard ?? 0, 0) }}</div>
            </div>
          </div>
          <div class="labelValue">
            <label><span class="addition"><i class="fa fa-plus" aria-hidden="true"></i></span> Purchases
              Delayed</label>
            <div class="inventoryContainer">
              @if ($item->dc_number === 1 && $item->region_id === 1)
                <div class="item">{{ number_format($item->purchases_delayed->plastic ?? 0, 0) }}</div>
                <div class="item">{{ number_format($item->purchases_delayed->casing ?? 0, 0) }}</div>
              @else
                <div class="item">&nbsp;</div>
                <div class="item">&nbsp;</div>
              @endif
              <div class="item">{{ number_format($item->purchases_delayed->control_interface ?? 0, 0) }}</div>
              <div class="item">{{ number_format($item->purchases_delayed->audio_modal ?? 0, 0) }}</div>
              <div class="item">{{ number_format($item->purchases_delayed->motherboard ?? 0, 0) }}</div>
            </div>
          </div>
          <div class="labelValue endingInventory">
            <label>Ending Inventory</label>
            <div class="inventoryContainer">
              @if ($item->dc_number === 1 && $item->region_id === 1)
                <div class="item">{{ number_format(max(0, $item->ending_inventory->plastic ?? 0), 0) }}</div>
                <div class="item">{{ number_format(max(0, $item->ending_inventory->casing ?? 0), 0) }}</div>
              @else
                <div class="item">&nbsp;</div>
                <div class="item">&nbsp;</div>
              @endif
              <div class="item">{{ number_format(max(0, $item->ending_inventory->control_interface ?? 0), 0) }}</div>
              <div class="item">{{ number_format(max(0, $item->ending_inventory->audio_modal ?? 0), 0) }}</div>
              <div class="item">{{ number_format(max(0, $item->ending_inventory->motherboard ?? 0), 0) }}</div>
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
        <h2>Quarter {{ request('quarter_id') }} - Procurement Inventory Analysis & Insights</h2>
        <p>Reviewing raw material and component stock positions, valuation, and supply chain efficiency.</p>
      </div>
      {!! $reportAnalysis->response !!}
    </div>
  @endif
@endsection

@section('templateScript')
  <script src="https://code.highcharts.com/highcharts.js"></script>
  <script>
    // Inventory Column Chart
    Highcharts.chart('inventoryDonut', {
      chart: {
        type: 'column'
      },
      title: {
        text: 'Inventory Stages'
      },
      xAxis: {
        categories: ['Plastic', 'Casing', 'Control Interface', 'Audio Modal', 'Motherboard'],
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
          pointPadding: 0.2,
          borderWidth: 0
        }
      },
      series: [{
          name: 'Beginning',
          data: [
            {{ $dcCenters[0]->beginning_inventory->plastic ?? 0 }},
            {{ $dcCenters[0]->beginning_inventory->casing ?? 0 }},
            {{ $dcCenters[0]->beginning_inventory->control_interface ?? 0 }},
            {{ $dcCenters[0]->beginning_inventory->audio_modal ?? 0 }},
            {{ $dcCenters[0]->beginning_inventory->motherboard ?? 0 }}
          ]
        },
        {
          name: 'Available',
          data: [
            {{ $dcCenters[0]->available_inventory->plastic ?? 0 }},
            {{ $dcCenters[0]->available_inventory->casing ?? 0 }},
            {{ $dcCenters[0]->available_inventory->control_interface ?? 0 }},
            {{ $dcCenters[0]->available_inventory->audio_modal ?? 0 }},
            {{ $dcCenters[0]->available_inventory->motherboard ?? 0 }}
          ]
        },
        {
          name: 'Ending',
          data: [
            {{ $dcCenters[0]->ending_inventory->plastic ?? 0 }},
            {{ $dcCenters[0]->ending_inventory->casing ?? 0 }},
            {{ $dcCenters[0]->ending_inventory->control_interface ?? 0 }},
            {{ $dcCenters[0]->ending_inventory->audio_modal ?? 0 }},
            {{ $dcCenters[0]->ending_inventory->motherboard ?? 0 }}
          ]
        }
      ]
    });

    // Purchases Stacked Column Chart
    Highcharts.chart('purchasesDonut', {
      chart: {
        type: 'column'
      },
      title: {
        text: 'Purchases by Mode'
      },
      xAxis: {
        categories: ['Plastic', 'Casing', 'Control Interface', 'Audio Modal', 'Motherboard'],
        crosshair: true
      },
      yAxis: {
        min: 0,
        title: {
          text: 'Units'
        },
        stackLabels: {
          enabled: true
        }
      },
      tooltip: {
        shared: true
      },
      plotOptions: {
        column: {
          stacking: 'normal'
        }
      },
      series: [{
          name: 'Surface',
          data: [
            {{ $dcCenters[0]->purchases_surface->plastic ?? 0 }},
            {{ $dcCenters[0]->purchases_surface->casing ?? 0 }},
            {{ $dcCenters[0]->purchases_surface->control_interface ?? 0 }},
            {{ $dcCenters[0]->purchases_surface->audio_modal ?? 0 }},
            {{ $dcCenters[0]->purchases_surface->motherboard ?? 0 }}
          ]
        },
        {
          name: 'Air',
          data: [
            {{ $dcCenters[0]->purchases_air->plastic ?? 0 }},
            {{ $dcCenters[0]->purchases_air->casing ?? 0 }},
            {{ $dcCenters[0]->purchases_air->control_interface ?? 0 }},
            {{ $dcCenters[0]->purchases_air->audio_modal ?? 0 }},
            {{ $dcCenters[0]->purchases_air->motherboard ?? 0 }}
          ]
        },
        {
          name: 'Emergency',
          data: [
            {{ $dcCenters[0]->purchases_emergency->plastic ?? 0 }},
            {{ $dcCenters[0]->purchases_emergency->casing ?? 0 }},
            {{ $dcCenters[0]->purchases_emergency->control_interface ?? 0 }},
            {{ $dcCenters[0]->purchases_emergency->audio_modal ?? 0 }},
            {{ $dcCenters[0]->purchases_emergency->motherboard ?? 0 }}
          ]
        }
      ]
    });
  </script>
@endsection
