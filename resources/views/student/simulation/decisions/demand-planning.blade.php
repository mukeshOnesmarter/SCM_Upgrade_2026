@extends('student.simulation.layout')
@section('title', 'Demand Planning')
@section('template')
  <div class="decisionTemplate">
    @include('student.simulation.decisions.partials.commonHeader')
    <div class="demandplanningSection">
      @include('student.simulation.decisions.partials.last-saved')
      <form class="demandPlanningForm"
        action="{{ route('student.simulation.decisions.save-demand-planning', [request()->route('simulation_id'), request()->route('quarter_id')]) }}"
        method="POST" autocomplete="off" enctype="multipart/form-data" id="submitForm"
        data-quarter="{{ request()->route('quarter_id') }}">
        @csrf
        <input type="hidden" name="simulation_id" value="{{ request()->route('simulation_id') }}">
        <input type="hidden" name="quarter_id" value="{{ request()->route('quarter_id') }}">
        <div class="regionWrapper">
          @foreach ($regions as $region)
            <input type="hidden" name="region[{{ $region->id }}]" value="{{ $region->id }}">
            <div class="region">
              <div class="regionTitle">
                <label for="heading">Region - {{ str_pad($region->id, '2', '0', STR_PAD_LEFT) }} 
                  <small>Use this panel to confirm your product's status and set pricing and marketing levels for the
                    Retail and Direct channels.</small>
                </label>
              </div>
              <div class="productWrapper">
                @foreach ($region->products as $product)
                  <input type="hidden" name="product[{{ $product->id }}]" value="{{ $product->id }}">
                  <div class="product">
                    <div class="productTitle">
                      <small>PRODUCT - {{ str_pad($product->id, '2', '0', STR_PAD_LEFT) }} </small>
                      <label for="heading">{{ $product->name }} </label>
                    </div>
                    <div class="demandWrapper">
                      @foreach ($product->demands as $demand)
                        <input type="hidden" name="demand[{{ $demand->id }}]" value="{{ $demand->id }}">
                        <div class="demand">
                          <label for="title">Channel: {{ $demand->title }}</label>
                          <div class="previousQuarter">
                            <label for="">Previous Quarter: Q{{ request()->route('quarter_id') - 1 }}</label>
                            <ul>
                              <li><span>Product Status</span>
                                {{ $demand->previous_status == 'yes' ? 'Active' : 'Inactive' }}</li>
                              <li><span>Product Price</span> ${{ $demand->previous_price }}</li>
                              <li><span>Mkt Spending</span> {{ number_format($demand->previous_marketSpending) }}</li>
                            </ul>
                          </div>
                          <div class="inputContainer">
                            <div class="caputreInput">
                              <label for="title">Product Status Active / Inactive</label>
                              <div class="selectbox">
                                <select
                                  name="{{ 'status_region_' . $region->id . '_product_' . $product->id . '_demand_' . $demand->id }}"
                                  id="">
                                  <option value="yes"
                                    {{ old('status_region_' . $region->id . '_product_' . $product->id . '_demand_' . $demand->id) == 'yes' ? 'selected' : '' }}
                                    {{ $demand->current_status == 'yes' ? 'selected' : '' }}>
                                    Yes</option>
                                  <option value="no"
                                    {{ old('status_region_' . $region->id . '_product_' . $product->id . '_demand_' . $demand->id) == 'no' ? 'selected' : '' }}
                                    {{ $demand->current_status == 'no' ? 'selected' : '' }}>
                                    No</option>
                                </select>
                              </div>
                            </div>
                            <div class="caputreInput">
                              <label for="title">Product Price ($)</label>
                              <input onkeypress="return event.charCode >= 48 && event.charCode <= 57" type="text"
                                name="{{ 'price_region_' . $region->id . '_product_' . $product->id . '_demand_' . $demand->id }}"
                                value="{{ old('price_region_' . $region->id . '_product_' . $product->id . '_demand_' . $demand->id, $demand->current_price ?? null) }}">
                            </div>
                            <div class="caputreInput">
                              <label for="title">Mkt Spending ($)</label>
                              <input onkeypress="return event.charCode >= 48 && event.charCode <= 57" type="text"
                                name="{{ 'marketSpending_region_' . $region->id . '_product_' . $product->id . '_demand_' . $demand->id }}"
                                value="{{ old('marketSpending_region_' . $region->id . '_product_' . $product->id . '_demand_' . $demand->id, $demand->current_marketSpending ?? null) }}">
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
        <button>Saves your pricing and marketing decisions for all regions</button>
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

      // Pick the demand planning form(s)
      const forms = document.querySelectorAll('form.demandPlanningForm');

      // Build unique storage key for a form (simulation + quarter)
      function storageKeyFor(form) {
        const sim = form.dataset.simulation || form.querySelector('input[name="simulation_id"]')?.value ||
          '{{ request()->route('simulation_id') }}';
        const q = form.dataset.quarter || form.querySelector('input[name="quarter_id"]')?.value ||
          '{{ request()->route('quarter_id') }}';
        return `demandplanning_draft_sim${sim}_q${q}`;
      }

      // Read values from form into object (skip disabled)
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
            // unchecked radios ignored
          } else {
            data[name] = el.value;
          }
        });
        return data;
      }

      // Fill form from stored data object
      function fillForm(form, data) {
        if (!data) return;
        Object.keys(data).forEach(name => {
          // Use querySelectorAll to handle names used multiple times (arrays)
          const nodes = form.querySelectorAll(`[name="${name}"]`);
          if (!nodes || nodes.length === 0) return;

          if (Array.isArray(data[name])) {
            nodes.forEach(n => {
              if (n.type === 'checkbox') {
                n.checked = data[name].includes(n.value);
              } else {
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

      // Save draft to localStorage
      function saveDraft(form) {
        try {
          const key = storageKeyFor(form);
          const payload = readForm(form);
          localStorage.setItem(key, JSON.stringify(payload));
          // Optional: small UI indicator can be shown here
          // console.log('Draft saved', key);
        } catch (e) {
          console.warn('Could not save demand planning draft', e);
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
          console.warn('Could not restore demand planning draft', e);
        }
      }

      // Clear draft on successful submit
      function clearDraft(form) {
        try {
          const key = storageKeyFor(form);
          localStorage.removeItem(key);
        } catch (e) {
          /* ignore */ }
      }

      // Attach handlers to each demand planning form
      forms.forEach(form => {
        // Restore on load (works even if fields are hidden)
        restoreDraft(form);

        // Debounced autosave for inputs and changes (selects/radios)
        const debouncedSave = debounce(() => saveDraft(form), 450);
        form.addEventListener('input', debouncedSave);
        form.addEventListener('change', debouncedSave);

        // Clear draft on submit
        form.addEventListener('submit', function() {
          clearDraft(form);
          // allow submit to proceed normally
        });
      });

      // Re-restore when pageshow from BFCache/back-button
      window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
          forms.forEach(f => restoreDraft(f));
        }
      });

    });
  </script>
@endsection
@include('student.simulation.decisionsModal.demand-planning')
