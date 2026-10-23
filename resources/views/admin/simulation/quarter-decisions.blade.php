@extends('admin.layout')
@section('title', 'Quarter Decisions')
@section('template')

  @if (Session::has('message'))
    <p class="message" id="successMessage">{{ Session::get('message') }}</p>
  @endif

  @if (Session::has('error'))
    <p class="message" id="errorMessage">{{ Session::get('error') }}</p>
  @endif

  @if (Session::has('setupInitialDemand'))
    <p class="message" id="errorMessage">{{ Session::get('setupInitialDemand') }}</p>
  @endif

  <div class="wraptemplateBody">
    <div class="templateBody">
      <div class="simulationTemplate" id="simulationTemplate">
        {{-- COURSE HEADER --}}
        <div class="commonHeader">
          <h3>{{ Str::upper($course->title) }}</h3>
          <p><strong>Session Code:</strong> {{ $course->simulation_code }} | <strong>Organization:</strong>
            {{ $course->organization }} | <strong>Start Date:</strong> {{ $course->start_date }} | <strong>End
              Date:</strong> {{ $course->end_date }} </p>
        </div>
        {{-- PAGETITLE --}}
        <div class="pageTitle">
          <h4>Quarter Decisions</h4>
          <p>Configure which strategic decisions are enabled for each simulation quarter. Mandatory decisions are
            pre-selected, while advanced decisions can be activated from Quarter 4 onward to gradually increase complexity
            and realism for participants.</p>
        </div>
      </div>
      <div class="templateCrud">
        <div class="dataForm">
          <form action="{{ route('admin.courses.saveQuarterDecisions', $course->id) }}" method="POST">
            @csrf
            @php
              $mandatoryIds = [1, 2, 3]; // adjust if your Decision IDs differ
            @endphp
            <div class="quarterDecisions">
            @foreach ($quarters as $quarter)
              @php
                $isLocked = (int) $quarter->quarter_name < 4;
                $isLast = (int) $quarter->quarter_name === (int) $lastQuarterNumber;
                $selected = $quarter->decisions_array ?? [];
              @endphp
              <div class="quarter">
                <label>
                  Quarter {{ $quarter->quarter_name }}
                  @if ($isLocked)
                    | Controlled Simulation (read-only)
                  @endif
                  @if ($isLast)
                    | Final Quarter (read-only)
                  @endif
                </label> 
                <ul class="{{ $isLocked || $isLast ? 'text-muted' : '' }}">
                  {{-- If read-only (first 3 or last) — show checked disabled checkboxes but DO NOT include hidden inputs --}}
                  @if ($isLocked || $isLast)
                    @foreach ($decisions as $decision)
                      {{-- show checked if stored, otherwise show checked for mandatory ones (visual only) --}}
                      @php
                        $checked =
                            in_array($decision->id, $selected) || in_array($decision->id, $mandatoryIds) || $isLast;
                      @endphp
                      <li>
                        <label>
                          <input type="checkbox" {{ $checked ? 'checked' : '' }} disabled>
                          {{ $decision->title }}
                        </label>
                      </li>
                    @endforeach
                  @else
                    {{-- Editable quarters (4..last-1): include hidden inputs for mandatory decisions and editable checkboxes for optional ones --}}
                    @foreach ($decisions->whereIn('id', $mandatoryIds) as $decision)
                      <li>
                        <label>
                          <input type="checkbox" checked disabled>
                          {{ $decision->title }}
                        </label>
                        {{-- hidden input to ensure mandatory decisions are posted for editable quarters --}}
                        <input type="hidden" name="decisions[{{ $quarter->id }}][]" value="{{ $decision->id }}">
                      </li>
                    @endforeach

                    @foreach ($decisions->whereNotIn('id', $mandatoryIds) as $decision)
                      <li>
                        <label>
                          <input type="checkbox" name="decisions[{{ $quarter->id }}][]" value="{{ $decision->id }}"
                            {{ in_array($decision->id, $selected) ? 'checked' : '' }}>
                          {{ $decision->title }}
                        </label>
                      </li>
                    @endforeach
                  @endif
                </ul>
              </div>
            @endforeach 
            </div>
            <div class="dataRow action_button">
              <input type="hidden" name="simulation_id" value="{{ $course->id }}">
              <button class="btn btn-primary" type="submit">Save Decisions</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
