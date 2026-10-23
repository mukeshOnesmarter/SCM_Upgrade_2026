@extends('student.simulation.layout')
@section('title', 'Information Technology in Supply Chain')
@section('template')
  <div class="decisionTemplate">
    @include('student.simulation.decisions.partials.commonHeader')
    <div class="itSection">
      @include('student.simulation.decisions.partials.last-saved')
      <form class="itForm"
        action="{{ route('student.simulation.decisions.save-information-technology', [request()->route('simulation_id'), request()->route('quarter_id')]) }}"
        method="POST" enctype="multipart/form-data" autocomplete="off" id="submitForm"
        data-simulation="{{ request()->route('simulation_id') }}" data-quarter="{{ request()->route('quarter_id') }}">
        @csrf
        <input type="hidden" name="simulation_id" value="{{ request()->route('simulation_id') }}">
        <input type="hidden" name="quarter_id" value="{{ request()->route('quarter_id') }}">

        <div class="itServices">
          <div class="servicesType">
            <div class="sectionHeading">
              <label for="heading">Choose IT sync status for: <small> Sub-Assembly Component Suppliers</small></label>
              <label for="previousquarter">Qtr {{ request()->route('quarter_id') - 1 }}</label>
              <label for="quarter">Current Qtr</label>
            </div>
            @foreach ($suppliers as $supplier)
              <input type="hidden" name="suppliers[]" value="{{ $supplier->id }}">
              <div class="supplier">
                <div class="item supplierSynchronization">
                  Supplier ({{ $supplier->name }})
                  <p><strong>Materials Supplied:</strong>
                    @foreach ($supplier->supplierComponent as $index => $component)
                      {{ ucwords($component->subAssemblyComponent->name) }}
                      @if (!$loop->last)
                        ,
                      @endif
                    @endforeach
                  </p>
                </div>
                {{-- Previous Quarter --}}
                @if ($synchronizationPrevious && $synchronizationPrevious->suppliers->isNotEmpty())
                  @if ($synchronizationPrevious->suppliers->contains('supplier_id', $supplier->id))
                    <div class="item previousQtr">Yes</div>
                  @else
                    <div class="item previousQtr">No</div>
                  @endif
                @endif

                @if ($synchronization && $synchronization->suppliers->isNotEmpty())
                  <div class="item labelSelection">
                    <label for="yes{{ $supplier->id }}">
                      <input @if ($synchronization->suppliers->contains('supplier_id', $supplier->id)) checked @endif name="supplier_{{ $supplier->id }}"
                        type="radio" value="yes" id="yes{{ $supplier->id }}">
                      Yes</label>
                    <label for="no{{ $supplier->id }}">
                      <input @if (!$synchronization->suppliers->contains('supplier_id', $supplier->id)) checked @endif name="supplier_{{ $supplier->id }}"
                        type="radio" value="no" id="no{{ $supplier->id }}">
                      No</label>
                  </div>
                @else
                  <div class="item labelSelection">
                    <label for="yes{{ $supplier->id }}">
                      <input name="supplier_{{ $supplier->id }}" type="radio" value="yes"
                        id="yes{{ $supplier->id }}">
                      Yes</label>
                    <label for="no{{ $supplier->id }}">
                      <input checked name="supplier_{{ $supplier->id }}" type="radio" value="no"
                        id="no{{ $supplier->id }}">
                      No</label>
                  </div>
                @endif
              </div>
            @endforeach
          </div>
          @if ($distributioncenters->isNotEmpty())
            <div class="servicesType">
              <div class="sectionHeading">
                <label for="heading">Choose IT sync status for:<small>Plant-to-DC Transportation Carriers</small></label>
                <label for="previousquarter">Qtr {{ request()->route('quarter_id') - 1 }}</label>
                <label for="quarter">Current Qtr</label>
              </div>
              @foreach ($carriers as $carrier)
                <input type="hidden" name="carrier[]" value="{{ $carrier->id }}">
                <div class="carrier applyborder">
                  <div class="item carrierSynchronization">
                    Carrier ({{ $carrier->name }})
                    <p><strong>Plant-To-DC:</strong> Synchronization</p>
                  </div>
                  {{-- Previous Quarter --}}
                  @if ($synchronizationPrevious && $synchronizationPrevious->carriers->isNotEmpty())
                    @if ($synchronizationPrevious->carriers->contains('carrier_id', $carrier->id))
                      <div class="item previousQtr">Yes</div>
                    @else
                      <div class="item previousQtr">No</div>
                    @endif
                  @endif

                  @if ($synchronization && $synchronization->carriers->isNotEmpty())
                    <div class="item labelSelection">
                      <label for="yes{{ $carrier->id }}">
                        <input @if ($synchronization->carriers->contains('carrier_id', $carrier->id)) checked @endif name="carrier_{{ $carrier->id }}"
                          type="radio" value="yes" id="yes{{ $carrier->id }}">
                        Yes</label>
                      <label for="no{{ $carrier->id }}">
                        <input @if (!$synchronization->carriers->contains('carrier_id', $carrier->id)) checked @endif name="carrier_{{ $carrier->id }}"
                          type="radio" value="no" id="no{{ $carrier->id }}">
                        No</label>
                    </div>
                  @else
                    <div class="item labelSelection">
                      <label for="yes{{ $carrier->id }}">
                        <input name="carrier_{{ $carrier->id }}" type="radio" value="yes"
                          id="yes{{ $carrier->id }}">
                        Yes</label>
                      <label for="no{{ $carrier->id }}">
                        <input checked name="carrier_{{ $carrier->id }}" type="radio" value="no"
                          id="no{{ $carrier->id }}">
                        No</label>
                    </div>
                  @endif

                </div>
              @endforeach
            </div>
          @else
            <div class="servicesType">
              <div class="sectionHeading">
                <label for="heading">Choose IT sync status for:<small>Plant-to-DC Transportation Carriers</small></label>
                <label for="">&nbsp;</label>
                <label for="quarter">&nbsp;</label>
              </div>
              <div class="notice applyborder" style="padding:1rem;">
                <p><strong>No Local Distribution Center:</strong> All orders are fulfilled from the main plant, resulting in higher air-shipment costs.</p>
              </div>
            </div>
          @endif
          <div class="servicesType note">
            <small>These decisions affect delivery reliability and component failure rates for suppliers, and surface
              shipping performance for carriers.</small>
          </div>
          <div class="servicesType actionButton">
            <button>Save Information Technology</button>
          </div>
        </div>
      </form>
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

      // All IT forms (likely just one)
      const forms = document.querySelectorAll('form.itForm');

      // Unique localStorage key builder
      function storageKeyFor(form) {
        const sim = form.dataset.simulation || form.querySelector('input[name="simulation_id"]')?.value ||
          '{{ request()->route('simulation_id') }}';
        const q = form.dataset.quarter || form.querySelector('input[name="quarter_id"]')?.value ||
          '{{ request()->route('quarter_id') }}';
        return `informationtechnology_draft_sim${sim}_q${q}`;
      }

      // Read form values (handles radios, checkboxes, selects, inputs)
      function readForm(form) {
        const data = {};
        const els = form.querySelectorAll('input[name], textarea[name], select[name]');
        els.forEach(el => {
          if (el.disabled) return;
          const name = el.name;
          if (!name) return;

          if (el.type === 'checkbox') {
            // single checkbox -> boolean, name[] -> array
            if (name.endsWith('[]')) {
              data[name] = data[name] || [];
              if (el.checked) data[name].push(el.value);
            } else {
              data[name] = el.checked;
            }
            return;
          }

          if (el.type === 'radio') {
            if (el.checked) data[name] = el.value;
            return;
          }

          if (name.endsWith('[]')) {
            data[name] = data[name] || [];
            data[name].push(el.value);
          } else {
            data[name] = el.value;
          }
        });
        return data;
      }

      // Fill form from saved data
      function fillForm(form, data) {
        if (!data) return;
        Object.keys(data).forEach(name => {
          const nodes = form.querySelectorAll(`[name="${name}"]`);
          if (!nodes || nodes.length === 0) return;
          const val = data[name];

          if (Array.isArray(val)) {
            nodes.forEach((n, idx) => {
              if (n.type === 'checkbox') n.checked = val.includes(n.value);
              else if (n.type === 'radio') n.checked = val.includes(n.value);
              else n.value = val[idx] ?? val[0] ?? '';
            });
          } else {
            nodes.forEach(n => {
              if (n.type === 'checkbox') n.checked = !!val;
              else if (n.type === 'radio') {
                if (n.value === val) n.checked = true;
              } else {
                n.value = val ?? '';
              }
            });
          }
        });
      }

      // Save / restore / clear functions
      function saveDraft(form) {
        try {
          const key = storageKeyFor(form);
          localStorage.setItem(key, JSON.stringify(readForm(form)));
        } catch (e) {
          console.warn('Could not save IT draft', e);
        }
      }

      function restoreDraft(form) {
        try {
          const key = storageKeyFor(form);
          const raw = localStorage.getItem(key);
          if (!raw) return;
          fillForm(form, JSON.parse(raw));
        } catch (e) {
          console.warn('Could not restore IT draft', e);
        }
      }

      function clearDraft(form) {
        try {
          const key = storageKeyFor(form);
          localStorage.removeItem(key);
        } catch (e) {}
      }

      // Attach handlers
      forms.forEach(form => {
        restoreDraft(form);

        const debouncedSave = debounce(() => saveDraft(form), 350);
        form.addEventListener('input', debouncedSave);
        form.addEventListener('change', debouncedSave);

        form.addEventListener('submit', function() {
          clearDraft(form);
          // allow submit to continue
        });
      });

      // Restore again if the page is loaded from BFCache (back button)
      window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
          forms.forEach(f => restoreDraft(f));
        }
      });
    });
  </script>

@endsection
@include('student.simulation.decisionsModal.information-technology')
