@extends('student.simulation.layout')
@section('title', 'Transportation')
@section('template')
  <div class="decisionTemplate">
    @include('student.simulation.decisions.partials.commonHeader')
    <div class="transportationSection">
      @include('student.simulation.decisions.partials.last-saved')
      {{-- All  DISTRIBUTIONCENTER --}}
      @if ($distributioncenters->isNotEmpty())
        @foreach ($distributioncenters as $distributioncenter)
          <form class="transportationForm"
            action="{{ route('student.simulation.decisions.save-transportation', [request()->route('simulation_id'), request()->route('quarter_id')]) }}"
            method="POST" enctype="multipart/form-data" autocomplete="off" id="submitForm"
            data-dc="{{ $distributioncenter->region_id }}">
            @csrf
            <input type="hidden" name="simulation_id" value="{{ request()->route('simulation_id') }}">
            <input type="hidden" name="quarter_id" value="{{ request()->route('quarter_id') }}">
            <input type="hidden" name="distribution_center_id" value="{{ $distributioncenter->region_id }}">
            <div class="distributioncenter">
              <div class="headingTitle">
                <img src="{{ asset('images/icon/distribution-center.svg') }}" alt="">
                Distribution Center: {{ str_pad($distributioncenter->region_id, '2', '0', STR_PAD_LEFT) }}
                <small>Plant-to-DC Transportation Shipments</small>
                <small>Manage shipment mode and volume for deliveries from the manufacturing plant to Distribution Center
                  {{ str_pad($distributioncenter->region_id, '2', '0', STR_PAD_LEFT) }}</small>
              </div>
              <div class="productManufacturing">
                @foreach ($distributioncenter->products as $item)
                  <input type="hidden" name="product_id[{{ $item->id }}]" value="{{ $item->id }}">
                  <div class="product">
                    <div class="productTitle">
                      <small>PRODUCT - {{ str_pad($item->id, 2, '0', STR_PAD_LEFT) }}</small>
                      <label for="heading">{{ strtoupper($item->name ?? 'Product (SHELL)') }} </label>
                    </div>
                    <div class="shipmentcarrier">
                      @foreach ($item->carriers as $shipment)
                        <input type="hidden" name="carrier_id[{{ $shipment->id }}]" value="{{ $shipment->id }}">
                        <div class="carrier">
                          <div class="carrierName">Carrier ({{ $shipment->name }})</div>
                          <div class="quarter">
                            <span>Prev Qtr</span>
                            <span>This Qtr</span>
                          </div>
                          <div class="supplyChannel">
                            @foreach ($shipment->channels as $channel)
                              <input type="hidden" name="supply_channel[{{ $channel->supply_channel_id }}]"
                                value="{{ $channel->supply_channel_id }}">
                              <div class="channel">
                                <div class="demandrecorded">
                                  <input disabled type="text"
                                    value="{{ $channel->previous_quantity > 0 ? $channel->previous_quantity : '' }}">
                                </div>
                                <div class="demandrecorded">
                                  <input onkeypress="return event.charCode >= 48 && event.charCode <= 57" type="text"
                                    name="{{ 'dc_' . $distributioncenter['region_id'] . '_product_' . $item->id . '_carrier_' . $shipment->id . '_supply_channel_' . $channel->supply_channel_id }}"
                                    id="" value="{{ $channel->quantity > 0 ? $channel->quantity : '' }}"
                                    placeholder="{{ $channel->supplyChannel->name }}">
                                  <i class="fa fa-pencil" aria-hidden="true"></i>
                                </div>
                              </div>
                            @endforeach
                          </div>
                        </div>
                      @endforeach
                    </div>
                  </div>
                @endforeach
                {{-- CROSS DOCKING --}}
                <div class="crossDocking">
                  <div class="dockingTitle">
                    <small>CROSS DOCKING</small>
                    <label for="heading">CROSS DOCKING</label>
                  </div>
                  <div class="dockingcarrier">
                    <div class="carrier">
                      <div class="carrierName">Carrier (I) </div>
                      <div class="notApplicable">Not Applicable</div>
                    </div>
                    <div class="carrier">
                      <div class="carrierName">Carrier (J) </div>
                      <div class="notApplicable">Not Applicable</div>
                    </div>
                    @foreach ($distributioncenter->crossDocking as $cd)
                      <div class="carrier">
                        <div class="carrierName">Carrier ({{ $cd->carrier }})</div>
                        <div class="quarter">
                          <span>Prev Qtr</span>
                          <span>This Qtr</span>
                        </div>
                        <div class="supplyChannel">
                          <div class="channel">
                            <!-- ✅ PREVIOUS QUARTER -->
                            <div class="demandrecorded">
                              <input class="crossdocking" disabled type="text" value="{{ $cd->previous }}">
                            </div>
                            <!-- ✅ CURRENT QUARTER -->
                            <div class="demandrecorded">
                              <label class="availcrossDocking">
                                <input type="checkbox" name="cross_docking_{{ strtolower($cd->carrier) }}" value="yes"
                                  {{ $cd->current ? 'checked' : '' }}>
                              </label>
                            </div>
                          </div>
                        </div>
                      </div>
                    @endforeach
                  </div>
                </div>
              </div>
              <div class="actionButton">
                <button>Save Transportation DC
                  {{ str_pad($distributioncenter->region_id, '2', '0', STR_PAD_LEFT) }}</button>
              </div>
            </div>
          </form>
        @endforeach
      @else
        <!-- Show Default Message If No Distribution Centers Exist -->
        <h5>Region Without Local Distribution Center</h5>
        <p>Your firm currently does not operate a distribution center in this market region. As a result, all customer
          orders are fulfilled from the manufacturing plant's distribution center in Market Region 1.</p>
        <p>To ensure timely delivery, these orders are shipped via air — incurring significantly higher transportation
          costs compared to standard surface shipments.</p>
        <p>This cost is especially high for <strong>Direct-channel (Channel 2)</strong> orders, which are typically
          shipped in smaller quantities and result in higher per-unit transport costs.</p>
        <p>To reduce shipping expenses and enhance service responsiveness, consider establishing a local distribution
          center in this region in future quarters.</p>

      @endif
    </div>
  </div>
@endsection
@section('templateScript')
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Debounce helper
      function debounce(fn, wait) {
        let t;
        return function() {
          clearTimeout(t);
          t = setTimeout(() => fn.apply(this, arguments), wait);
        };
      }

      // All transportation forms
      const forms = document.querySelectorAll('form.transportationForm');

      // Unique storage key per simulation/quarter/distribution center
      function storageKeyFor(form) {
        const sim = form.querySelector('input[name="simulation_id"]')?.value ||
          '{{ request()->route('simulation_id') }}';
        const q = form.querySelector('input[name="quarter_id"]')?.value || '{{ request()->route('quarter_id') }}';
        const dc = form.dataset.dc || form.querySelector('input[name="distribution_center_id"]')?.value || 'dc0';
        return `transportation_draft_sim${sim}_q${q}_dc${dc}`;
      }

      // Read form fields into object (skip disabled)
      function readForm(form) {
        const data = {};
        const els = form.querySelectorAll('input[name], textarea[name], select[name]');
        els.forEach(el => {
          if (el.disabled) return;
          const name = el.name;
          if (!name) return;

          if (el.type === 'checkbox') {
            if (name.endsWith('[]')) {
              data[name] = data[name] || [];
              if (el.checked) data[name].push(el.value);
            } else {
              data[name] = el.checked;
            }
          } else if (el.type === 'radio') {
            if (el.checked) data[name] = el.value;
            // leave unchecked radios alone
          } else {
            data[name] = el.value;
          }
        });
        return data;
      }

      // Populate form from stored object
      function fillForm(form, data) {
        if (!data) return;
        Object.keys(data).forEach(name => {
          const nodes = form.querySelectorAll(`[name="${name}"]`);
          if (!nodes || nodes.length === 0) return;

          if (Array.isArray(data[name])) {
            nodes.forEach(n => {
              if (n.type === 'checkbox') n.checked = data[name].includes(n.value);
              else n.value = data[name][0] ?? n.value;
            });
          } else {
            nodes.forEach(n => {
              if (n.type === 'checkbox') n.checked = !!data[name];
              else if (n.type === 'radio') {
                if (n.value === data[name]) n.checked = true;
              } else {
                n.value = data[name] ?? '';
              }
            });
          }
        });
      }

      // Save draft to localStorage
      function saveDraft(form) {
        try {
          const key = storageKeyFor(form);
          const payload = readForm(form);
          localStorage.setItem(key, JSON.stringify(payload));
          // optional: show UI indicator here
        } catch (e) {
          console.warn('Could not save transportation draft', e);
        }
      }

      // Restore draft from localStorage
      function restoreDraft(form) {
        try {
          const key = storageKeyFor(form);
          const raw = localStorage.getItem(key);
          if (!raw) return;
          const data = JSON.parse(raw);
          fillForm(form, data);
        } catch (e) {
          console.warn('Could not restore transportation draft', e);
        }
      }

      // Clear draft on submit
      function clearDraft(form) {
        try {
          const key = storageKeyFor(form);
          localStorage.removeItem(key);
        } catch (e) {
          /* ignore */
        }
      }

      // Attach handlers
      forms.forEach(form => {
        // restore on load (safe even if hidden)
        restoreDraft(form);

        // debounced autosave on input + change (for selects, radios)
        const debounced = debounce(() => saveDraft(form), 450);
        form.addEventListener('input', debounced);
        form.addEventListener('change', debounced);

        // clear draft on submit
        form.addEventListener('submit', function() {
          clearDraft(form);
          // allow form submission to continue
        });
      });

      // Re-restore when page is shown from BFCache or back/forward
      window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
          forms.forEach(f => restoreDraft(f));
        }
      });

      // If you have UI that hides/shows a DC (tabs, click handlers), restore when the DC is shown.
      // Example: if distribution center panels have a `.distCenter` button with `data-id="01"`,
      // call restoreDraft for the matching form on click:
      document.querySelectorAll('.distCenter, .dcToggle, .your-dc-selector').forEach(btn => {
        btn.addEventListener('click', function() {
          const rid = this.getAttribute('data-id') || this.dataset.dc || this.getAttribute('data-region');
          if (!rid) return;
          forms.forEach(f => {
            const fDc = (f.dataset.dc || f.querySelector('input[name="distribution_center_id"]')?.value ||
              '').toString();
            if (fDc === rid || fDc === rid.replace(/^0/, '') || ('0' + fDc) === rid) {
              restoreDraft(f);
            }
          });
        });
      });

    });
  </script>
@endsection
@include('student.simulation.decisionsModal.transportation')
