@extends('student.simulation.layout')
@section('title', 'Manufacturing')
@section('template')
  <div class="decisionTemplate">
    @include('student.simulation.decisions.partials.commonHeader')
    <div class="manufacturingSection">
      @include('student.simulation.decisions.partials.last-saved')
      <form class="manufacturingForm"
        action="{{ route('student.simulation.decisions.save-manufacturing', [request()->route('simulation_id'), request()->route('quarter_id')]) }}"
        method="POST" enctype="multipart/form-data" autocomplete="off" id="submitForm"
        data-region="{{ request()->route('region_id') ?? 'home' }}">
        @csrf
        <input type="hidden" name="simulation_id" value="{{ request()->route('simulation_id') }}">
        <input type="hidden" name="quarter_id" value="{{ request()->route('quarter_id') }}">
        <div class="productContainer">
          @foreach ($products as $product)
            <input type="hidden" name="product_id[]" value="{{ $product->id }}">
            <div class="product">
              <div class="productTitle">
                <small>Product - {{ str_pad($product->id, 2, '0', STR_PAD_LEFT) }}</small>
                <label for="heading">{{ strtoupper($product->name) }} </label>
                <span>Industry: {{ optional($product->industry)->name ?? '-' }}</span>
              </div>
              <div class="forcastDemand">
                Forecasted Demand: {{ number_format($product->forcastDemand, 0) }} units
              </div>
              <div class="inputContainer">
                <div class="inputText">
                  <div class="pastQtr">
                    <label for="">Previous Qtr</label>
                    {{-- safe access --}}
                    <input disabled type="text"
                      value="{{ number_format(optional($product->lastQuartersaved)->production_quantity ?? 0) }} units">
                  </div>
                  <div class="currentQtr">
                    <label for="">Production Quantity *</label>
                    <input type="text" onkeypress="return event.charCode >= 48 && event.charCode <= 57"
                      placeholder="Enter planned production units" name="production_quantity[{{ $product->id }}]"
                      value="{{ old("production_quantity.$product->id") ??
                          (optional($product->manufacturingRecord)->production_quantity ?? $product->forcastDemand) }}">
                    <i class="fa fa-pencil" aria-hidden="true"></i>
                    @error("production_quantity.$product->id")
                      <small class="error_text">{{ $message }}</small>
                    @else
                      <small class="help_text">Enter the number of units to produce this quarter.</small>
                    @enderror
                  </div>
                </div>
                <div class="inputText">
                  <div class="pastQtr">
                    <label for="">Previous Qtr</label>
                    <input disabled type="text"
                      value="{{ number_format(optional($product->lastQuartersaved)->emergency_production ?? 0) }} units">
                  </div>
                  <div class="currentQtr">
                    <label for="">Emergency Production Reserve</label>
                    <input type="text" onkeypress="return event.charCode >= 48 && event.charCode <= 57"
                      placeholder="Set reserve stock units" name="emergency_production[{{ $product->id }}]"
                      value="{{ old("emergency_production.$product->id") ??
                          (optional($product->manufacturingRecord)->emergency_production ?? 0) }}">
                    <i class="fa fa-pencil" aria-hidden="true"></i>
                    <small class="help_text">Extra units to cover unexpected demand.</small>
                    @error("emergency_production.$product->id")
                      <small class="error_text">{{ $message }}</small>
                    @enderror
                  </div>
                </div>
                <div class="inputText">
                  <div class="pastQtr">
                    <label for="">Previous Qtr</label>
                    <input disabled type="text"
                      value="{{ number_format(optional($product->lastQuartersaved)->adjustable_production ?? 0) }} units">
                  </div>
                  <div class="currentQtr">
                    <label for="">Maximum Adjustable Capacity</label>
                    <input type="text" onkeypress="return event.charCode >= 48 && event.charCode <= 57"
                      placeholder="Specify max expandable limit" name="adjustable_production[{{ $product->id }}]"
                      value="{{ old("adjustable_production.$product->id") ??
                          (optional($product->manufacturingRecord)->adjustable_production ?? 10000) }}">
                    <i class="fa fa-pencil" aria-hidden="true"></i>
                    <small class="help_text">Additional capacity available for sudden spikes in demand.</small>
                    {{-- fixed error key --}}
                    @error("adjustable_production.$product->id")
                      <small class="error_text">{{ $message }}</small>
                    @enderror
                  </div>
                </div>
              </div>
            </div>
          @endforeach
          <div class="product productZero">
            <div class="productTitle">
              <small>Postponed Production</small>
              <label for="heading">PARTIALLY COMPLETED PRODUCT</label>
              {{-- don't rely on $product from loop; use first product safely --}}
              <span>Industry: {{ optional($products->first()->industry)->name ?? '-' }}</span>
            </div>
            <div class="additionalNote">
              <p>Postponed production involves creating a partially completed product, called a "SHELL," at your firm's
                manufacturing plant. Postponed production occurs at your regional distribution centers and involves
                converting the SHELL into specific finished goods. With postponed production, the final product identity
                is assigned at the distribution center (DC), not at the manufacturing plant.</p>
            </div>
            <div class="inputContainer" style="padding-top: 15px;">
              <div class="inputText">
                <div class="pastQtr">
                  <label for="">Previous Qtr</label>
                  <input disabled type="text"
                    value="{{ number_format(optional($pzm_last_qtr)->quantity ?? 0) }} units">
                </div>
                <div class="currentQtr">

                  <label for="">Production Quantity</label>
                  <input type="text" onkeypress="return event.charCode >= 48 && event.charCode <= 57"
                    name="production_quantity_zero" id=""
                    value="{{ old('production_quantity_zero') ?? (optional($product_zero_manufacturing)->quantity ?? '') }}"
                    placeholder="Enter units">
                  <i class="fa fa-pencil" aria-hidden="true"></i>
                  <small class="help_text">&nbsp;</small>
                  @error('production_quantity_zero')
                    <small class="error_text">{{ $message }}</small>
                  @enderror
                </div>
              </div>
            </div>
          </div>
          <div class="actionSubmit">
            <button>Submit Manufacturing</button>
          </div>
        </div>
      </form>
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

      // Select the manufacturing form(s)
      const forms = document.querySelectorAll('form.manufacturingForm');

      // Build storage key - include simulation_id and quarter_id for uniqueness
      function storageKeyFor(form) {
        const sim =
          form.querySelector('input[name="simulation_id"]')?.value ||
          '{{ request()->route('simulation_id') }}';

        const q =
          form.querySelector('input[name="quarter_id"]')?.value ||
          '{{ request()->route('quarter_id') }}';

        const region = form.dataset.region || 'region_home';

        return `manufacturing_draft_sim${sim}_q${q}_${region}`;
      }

      // Read form values -> object (skip disabled + structural fields)
      function readForm(form) {
        const data = {};
        const els = form.querySelectorAll('input[name], textarea[name], select[name]');

        els.forEach(el => {
          if (el.disabled) return;

          const name = el.name;
          if (!name) return;

          // 🚫 CRITICAL FIX: never store product_id[]
          if (name === 'product_id[]') return;

          // Handle checkboxes
          if (el.type === 'checkbox') {
            if (name.endsWith('[]')) {
              data[name] = data[name] || [];
              if (el.checked) data[name].push(el.value);
            } else {
              data[name] = el.checked;
            }
            return;
          }

          // Handle radio buttons
          if (el.type === 'radio') {
            if (el.checked) data[name] = el.value;
            return;
          }

          // Normal inputs (text, number, select, etc.)
          data[name] = el.value;
        });

        return data;
      }

      // Fill form from stored data
      function fillForm(form, data) {
        if (!data) return;

        Object.keys(data).forEach(name => {
          // 🚫 Extra safety: never restore product_id[]
          if (name === 'product_id[]') return;

          const nodes = form.querySelectorAll(`[name="${name}"]`);
          if (!nodes || nodes.length === 0) return;

          if (Array.isArray(data[name])) {
            nodes.forEach(n => {
              if (n.type === 'checkbox') {
                n.checked = data[name].includes(n.value);
              }
            });
          } else {
            nodes.forEach(n => {
              if (n.type === 'checkbox') {
                n.checked = !!data[name];
              } else if (n.type === 'radio') {
                if (n.value === data[name]) n.checked = true;
              } else {
                n.value = data[name] ?? '';
              }
            });
          }
        });
      }

      // Save to localStorage
      function saveDraft(form) {
        try {
          const key = storageKeyFor(form);
          const payload = readForm(form);
          localStorage.setItem(key, JSON.stringify(payload));
        } catch (e) {
          console.warn('Could not save manufacturing draft', e);
        }
      }

      // Restore from localStorage
      function restoreDraft(form) {
        try {
          const key = storageKeyFor(form);
          const raw = localStorage.getItem(key);
          if (!raw) return;

          const data = JSON.parse(raw);
          fillForm(form, data);
        } catch (e) {
          console.warn('Could not restore manufacturing draft', e);
        }
      }

      // Clear draft on final submit
      function clearDraft(form) {
        try {
          const key = storageKeyFor(form);
          localStorage.removeItem(key);
        } catch (e) {
          /* ignore */
        }
      }

      // Attach handlers to each form
      forms.forEach(form => {

        // Restore existing draft on load
        restoreDraft(form);

        // Debounced save on input/change
        const debouncedSave = debounce(() => saveDraft(form), 450);
        form.addEventListener('input', debouncedSave);
        form.addEventListener('change', debouncedSave);

        // Clear draft on submit
        form.addEventListener('submit', function() {
          clearDraft(form);
        });
      });

      // Handle browser back/forward cache
      window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
          forms.forEach(f => restoreDraft(f));
        }
      });

    });
  </script>
@endsection
@include('student.simulation.decisionsModal.manufacturing')
