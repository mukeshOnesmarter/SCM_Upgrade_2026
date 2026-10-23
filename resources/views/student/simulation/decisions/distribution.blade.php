@extends('student.simulation.layout')
@section('title', 'Distribution')
@section('template')
  <div class="decisionTemplate">
    @include('student.simulation.decisions.partials.commonHeader')
    <div class="distributionSection">
      @include('student.simulation.decisions.partials.last-saved')
      @if ($showDcAlert)
        <div class="showDcAlert">
          A Distribution Center outside your home region (Region 1) is currently not operational.
          Was this intentional, or was it closed by mistake?
        </div>
      @endif
      @if ($missingTransport)
        <div class="missingTransport">
          A Distribution Center outside your home region is operational but has no transportation configured.
          Please add transportation data for this region.
        </div>
      @endif

      <div class="headingSection">
        <h5>Home Region (Region 1) - Distribution Center</h5>
        <p>A facility for receiving, storing, and distributing products within your home market.</p>
      </div>
      <form class="distributionForm"
        action="{{ route('student.simulation.decisions.save-distribution', [request()->route('simulation_id'), request()->route('quarter_id')]) }}"
        method="POST" enctype="multipart/form-data" autocomplete="off" id="submitForm" data-region="1">
        @csrf
        <input type="hidden" name="simulation_id" value="{{ request()->route('simulation_id') }}">
        <input type="hidden" name="quarter_id" value="{{ request()->route('quarter_id') }}">
        <input type="hidden" name="region_id" value="1">
        <input type="hidden" name="dc_number" value="1">
        <input type="hidden" name="distribution_center" value="insourced">
        <input type="hidden" name="emergency_carrier" value="5">
        <input type="hidden" name="ffgi_surface_shipping" value="economy">
        <input type="hidden" name="is_home_region" value="yes">
        <input type="hidden" name="is_operational" value="yes">
        <div class="homeRegion">
          <div class="item title">
            <img src="{{ asset('images/icon/warehouse.svg') }}" alt="">
            <span>Distribution Center: Home Region(A)</span>
          </div>
          <div class="item">
            <span class="heading">RFID (Radio-Frequency Identification):</span>
            <small>Tracks inventory for retail shipments.</small>
            <div class="captureFeature">
              <label for="rfidYes">
                <input type="radio" name="rfid_application" id="rfidYes" value="yes"
                  @if (!is_null($homeRegions) && $homeRegions->rfid_application === 'yes') checked @endif>
                Yes</label>
              <label for="rfidNo">
                <input type="radio" name="rfid_application" id="rfidNo" value="no"
                  @if (!is_null($homeRegions) && $homeRegions->rfid_application === 'no') checked @endif>
                No</label>
            </div>
          </div>
          <div class="item">
            <span class="heading">SAC Surface Shipping Level:</span>
            <small>Controls cost and reliability for inbound component shipments.</small>
            <div class="captureFeature">
              <label for="economy"><input checked type="radio" name="sac_surface_shipping" id="economy"
                  value="economy" @if (!is_null($homeRegions) && $homeRegions->sac_shipping === 'economy') checked @endif>Economy</label>
              <label for="standard"><input type="radio" name="sac_surface_shipping" id="standard" value="standard"
                  @if (!is_null($homeRegions) && $homeRegions->sac_shipping === 'standard') checked @endif>Standard</label>
              <label for="expedited"><input type="radio" name="sac_surface_shipping" id="expedited" value="expedited"
                  @if (!is_null($homeRegions) && $homeRegions->sac_shipping === 'expedited') checked @endif>Expedited</label>
            </div>

          </div>
          <div class="item actionButton">
            @if (!is_null($homeRegions))
              <span> Emergency Carrier: {{ $homeRegions->emergencyCarrier->name }}</span>
            @endif
            <button>Save Changes</button>
          </div>
        </div>
      </form>
      <div class="headingSection">
        <h5>Regional Distribution Centers - Other Regions</h5>
        <p>These DCs serve market regions outside Region 1.</p>
      </div>
      <div class="distributionCenters">
        <div class="item">
          <div class="titleContainer graphic">
            <div class="graphic"><img src="{{ asset('images/icon/logistics.svg') }}" alt=""></div>
            <div class="label">Logistics & Distribution <small>Other than home region</small></div>
          </div>
          <div class="featuredContainer setbackgroup">
            <div class="featured">
              <label for="title">Distribution Center (DC) Type</label>
              <small>A facility for receiving, storing, and distributing products.</small>
            </div>
            <div class="featured">
              <label for="title">RFID (Radio-Frequency Identification)</label>
              <small>Tracks and manages inventory automatically.</small>
            </div>
            <div class="featured">
              <label for="title">Emergency Carrier</label>
              <small>Handles urgent or unplanned transportation.</small>
            </div>
            @foreach ($crossDocking as $item)
              <div class="featured">
                <label for="title">Cross-Docking: Carrier ({{ $item->carrier->name }})</label>
                <small>Directly transfers incoming goods to outgoing shipments.</small>
              </div>
            @endforeach
            <div class="featured">
              <label for="title">FFGI (Finished Goods Inventory) Surface Shipping</label>
              <small>Ships finished products via ground transport.</small>
            </div>
            <div class="featured">
              <label for="title">SAC (Supplier Acquisition Cost) Surface Shipping</label>
              <small>Ships raw materials via ground transport.</small>
            </div>
          </div>
        </div>
        @foreach ($regions as $region)
          @continue($region->id === 1)
          <div class="item">
            @if (optional($region->dataSaved)->is_operational == 'no' && request()->route('quarter_id') > 6)
              <div class="dcClosed">
                <label for="">Distribution Center Closed</label>
                <p>You have deactivated this Distribution Center for Quarter {{ request()->route('quarter_id') }}.
                  <span>No shipments will be handled. Re-opening is possible in the next quarter.</span>
                </p>
                <div class="graphic"><img src="{{ asset('images/dc.png') }}" alt=""></div>

                <form class="distributionForm"
                  action="{{ route('student.simulation.decisions.close-distribution', [request()->route('simulation_id'), request()->route('quarter_id')]) }}"
                  method="POST" enctype="multipart/form-data" autocomplete="off" id="submitForm"
                  data-region="{{ $region->id }}">
                  @csrf
                  <input type="hidden" name="simulation_id" value="{{ request()->route('simulation_id') }}">
                  <input type="hidden" name="quarter_id" value="{{ request()->route('quarter_id') }}">
                  <input type="hidden" name="region_id" value="{{ $region->id }}">
                  <input type="hidden" name="requesttype" value="re-open">
                  <button>Re-Activate</button>
                </form>

              </div>
            @else
              <form
                action="{{ route('student.simulation.decisions.save-distribution', [request()->route('simulation_id'), request()->route('quarter_id')]) }}"
                method="POST" enctype="multipart/form-data" autocomplete="off" id="submitForm">
                @csrf
                <input type="hidden" name="simulation_id" value="{{ request()->route('simulation_id') }}">
                <input type="hidden" name="quarter_id" value="{{ request()->route('quarter_id') }}">
                <input type="hidden" name="region_id" value="{{ $region->id }}">
                <input type="hidden" name="dc_number" value="{{ $region->id }}">
                <input type="hidden" name="type" value="distribution_center">
                <input type="hidden" name="is_home_region" value="no">
                <input type="hidden" name="is_operational" value="yes">

                <div class="titleContainer group">
                  @if ($region->prevDataSaved)
                    <div class="item previousQtr"> Previous Qtr
                      <small>Q{{ request()->route('quarter_id') - 1 }}</small>
                    </div>
                  @endif
                  <div class="item">
                    <span> {{ 'Region(' . $region->name . ') ' }}</span>
                    <small>{{ $region->custome_name }}</small>
                    @if (optional($region->dataSaved)->is_operational == 'yes')
                      <span>DC Is Operational</span>
                    @endif
                  </div>
                </div>

                <div class="featuredContainer">
                  <div class="featured nonDomesticDc">
                    @if ($region->prevDataSaved)
                      <div class="previousQuarter">
                        {{ Str::ucfirst(optional($region->prevDataSaved)->dc_type) }}
                      </div>
                    @endif
                    <div class="captureFeature">
                      <label for="insourced"><input type="radio" name="distribution_center" id="insourced"
                          value="insourced" @if (!is_null($region->dataSaved) && $region->dataSaved->dc_type === 'insourced') checked @endif> Insourced</label>
                      <label for="outsourced"><input type="radio" name="distribution_center" id="outsourced"
                          value="outsourced" @if (is_null($region->dataSaved) || $region->dataSaved->dc_type === 'outsourced') checked @endif> Outsourced</label>
                    </div>
                  </div>
                  <div class="featured nonDomesticDc">
                    @if ($region->prevDataSaved)
                      <div class="previousQuarter">
                        {{ Str::ucfirst(optional($region->prevDataSaved)->rfid_application) }}
                      </div>
                    @endif
                    <div class="captureFeature">
                      <label for="rfidYes"><input type="radio" name="rfid_application" id="rfidYes"
                          value="yes" @if (!is_null($region->dataSaved) && $region->dataSaved->rfid_application === 'yes') checked @endif> Yes</label>
                      <label for="rfidNo"><input type="radio" name="rfid_application" id="rfidNo"
                          value="no" @if (is_null($region->dataSaved) || $region->dataSaved->rfid_application === 'no') checked @endif> No</label>
                    </div>
                  </div>
                  <div class="featured nonDomesticDc">
                    @if ($region->prevDataSaved)
                      <div class="previousQuarter">
                        @foreach ($carriers as $carrier)
                          @if (!is_null($region->prevDataSaved) && $region->prevDataSaved->emergency_carrier_id === $carrier->id)
                            Carrier: {{ $carrier->name }}
                          @endif
                        @endforeach
                      </div>
                    @endif
                    <div class="captureFeature">
                      <div class="selectbox">
                        <select name="emergency_carrier" id="">
                          @foreach ($carriers as $carrier)
                            <option value="{{ $carrier->id }}" @if (!is_null($region->dataSaved) && $region->dataSaved->emergency_carrier_id === $carrier->id) selected @endif>
                              Carrier {{ $carrier->name }}</option>
                          @endforeach
                        </select>
                      </div>
                    </div>
                  </div>
                  {{-- crossDocking --}}
                  @foreach ($crossDocking as $item)
                    @php
                      $carrierName = strtoupper($item->carrier->name);
                      $column = 'cross_docking_' . strtolower($carrierName);
                      $isValidColumn = in_array($carrierName, ['K', 'L', 'M', 'N']);
                      $regionAllowedCarriers = $allowedCarrierIdsByRegion[$region->id] ?? [];
                      $isAllowed = in_array($item->carrier_id, $regionAllowedCarriers);
                      $canUse = $isValidColumn && $isAllowed;
                    @endphp

                    <div class="featured nonDomesticDc">
                      {{-- Previous Quarter --}}
                      @if ($region->prevDataSaved && $isValidColumn)
                        <div class="previousQuarter">
                          {{ is_null($region->prevDataSaved) || $region->prevDataSaved->{$column} === 'yes' ? 'Yes' : 'No' }}
                        </div>
                      @endif
                      <div class="captureFeature">
                        @if ($canUse)
                          <label>
                            <input type="radio" name="cross_docking_{{ $carrierName }}" value="yes"
                              @if (!is_null($region->dataSaved) && $region->dataSaved->{$column} === 'yes') checked @endif>
                            Yes
                          </label>
                          <label>
                            <input type="radio" name="cross_docking_{{ $carrierName }}" value="no"
                              @if (is_null($region->dataSaved) || $region->dataSaved->{$column} !== 'yes') checked @endif>
                            No
                          </label>
                        @else
                          <span class="not-in-use">No surface transport selected</span>
                        @endif
                      </div>
                    </div>
                  @endforeach
                  <div class="featured nonDomesticDc">
                    @if ($region->prevDataSaved)
                      <div class="previousQuarter">
                        {{ Str::ucfirst(optional($region->prevDataSaved)->ffgi_shipping) }}
                      </div>
                    @endif
                    <div class="captureFeature">
                      <label for="economy"><input type="radio" name="ffgi_surface_shipping" id="economy"
                          value="economy" @if (is_null($region->dataSaved) || $region->dataSaved->ffgi_shipping === 'economy') checked @endif>
                        Economy</label>
                      <label for="standard"><input type="radio" name="ffgi_surface_shipping" id="standard"
                          value="standard" @if (!is_null($region->dataSaved) && $region->dataSaved->ffgi_shipping === 'standard') checked @endif>
                        Standard</label>
                      <label for="expedited"><input type="radio" name="ffgi_surface_shipping" id="expedited"
                          value="expedited" @if (!is_null($region->dataSaved) && $region->dataSaved->ffgi_shipping === 'expedited') checked @endif>
                        Expedited</label>
                    </div>
                  </div>
                  <div class="featured nonDomesticDc">
                    @if ($region->prevDataSaved)
                      <div class="previousQuarter">
                        {{ Str::ucfirst(optional($region->prevDataSaved)->sac_shipping) }}
                      </div>
                    @endif
                    <div class="captureFeature">
                      <label for="economy"><input type="radio" name="sac_surface_shipping" id="economy"
                          value="economy" @if (is_null($region->dataSaved) || $region->dataSaved->sac_shipping === 'economy') checked @endif>
                        Economy</label>
                      <label for="standard"><input type="radio" name="sac_surface_shipping" id="standard"
                          value="standard" @if (!is_null($region->dataSaved) && $region->dataSaved->sac_shipping === 'standard') checked @endif>
                        Standard</label>
                      <label for="expedited"><input type="radio" name="sac_surface_shipping" id="expedited"
                          value="expedited" @if (!is_null($region->dataSaved) && $region->dataSaved->sac_shipping === 'expedited') checked @endif>
                        Expedited</label>
                    </div>
                  </div>
                  <div class="featured info nonDomesticDc">
                    @if (optional($region->dataSaved)->is_operational == 'yes' && request()->route('quarter_id') > 6)
                      <div class="alertMessage">
                        <label for="heading">Distribution Center is currently operational.</label>
                        <p>Shipments are being processed from this location.</p>
                        <p>You can choose to close this Distribution Center for the current quarter if needed.</p>
                      </div>
                    @else
                      <div class="alertMessage">
                        <label for="heading">Distribution Center is not yet set up for this region.</label>
                        <p>Shipments are not being processed from this location.</p>
                        <p>You must set up and open the Distribution Center to enable shipments from this region.</p>
                      </div>
                    @endif
                  </div>
                  <div class="actionButton">
                    @if ($region->dataSaved !== null)
                      <button class="update">Modify DC</button>
                    @else
                      <button>Set Up DC</button>
                    @endif
                    @if (optional($region->dataSaved)->is_operational == 'yes' && request()->route('quarter_id') > 3)
                      <button type="submit" form="close-dc-{{ $region->id }}"
                        onclick="return confirm('Are you sure you want to close this Distribution Center?')">
                        Close Dc
                      </button>
                    @endif
                  </div>
                </div>
              </form>
            @endif
            @if (optional($region->dataSaved)->is_operational == 'yes' && request()->route('quarter_id') > 3)
              <form id="close-dc-{{ $region->id }}"
                action="{{ route('student.simulation.decisions.close-distribution', [request()->route('simulation_id'), request()->route('quarter_id')]) }}"
                method="POST" enctype="multipart/form-data" autocomplete="off" id="submitForm"
                style="display:none;">
                @csrf
                <input type="hidden" name="simulation_id" value="{{ request()->route('simulation_id') }}">
                <input type="hidden" name="quarter_id" value="{{ request()->route('quarter_id') }}">
                <input type="hidden" name="region_id" value="{{ $region->id }}">
                <input type="hidden" name="requesttype" value="close">
              </form>
            @endif
          </div>
        @endforeach
      </div>
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

      // All distribution forms
      const forms = document.querySelectorAll('form.distributionForm');

      // Build a unique localStorage key for a form
      function storageKeyFor(form) {
        const sim = form.querySelector('input[name="simulation_id"]')?.value ||
          '{{ request()->route('simulation_id') }}';
        const q = form.querySelector('input[name="quarter_id"]')?.value || '{{ request()->route('quarter_id') }}';
        const region = form.dataset.region || form.querySelector('input[name="region_id"]')?.value || 'region0';
        return `distribution_draft_sim${sim}_q${q}_r${region}`;
      }

      // Read form values -> object (skip disabled)
      function readForm(form) {
        const data = {};
        const els = form.querySelectorAll('input[name], textarea[name], select[name]');
        els.forEach(el => {
          if (el.disabled) return;
          const name = el.name;
          if (!name) return;
          // Handle checkboxes & radios
          if (el.type === 'checkbox') {
            // If multiple checkboxes share same name (arrays), collect them as array
            if (name.endsWith('[]')) {
              const base = name;
              data[base] = data[base] || [];
              if (el.checked) data[base].push(el.value);
            } else {
              data[name] = el.checked;
            }
          } else if (el.type === 'radio') {
            // radios with same name — only store checked one
            if (el.checked) data[name] = el.value;
          } else {
            // Normal inputs/selects/hidden
            data[name] = el.value;
          }
        });
        return data;
      }

      // Fill form from stored data
      function fillForm(form, data) {
        if (!data) return;
        // iterate stored keys
        Object.keys(data).forEach(name => {
          const nodes = form.querySelectorAll(`[name="${name}"]`);
          if (!nodes || nodes.length === 0) return;

          // If stored is array (checkbox[]), assign accordingly
          if (Array.isArray(data[name])) {
            nodes.forEach(n => {
              if (n.type === 'checkbox') {
                n.checked = data[name].includes(n.value);
              } else {
                // other input types with [] naming - try first node
                n.value = data[name][0] ?? n.value;
              }
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

      // Save draft
      function saveDraft(form) {
        try {
          const key = storageKeyFor(form);
          const payload = readForm(form);
          localStorage.setItem(key, JSON.stringify(payload));
          // Optional: you can show a small "Draft saved" indicator here
          // console.log('Saved draft:', key);
        } catch (e) {
          console.warn('Failed to save distribution draft', e);
        }
      }

      // Restore draft
      function restoreDraft(form) {
        try {
          const key = storageKeyFor(form);
          const raw = localStorage.getItem(key);
          if (!raw) return;
          const data = JSON.parse(raw);
          fillForm(form, data);
        } catch (e) {
          console.warn('Failed to restore distribution draft', e);
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

      // Attach listeners for each form
      forms.forEach(form => {
        // restore (for hidden forms this will prepare values when shown)
        restoreDraft(form);

        // autosave (debounced)
        const debounced = debounce(() => saveDraft(form), 400);
        form.addEventListener('input', debounced);
        form.addEventListener('change', debounced); // for selects/radios

        // clear on submit
        form.addEventListener('submit', function() {
          clearDraft(form);
          // allow normal submit to proceed
        });
      });

      // If you have region nav / show-hide logic, ensure restore is called when a region is shown.
      // Example: when a .distCenter or region tab is clicked, restore that form:
      document.querySelectorAll('.distCenter, .regionTab, .your-region-selector').forEach(btn => {
        btn.addEventListener('click', function() {
          // get region id — support data-id or data-region
          const rid = this.getAttribute('data-id') || this.dataset.region || this.getAttribute('data-region');
          if (!rid) return;
          // find form matching region (support '01' or '1')
          forms.forEach(f => {
            const fRegion = (f.dataset.region || f.querySelector('input[name="region_id"]')?.value || '')
              .toString();
            if (fRegion === rid || fRegion === rid.replace(/^0/, '') || ('0' + fRegion) === rid) {
              restoreDraft(f);
            }
          });
        });
      });

    });
  </script>
@endsection
@include('student.simulation.decisionsModal.distribution')
