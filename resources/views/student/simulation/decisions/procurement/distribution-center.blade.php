<div class="distributionCenter" id="distCenter0{{ $distCenter->region_id }}">
  <div class="sectionHeading">
    <h5><small>PROCUREMENT DECISIONS</small>
      Distribution-Center: {{ str_pad($distCenter->dc_number, '2', '0', STR_PAD_LEFT) }} </h5>
    DC Type - {{ ucwords($distCenter->dc_type) }}
    <small>REGION ({{ $distCenter->region->name }}): {{ $distCenter->region->custome_name }} </small>
    <span>Total Forecasted Demand: {{ number_format($totalForcastDemand, 0) }}</span>
  </div>
  <form
    action="{{ route('student.simulation.decisions.save-procurement', [request()->route('simulation_id'), request()->route('quarter_id')]) }}"
    method="POST" enctype="multipart/form-data" autocomplete="off" id="submitForm">
    @csrf
    <div class="procurementContainer">
      <input type="hidden" name="simulation_id" value="{{ request()->route('simulation_id') }}">
      <input type="hidden" name="quarter_id" value="{{ request()->route('quarter_id') }}">
      <input type="hidden" name="region_id" value="{{ $distCenter->region_id }}">
      @foreach ($manageProcurement as $procurement)
        <input type="hidden" name="procurement_type[]" value="{{ $procurement->type }}">
        @if ($procurement->type === 'raw_material')
          @foreach ($procurement->material as $item)
            <input type="hidden" name="{{ $procurement->type }}[{{ $item->material_id }}]">
          @endforeach
        @else
          <div class="procurement">
            <div class="sectionTitle">
              <h6>Sub Assembly Component</h6>
              <p>Sub-Assembly Component Characteristics</p>
            </div>
            <div class="subAssembly">
              @foreach ($procurement->material as $item)
                <input type="hidden" name="{{ $procurement->type }}[]" value="{{ $item->material_id }}">
                <div class="material">
                  <div class="materiallabel">
                    <label for="heading">{{ $item->sac->name }}</label>
                    <small>Units Required: {{ number_format($item->demand, 0) }}</small>
                  </div>
                  <div class="suppliersContainer">
                    @foreach ($item->sac->supplierComponent as $component)
                      <div class="supplier">
                        <div class="supplierName">
                          <label for="supplierName">SUPPLIER ({{ $component->supplier->name }}) </label> 
                        </div>
                        <div class="supplyChannels">
                          @foreach ($component->supply_channels as $channel)
                            <div class="channels">
                              <div class="item channeltype">
                                @if ($channel->id === 1)
                                  <img src="{{ asset('images/icon/supply-chanel-air.svg') }}" alt="">
                                @else
                                  <img src="{{ asset('images/icon/supply-chanel-surface.svg') }}" alt="">
                                @endif
                              </div>
                              <div class="item inputText">
                                <label for="title">Supply Channel: {{ ucwords($channel->name) }}</label>
                                <div class="textInput">
                                  <input onkeypress="return event.charCode >= 48 && event.charCode <= 57" type="text"
                                    name="{{ $procurement->type . '_' . $item->material_id . '_supplier_' . $component->supplier->id . '_supply_channel_' . $channel->id }}"
                                    value="{{ $channel->procurement->total_shipment ?? null }}"
                                    placeholder="Enter Units">
                                  <i class="fa fa-pencil" aria-hidden="true"></i>
                                </div>
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
          </div>
        @endif
      @endforeach
    </div>
    <div class="procurementSave">
      <button>Save Procurement</button>
    </div>
  </form>
</div>
