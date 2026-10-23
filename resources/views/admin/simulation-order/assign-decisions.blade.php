@extends('admin.layout.static')
@section('title', 'Simulation Order')
@section('staticTemplate')
  <div class="pageTitle">
    <h4>Create Simulation Order</h4>
    <p>Provide details for the simulation. These settings will be used to create teams, enroll learners, and schedule
      decision runs.</p>
  </div>

  <div class="templateCrud">
    <div class="dataForm">

      @foreach ($quarters as $q)
        @php $assignedForThis = $assigned[$q->id] ?? []; @endphp
        <form method="POST"
          action="{{ route('admin.simulation-order.quarter.save', ['order' => $order->id, 'quarter' => $q->id]) }}">
          @csrf
          <div class="card mb-3 p-3" data-quarter-number="{{ $q->quarter_number }}" data-quarter-id="{{ $q->id }}">
            <h5>Quarter {{ $q->quarter_number }}</h5>

            <div class="row">
              <div class="col-md-4">
                <label>Start date</label>
                <input type="text" class="form-control"
                  value="{{ \Carbon\Carbon::parse($q->start_date)->format('m/d/Y') }}" disabled>
                <input type="hidden" name="quarters[{{ $q->id }}][start_date]" value="{{ $q->start_date }}">
              </div>

              <div class="col-md-4">
                <label>End date</label>
                <input type="text" name="end_date" class="form-control"
                  value="{{ \Carbon\Carbon::parse($q->end_date)->format('m/d/Y') }}">
              </div>
            </div>

            <div class="mt-3">
              <label>Decisions</label>
              <div class="decisions-list">
                @foreach ($decisions as $d)
                  @php
                    $isAssigned = in_array($d->id, $assignedForThis);
                    $isMandatory = in_array($d->id, $mandatoryDecisionIds);
                  @endphp

                  <div class="form-check">
                    <input type="checkbox" name="decisions[]" value="{{ $d->id }}"
                      id="q{{ $q->id }}_d{{ $d->id }}" class="form-check-input"
                      {{ $isAssigned ? 'checked' : '' }} {{ $isMandatory ? 'checked disabled' : '' }}>
                    <label class="form-check-label" for="q{{ $q->id }}_d{{ $d->id }}">
                      {{ $d->name ?? 'Decision ' . $d->id }}
                      @if ($isMandatory)
                        <span class="text-muted">(mandatory)</span>
                      @endif
                    </label>
                  </div>
                @endforeach
              </div>
            </div>
            <div class="mt-3">
              <button type="submit" class="btn btn-primary">Save Quarter</button>
            </div>
          </div>
        </form>
      @endforeach
    </div>
  </div>
@endsection
