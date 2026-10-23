@extends('student.simulation.layout')
@section('title', 'Forecasting')
@section('template')
  <div class="decisionTemplate">
    @include('student.simulation.decisions.partials.commonHeader')
    <div class="forecastingSection">
      @include('student.simulation.decisions.partials.last-saved')
      <form
        class="forecastingForm"
        action="{{ route('student.simulation.decisions.save-forecasting', [request()->route('simulation_id'), request()->route('quarter_id')]) }}"
        method="POST" autocomplete="off" enctype="multipart/form-data" id="submitForm"
        data-simulation="{{ request()->route('simulation_id') }}"
        data-quarter="{{ request()->route('quarter_id') }}">
        @csrf
        <input type="hidden" name="simulation_id" value="{{ request()->route('simulation_id') }}">
        <input type="hidden" name="quarter_id" value="{{ request()->route('quarter_id') }}">
        <div class="regionWrapper">
          @foreach ($regions as $region)
            <div class="region">
              <div class="regionTitle">
                <label for="heading">Region - {{ str_pad($region->id, '2', '0', STR_PAD_LEFT) }}<small> - DEMAND
                    FORECASTING - </small></label>
              </div>
              <div class="productWrapper">
                @foreach ($region->products as $product)
                  <div class="product">
                    <div class="productTitle">
                      <label for="heading">{{ $product->name }} </label>
                    </div>
                    <div class="demandWrapper">
                      <div class="quarter">
                        <span class="label">Qtr</span>
                        <span>{{ request()->route('quarter_id') - 1 }}</span>
                        <span>{{ request()->route('quarter_id') }}</span>
                      </div>
                      @foreach ($product->demands as $demand)
                        <div class="demand">
                          <label for="title">{{ $demand->title }}</label>
                          <div class="inputCase">
                            <input type="text" disabled value="{{ $demand->last_quarter_demand }}">
                          </div>
                          <div class="inputCase">
                            <input onkeypress="return event.charCode >= 48 && event.charCode <= 57" type="text"
                              name="{{ 'region_' . $region->id . '_product_' . $product->id . '_demand_' . $demand->id }}[]"
                              value="{{ $demand->demand_saved ?? '' }}">
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
        <div class="actionButton">
          <button>Save your manual entries for the current quarter</button>
        </div>
      </form>

      {{-- saveDefault --}}
      <div class="saveDefault">
        <h5><img src="{{ asset('images/icon/data-exploration.svg') }}" alt=""> Apply System Data</h5>
        <p>This will overwrite your entries with system-generated forecast values based on historical demand patterns and
          predictive modeling.</p>
        <form 
          action="{{ route('student.simulation.decisions.save-forecasting-default', [request()->route('simulation_id'), request()->route('quarter_id')]) }}"
          method="POST" autocomplete="off" enctype="multipart/form-data">
          @csrf
          <input type="hidden" name="simulation_id" value="{{ request()->route('simulation_id') }}">
          <input type="hidden" name="quarter_id" value="{{ request()->route('quarter_id') }}">
          <button class="default">Apply System Data</button>
        </form>
      </div>
    </div>
  </div>
@endsection
@section('templateScript')
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // debounce helper
      function debounce(fn, wait) {
        let t;
        return function() {
          clearTimeout(t);
          t = setTimeout(() => fn.apply(this, arguments), wait);
        };
      }

      // All forecasting forms (should be one, but supports many)
      const forms = document.querySelectorAll('form.forecastingForm');

      // Unique localStorage key
      function storageKeyFor(form) {
        const sim = form.dataset.simulation || form.querySelector('input[name="simulation_id"]')?.value ||
          '{{ request()->route('simulation_id') }}';
        const q = form.dataset.quarter || form.querySelector('input[name="quarter_id"]')?.value ||
          '{{ request()->route('quarter_id') }}';
        return `forecasting_draft_sim${sim}_q${q}`;
      }

      // Read form inputs into plain object (handles name[], etc.)
      function readForm(form) {
        const data = {};
        const els = form.querySelectorAll('input[name], textarea[name], select[name]');
        els.forEach(el => {
          if (el.disabled) return; // skip disabled display-only fields
          const name = el.name;
          if (!name) return;

          // checkboxes (support name[] arrays)
          if (el.type === 'checkbox') {
            if (name.endsWith('[]')) {
              data[name] = data[name] || [];
              if (el.checked) data[name].push(el.value);
            } else {
              data[name] = el.checked;
            }
            return;
          }

          // radios — only store checked one
          if (el.type === 'radio') {
            if (el.checked) data[name] = el.value;
            return;
          }

          // inputs/selects/textarea (including names like region_1_product_2_demand_3[])
          if (name.endsWith('[]')) {
            // multiple inputs with same name[]: collect values in array
            data[name] = data[name] || [];
            data[name].push(el.value);
          } else {
            data[name] = el.value;
          }
        });
        return data;
      }

      // Fill form fields from saved object
      function fillForm(form, data) {
        if (!data) return;
        Object.keys(data).forEach(name => {
          const nodes = form.querySelectorAll(`[name="${name}"]`);
          if (!nodes || nodes.length === 0) return;

          const value = data[name];

          if (Array.isArray(value)) {
            // name[] or multiple elements with same name
            nodes.forEach((n, idx) => {
              if (n.type === 'checkbox') {
                n.checked = value.includes(n.value);
              } else if (n.type === 'radio') {
                n.checked = value.includes(n.value);
              } else {
                // assign by index if available, else first value
                n.value = value[idx] ?? value[0] ?? '';
              }
            });
          } else {
            nodes.forEach(n => {
              if (n.type === 'checkbox') n.checked = !!value;
              else if (n.type === 'radio') {
                if (n.value === value) n.checked = true;
              } else {
                n.value = value ?? '';
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
          // optional UI hint: dispatch an event or toggle a badge.
          // dispatchDraftSaved(key);
        } catch (e) {
          console.warn('Could not save forecasting draft', e);
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
          console.warn('Could not restore forecasting draft', e);
        }
      }

      // Clear draft on submit
      function clearDraft(form) {
        try {
          const key = storageKeyFor(form);
          localStorage.removeItem(key);
        } catch (e) {}
      }

      // Hook handlers to each form
      forms.forEach(form => {
        // Restore on load (safe even if fields are hidden)
        restoreDraft(form);

        // Debounced autosave for inputs and changes
        const debouncedSave = debounce(() => saveDraft(form), 450);
        form.addEventListener('input', debouncedSave);
        form.addEventListener('change', debouncedSave);

        // Clear draft on submit
        form.addEventListener('submit', function() {
          clearDraft(form);
          // allow submit to proceed normally
        });

        // If forecasting inputs are dynamically generated or updated by JS,
        // you can call restoreDraft(form) again after generation.
      });

      // Re-restore on pageshow (back button or BFCache)
      window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
          forms.forEach(f => restoreDraft(f));
        }
      });

      // Optional helper to restore when a modal or tab opens:
      // call restoreDraftForcing(element) where element is the form or a button that shows the form.
      window.restoreForecastingDraftFor = function(formOrSelector) {
        let form = null;
        if (typeof formOrSelector === 'string') form = document.querySelector(formOrSelector);
        else form = formOrSelector;
        if (form && form.matches && form.matches('form.forecastingForm')) restoreDraft(form);
      };

    });
  </script>
@endsection
@include('student.simulation.decisionsModal.forecasting')