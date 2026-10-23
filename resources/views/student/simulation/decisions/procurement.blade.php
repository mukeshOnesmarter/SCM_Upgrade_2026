@extends('student.simulation.layout')
@section('title', 'Procurement')
@section('template')
  <div class="decisionTemplate">
    @include('student.simulation.decisions.partials.commonHeader')
    <div class="procurementSection">
      @include('student.simulation.decisions.partials.last-saved')
      <div class="sectionContainer">
        {{-- PRODUCTS INTRODUCTION --}}
        <div class="productsIntroduction">
          @foreach ($products as $product)
            <div class="product">
              <div class="productTitle">
                <small>PRODUCT - {{ str_pad($product->id, 2, '0', STR_PAD_LEFT) }}</small>
                <label for="heading">{{ strtoupper($product->name) }} </label>
                <span>Industry: {{ $product->industry->name }}</span>
                <p>Materials needed to assemble this product: </p>
              </div>
              @foreach ($product->materialtypes as $materialtype)
                <div class="materialtype">
                  <label for="materialtype">{{ ucwords(str_replace('_', ' ', $materialtype->type)) }} </label>
                  <div class="collection">
                    <ul>
                      @foreach ($materialtype->collections as $collection)
                        <li><i class="fa fa-angle-right" aria-hidden="true"></i> &nbsp; {{ $collection->component->name }}
                          <span>
                            {{ $materialtype->type == 'raw_material' ? str_pad($collection->quantity, 2, '0', STR_PAD_LEFT) . ' Kg' : '01 Unit' }}
                          </span>
                        </li>
                      @endforeach
                    </ul>
                  </div>
                </div>
              @endforeach
              <div class="forcastDemand">
                <strong>{{ number_format($product->forcastDemand, 0) }}</strong>
                <small>Total Forecasted Demand</small>
                <p>(Inclusive of all regions and firms)</p>
              </div>
            </div>
          @endforeach
        </div>
        {{-- ASSEMBLE PRODUCTS  --}}
        <div class="assembleProducts">
          <div class="sectionTitle">
            <small>ASSEMBLE PRODUCTS</small>
            <label for="heading">MATERIAL REQUIREMENTS SUMMARY</label>
            <span>Total Forecasted Demand: {{ number_format($totalForcastDemand, 0) }} Units</span>
            <p>(Inclusive of all products & regions)</p>
          </div>
          <div class="materialtype">
            @foreach ($totalMaterial as $type => $components)
              <div class="type">
                <div class="item title">
                  <label>{{ ucwords(str_replace('_', ' ', $type)) }}</label>
                  <small>{{ $type == 'raw_material' ? '(Kilograms - kg)' : '(Units - pcs)' }}</small>
                </div>
                <div class="componentContainer">
                  @foreach ($components as $component)
                    <div class="component">
                      <span>
                        {{ $type == 'raw_material' ? $component['material']->rawMaterial->name : $component['material']->sac->name }}
                      </span>
                      <strong>{{ number_format($component['total_quantity'], 0) }}</strong>
                    </div>
                  @endforeach
                </div>
              </div>
            @endforeach
          </div>
          {{-- distributionCenters OTHER THAN HOME REGION --}}
          <div class="distributionCenters">
            @if ($distributionCenters)
              <div class="distCenter active" data-id="01">
                <img src="{{ asset('images/icon/home-region.svg') }}" alt="">
                <label for="">Home-Region: 01</label>
                <span>DC Type - In-Sourced </span>
                REGION (A)
                <small>North America</small>
              </div>
              @foreach ($distributionCenters as $distCenter)
                <div class="distCenter" data-id="0{{ $distCenter->region_id }}">
                  <img src="{{ asset('images/icon/distribution-center.svg') }}" alt="">
                  <label for=""> Distribution-Center:
                    {{ str_pad($distCenter->dc_number, '2', '0', STR_PAD_LEFT) }}</label>
                  <span>DC Type - {{ ucwords($distCenter->dc_type) }} </span>
                  REGION ({{ $distCenter->region->name }})
                  <small>{{ $distCenter->region->custome_name }}</small>
                </div>
              @endforeach
            @else
            @endif
          </div>
        </div>
      </div>

      {{-- PROCUREMENT DECISION --}}
      <div class="procurementDecision" id="procurementDecision">
        @foreach ($ownedDcCenters as $distCenter)
          <div class="distributionCenter {{ $distCenter->region_id == 1 ? 'active' : '' }}"
            id="distCenter0{{ $distCenter->region_id }}">
            <div class="sectionHeading">
              <h5><small>PROCUREMENT DECISIONS</small>
                Distribution-Center: {{ str_pad($distCenter->dc_number, '2', '0', STR_PAD_LEFT) }} </h5>
              DC Type - {{ ucwords($distCenter->dc_type) }}
              <small>REGION ({{ $distCenter->region->name }}): {{ $distCenter->region->custome_name }} </small>
              <span>Total Forecasted Demand: {{ number_format($totalForcastDemand, 0) }}</span>
            </div>
            <form class="procurementForm"
              action="{{ route('student.simulation.decisions.save-procurement', [request()->route('simulation_id'), request()->route('quarter_id')]) }}"
              method="POST" enctype="multipart/form-data" autocomplete="off" id="submitForm"
              data-region="{{ $distCenter->region_id }}">
              @csrf
              <div class="procurementContainer">
                <input type="hidden" name="simulation_id" value="{{ request()->route('simulation_id') }}">
                <input type="hidden" name="quarter_id" value="{{ request()->route('quarter_id') }}">
                <input type="hidden" name="region_id" value="{{ $distCenter->region_id }}">

                @foreach ($distCenter->regionProcurement as $procurement)
                  <input type="hidden" name="procurement_type[]" value="{{ $procurement->type }}">
                  @if ($procurement->type === 'raw_material')
                    @if ($distCenter->region_id == 1)
                      <div class="procurement">
                        <div class="sectionTitle">
                          <h6>Raw Material</h6>
                          <p>Widely available single-grade commodities purchased at worldwide spot-market prices.</p>
                        </div>
                        <div class="rawMaterial">
                          @foreach ($procurement->material as $item)
                            <div class="material">
                              <label for="heading">{{ $item->rawMaterial->name }}</label>
                              <input onkeypress="return event.charCode >= 48 && event.charCode <= 57" type="text"
                                name="{{ $procurement->type }}[{{ $item->material_id }}]" placeholder="Enter Quantity"
                                value="{{ $item->rawMaterial->quantity ?? null }}">
                            </div>
                          @endforeach
                        </div>
                      </div>
                    @else
                      @foreach ($procurement->material as $item)
                        <input type="hidden" name="{{ $procurement->type }}[{{ $item->material_id }}]">
                      @endforeach
                    @endif
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
                                            <img src="{{ asset('images/icon/supply-chanel-surface.svg') }}"
                                              alt="">
                                          @endif
                                        </div>
                                        <div class="item inputText">
                                          <label for="title">Previous Qtr</label>
                                          <div class="textInput">
                                            <input disabled type="text"
                                              value="{{ $channel->previousProcurement->total_shipment ?? 0 }}">
                                          </div>
                                        </div>
                                        <div class="item inputText">
                                          <label for="title">Supply Channel: {{ ucwords($channel->name) }}</label>
                                          <div class="textInput">
                                            <input onkeypress="return event.charCode >= 48 && event.charCode <= 57"
                                              type="text"
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
        @endforeach
      </div>
    </div>
  </div>
@endsection
@include('student.simulation.decisionsModal.procurement')
@section('templateScript')
  <script type="text/javascript">
    $(document).ready(function() {
      $(".distCenter").click(function() {
        let parentContainer = $(this).closest(".distributionCenters"); // Find the closest parent container
        let dataId = $(this).attr("data-id"); // Get the data-id of clicked element 

        // Remove active class from all siblings and add it to clicked element
        parentContainer.find(".distCenter").removeClass("active");
        $(this).addClass("active");

        // Find the corresponding procurementDecision container and show the related distributionCenter
        let procurementDecision = $('#procurementDecision'); // Get the next procurementDecision div
        procurementDecision.find(".distributionCenter").hide(); // Hide all
        procurementDecision.find("#distCenter" + dataId).show(); // Show the matching one
      });
    });
  </script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // helper debounce
      function debounce(fn, wait) {
        let t;
        return function() {
          clearTimeout(t);
          t = setTimeout(() => fn.apply(this, arguments), wait);
        };
      }

      // Select all procurement forms
      const forms = document.querySelectorAll('form.procurementForm');

      // Build storage key for a given form
      function storageKeyFor(form) {
        const sim = form.querySelector('input[name="simulation_id"]')?.value ||
          '{{ request()->route('simulation_id') }}';
        const q = form.querySelector('input[name="quarter_id"]')?.value || '{{ request()->route('quarter_id') }}';
        // prefer explicit data-region attribute else fallback to hidden input
        const region = form.dataset.region || form.querySelector('input[name="region_id"]')?.value || 'region0';
        return `procurement_draft_sim${sim}_q${q}_r${region}`;
      }

      // Read form fields into object (skip disabled inputs)
      function readForm(form) {
        const data = {};
        const elements = form.querySelectorAll('input[name], textarea[name], select[name]');
        elements.forEach(el => {
          if (el.disabled) return;
          const name = el.name;
          if (!name) return;
          if (el.type === 'checkbox') data[name] = el.checked;
          else if (el.type === 'radio') {
            if (el.checked) data[name] = el.value;
          } else {
            data[name] = el.value;
          }
        });
        return data;
      }

      // Fill form from object (safe assign)
      function fillForm(form, data) {
        if (!data) return;
        Object.keys(data).forEach(name => {
          // handle inputs that were created as arrays e.g. name[] - querySelector will get the first; handle NodeList
          const els = form.querySelectorAll(`[name="${name}"]`);
          if (!els || els.length === 0) return;
          els.forEach(el => {
            if (el.disabled) return;
            if (el.type === 'checkbox') el.checked = !!data[name];
            else if (el.type === 'radio') {
              if (el.value === data[name]) el.checked = true;
            } else el.value = data[name] ?? '';
          });
        });
      }

      // Save to localStorage
      function saveDraft(form) {
        try {
          const key = storageKeyFor(form);
          const payload = readForm(form);
          localStorage.setItem(key, JSON.stringify(payload));
          // Optional: show small visual 'Draft saved' indicator (implement if you want)
          // console.log('Draft saved for', key);
        } catch (e) {
          console.warn('Could not save procurement draft', e);
        }
      }

      // Restore from localStorage
      function restoreDraft(form) {
        try {
          const key = storageKeyFor(form);
          const draft = localStorage.getItem(key);
          if (draft) {
            fillForm(form, JSON.parse(draft));
          }
        } catch (e) {
          console.warn('Could not restore procurement draft', e);
        }
      }

      // Clear draft (on final submit)
      function clearDraft(form) {
        try {
          const key = storageKeyFor(form);
          localStorage.removeItem(key);
        } catch (e) {
          /* ignore */ }
      }

      // Attach listeners to each form
      forms.forEach(form => {
        // restore on page load (but only for forms that are visible OR we'll restore all to be safe)
        restoreDraft(form);

        // debounced save on input
        const debouncedSaver = debounce(() => saveDraft(form), 500); // saves 500ms after last input
        form.addEventListener('input', debouncedSaver);

        // clear on form submit (successful save will clear draft)
        form.addEventListener('submit', function() {
          clearDraft(form);
        });
      });

      // If you use the distribution center click handler to show/hide forms,
      // ensure restore happens when a distCenter is clicked (so dynamic show also fills).
      // If you still use jQuery click handler from your template, we hook into delegation:
      document.querySelectorAll('.distCenter').forEach(btn => {
        btn.addEventListener('click', function() {
          const dataId = this.getAttribute('data-id')?.replace(/^0/, ''); // normalize '01' -> '1' if needed
          // find the form that matches region id (we used data-region)
          const formToShow = Array.from(forms).find(f => {
            const fRegion = (f.dataset.region || f.querySelector('input[name="region_id"]')?.value || '')
              .toString();
            // accept either '01' or '1' matching — do gentle compare
            return fRegion === this.getAttribute('data-id') || fRegion === dataId || fRegion === ('0' +
              dataId);
          });
          if (formToShow) restoreDraft(formToShow);
        });
      });

    });
  </script>
@endsection
