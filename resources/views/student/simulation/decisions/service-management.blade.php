@extends('student.simulation.layout')
@section('title', 'Service Management')
@section('template')
  <div class="decisionTemplate">
    @include('student.simulation.decisions.partials.commonHeader')
    <div class="servicemanagementTemplate">
      @include('student.simulation.decisions.partials.last-saved')
      <form
      class="serviceForm"
      action="{{ route('student.simulation.decisions.save-service-management', [request()->route('simulation_id'), request()->route('quarter_id')]) }}"
      method="POST" autocomplete="off" enctype="multipart/form-data" id="submitForm"
      data-simulation="{{ request()->route('simulation_id') }}"
      data-quarter="{{ request()->route('quarter_id') }}">
        @csrf
        <input type="hidden" name="simulation_id" value="{{ request()->route('simulation_id') }}">
        <input type="hidden" name="quarter_id" value="{{ request()->route('quarter_id') }}">
        <div class="regionWrapper">
          @foreach ($regions as $region) 
            <input type="hidden" name="region[{{ $region->id }}]" value="{{ $region->id }}">
            <div class="region">
              <div class="regionTitle">
                <label for="heading">Region - {{ str_pad($region->id, '2', '0', STR_PAD_LEFT) }}<small>-REGIONAL SERVICE LEVEL SELECTION-</small></label> 
              </div>
              
              <div class="serviceWrapper">
                <div class="service previous_service">
                  Previous Quarter's Service Level: {{ $region->previous_service }} 
                </div>
                @foreach ($region->service_outsourcing as $item)
                  <div class="service"> 
                    <div class="item">
                      <input type="radio" name="service_region_{{ $region->id }}"
                         {{ $region->service_assign_id !== null ? ($region->service_assign_id == $item->id ? 'checked' : '') : ($item->id === 1 ? 'checked' : '') }}
                        value="{{ $item->id }}">
                    </div>
                    <div class="item">
                      <span> {{ $item->level_title }} 
                        <i class="fa fa-question-circle fa-sm text-primary" tabindex="0" role="button" data-bs-toggle="popover" data-bs-trigger="focus"
                        data-bs-html="true" data-bs-placement="top"  
                        data-bs-content='
                          <div class="m-0 p-0">
                            <div class="m-0 p-0">{{ addslashes($item->description ?? 'No description available.') }}</div> 
                          </div>'>
                        </i>
                      </span>
                    </div> 
                    @if ($region->service_assign_id !== null && $region->service_assign_id == $item->id)
                     <div class="item current_status">
                        <img src="{{ asset('images/icon/check-green.svg') }}" alt="">
                       <small>Selected Currentely </small>
                      </div>
                    @endif
                  </div>
                @endforeach
                <div class="service">
                  <div class="item">
                    <input type="radio" name="service_region_{{ $region->id }}"
                      {{ $region->service_assign_id !== null && $region->service_assign_id == 0 ? 'checked' : '' }}
                      value="0">
                  </div>
                  <div class="item">None
                    <i class="fa fa-question-circle fa-sm text-primary" tabindex="0" role="button" data-bs-toggle="popover" data-bs-trigger="focus"
                      data-bs-html="true" data-bs-placement="top"  
                      data-bs-content='
                      <div class="m-0 p-0">
                        <div class="m-0 p-0">No call center support provided in this region</div> 
                      </div>'>
                    </i>
                  </div>
                </div>
              </div>
            </div>
          @endforeach
        </div>
        <div class="actionButton">
          <button>Apply your selected service levels for the current quarter</button>
        </div>
        <p>&nbsp;</p>
      </form>
    </div>
  </div>
@endsection
@section('templateScript')
<script>
document.addEventListener('DOMContentLoaded', function() {
  function debounce(fn, wait) {
    let t;
    return function() {
      clearTimeout(t);
      t = setTimeout(() => fn.apply(this, arguments), wait);
    };
  }

  const forms = document.querySelectorAll('form.serviceForm');

  function storageKeyFor(form) {
    const sim = form.dataset.simulation || form.querySelector('input[name="simulation_id"]')?.value ||
      '{{ request()->route("simulation_id") }}';
    const q = form.dataset.quarter || form.querySelector('input[name="quarter_id"]')?.value ||
      '{{ request()->route("quarter_id") }}';
    return `servicemanagement_draft_sim${sim}_q${q}`;
  }

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

  function saveDraft(form) {
    try {
      const key = storageKeyFor(form);
      localStorage.setItem(key, JSON.stringify(readForm(form)));
    } catch (e) { console.warn('Could not save service draft', e); }
  }

  function restoreDraft(form) {
    try {
      const key = storageKeyFor(form);
      const raw = localStorage.getItem(key);
      if (!raw) return;
      fillForm(form, JSON.parse(raw));
    } catch (e) { console.warn('Could not restore service draft', e); }
  }

  function clearDraft(form) {
    try {
      const key = storageKeyFor(form);
      localStorage.removeItem(key);
    } catch (e) {}
  }

  forms.forEach(form => {
    restoreDraft(form);

    const debouncedSave = debounce(() => saveDraft(form), 350);
    form.addEventListener('input', debouncedSave);
    form.addEventListener('change', debouncedSave);

    form.addEventListener('submit', function() {
      clearDraft(form);
      // allow submit to proceed
    });
  });

  window.addEventListener('pageshow', function(event) {
    if (event.persisted) {
      forms.forEach(f => restoreDraft(f));
    }
  });
});
</script>

@endsection
@include('student.simulation.decisionsModal.service-management')
