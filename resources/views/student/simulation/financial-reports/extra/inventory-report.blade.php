@php $pageTitle = 'Inventory Stock'; @endphp
@php $routeParams = ['simulation_id' => request('simulation_id'), 'quarter_id' => request('quarter_id') ]; @endphp
@extends('student.simulation.financial-reports.layout')
@section('title', 'Inventory Stock')
@section('downloadlink', route('student.simulation.download.inventory-stock', $routeParams))
@section('reportTemplate')
  @php
    $subAssemblyNames = [
        1 => 'Control Interface',
        2 => 'Audio Module',
        3 => 'Motherboard',
    ];

    $supplierMap = [
        1 => ['code' => 'A', 'name' => 'Global Prime Suppliers'],
        2 => ['code' => 'B', 'name' => 'Federal Suppliers'],
        3 => ['code' => 'C', 'name' => 'Precision Line Suppliers'],
        4 => ['code' => 'D', 'name' => 'Elite Tech Suppliers'],
        5 => ['code' => 'E', 'name' => 'Summit Edge Suppliers'],
        6 => ['code' => 'F', 'name' => 'Reliable Trade Suppliers'],
        7 => ['code' => 'G', 'name' => 'Visionary Suppliers'],
    ];
  @endphp

  <div class="inventoryStock">
    {{-- procurementInventory --}}
    <div class="procurementInventory">
      <h3>Procurement Summary (Materials Procured This Quarter)</h3>
      @if (is_array($procurementClosure) && isset($procurementClosure['message']))
        <p>{{ $procurementClosure['message'] }}</p>
      @elseif (is_array($procurementClosure))
        <div class="procurementClosure">
          @foreach ($procurementClosure as $regionId => $regionData)
            <div class="regionContainer">
              <h4>Region {{ $regionId }}</h4>
              @if ($regionId === 1)
                <div class="rawMaterials">
                  <label for="heading">Raw Materials</label>
                  @foreach ($regionData['rawMaterial'] as $rawMaterialId => $quantity)
                    <div class="materialType">
                      <strong>Raw Material: {{ $rawMaterialId }}</strong>
                      <span>{{ number_format($quantity, 0) }}</span>
                    </div>
                  @endforeach
                </div>
              @endif
              <div class="heading">Sub Assemblies</div>
              <div class="subAssemblieContainer">
                @foreach ($regionData['subAssembly'] as $subAssemblyId => $suppliers)
                  <div class="subAssemblies">
                    <label>{{ $subAssemblyNames[$subAssemblyId] ?? 'Sub Assembly' }}</label>
                    <div class="supplierContainer">
                      @foreach ($suppliers as $supplierId => $supplyChannels)
                        <div class="supplier">
                          <label for="">Supplier ({{ $supplierMap[$supplierId]['code'] ?? '' }}) :
                            {{ $supplierMap[$supplierId]['name'] ?? 'Supplier' }}
                          </label>
                          <div class="supplyChannel">
                            @foreach ($supplyChannels as $supplyChannelId => $quantity)
                              <div class="channel">
                                <span>Supply Channel (<strong>{{ $supplyChannelId == 1 ? 'Air' : 'Surface' }}</strong>):
                                </span>
                                <strong>{{ $quantity }}</strong>
                              </div>
                            @endforeach
                          </div>
                        </div>
                      @endforeach
                    </div>
                  </div>
                @endforeach

              </div>
            </div>
          @endforeach
        </div>
      @endif

      <h3>Available Inventory After Production & Consumption</h3>
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
      @foreach ($procurementDcCenters as $item)
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
                <div class="item motherboard">{{ number_format(optional($item->purchases_air)->motherboard ?? 0, 0) }}
                </div>
              </div>
            </div>
            <div class="labelValue">
              <label><span class="addition"><i class="fa fa-plus" aria-hidden="true"></i></span> Purchases
                Emergency</label>
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
                <label for="productiontype"><span class="subtraction"><i class="fa fa-minus"
                      aria-hidden="true"></i></span>
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
                <label for="productiontype"><span class="subtraction"><i class="fa fa-minus"
                      aria-hidden="true"></i></span>
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
              <label><span class="subtraction"><i class="fa fa-minus" aria-hidden="true"></i></span> Postponed
                Production</label>
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
              <label for="productiontype"><span class="subtraction"><i class="fa fa-minus"
                    aria-hidden="true"></i></span>
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
                <div class="item">{{ number_format(max(0, $item->ending_inventory->control_interface ?? 0), 0) }}
                </div>
                <div class="item">{{ number_format(max(0, $item->ending_inventory->audio_modal ?? 0), 0) }}</div>
                <div class="item">{{ number_format(max(0, $item->ending_inventory->motherboard ?? 0), 0) }}</div>
              </div>
            </div>
          </div>
        </div>
      @endforeach
    </div>

    {{-- finishedGoods --}}
    <div class="finishedGoods">
      <h3>Finished Goods Inventory Remaining (After Sales & Fulfillment)</h3>
      {{-- Header --}}
      <div class="headerContainer">
        <div class="item">Product 0 <small>Product "SHELL"</small></div>
        <div class="item">Product 01 <small>SMART HOME ASSISTANT</small></div>
        <div class="item">Product 02 <small>SMART THERMOSTAT</small></div>
      </div>

      @foreach ($finishedGoodsDcCenters as $item)
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
                <label for="title"><span class="addition"><i class="fa fa-plus"
                      aria-hidden="true"></i></span></span>
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
                    <div class="item">
                      {{ $item->used_conversion->converted_p1 + $item->used_conversion->converted_p2 }}
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
  </div>
@endsection
