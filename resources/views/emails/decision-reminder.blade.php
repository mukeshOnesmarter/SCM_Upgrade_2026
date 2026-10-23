@extends('emails.layout')
@section('title', "Decision Reminder – Q$quarterNameLabel")
@section('emailBody')
  <h4>Dear {{ $student->name ?? 'Student' }},</h4>

  <p>This is a friendly reminder regarding your simulation decisions for <strong>{{ $quarterNameLabel }}</strong>.</p>

  @php
    // Prefer a Carbon instance if provided
    $displayDate = null;
    $displayTime = null;
    $displayZone = $timezone ?? null;

    if (isset($closingAt) && method_exists($closingAt, 'format')) {
        $displayDate = $closingAt->format('l, F j, Y');
        $displayTime = $closingAt->format('g:i A');
        $displayZone = $closingAt->getTimezone()->getName();
    } else {
        // fallback to separate pieces if provided
        if (!empty($closingDate)) {
            // try to normalize date string, otherwise use as-is
            try {
                $d = \Carbon\Carbon::parse($closingDate);
                $displayDate = $d->format('l, F j, Y');
            } catch (\Exception $e) {
                $displayDate = $closingDate;
            }
        }
        if (!empty($closingTime)) {
            // try to normalize time if in H:i:s
            try {
                $t = \Carbon\Carbon::createFromFormat('H:i:s', $closingTime);
                $displayTime = $t->format('g:i A');
            } catch (\Exception $e) {
                $displayTime = $closingTime;
            }
        }
    }
@endphp

@if($displayDate || $displayTime)
    <div style="border:1px solid #e6e6e6; padding:12px; border-radius:6px; margin:12px 0; background:#fafafa;">
        <strong>Quarter Closing</strong><br>
        @if($displayDate)<div>Date: {{ $displayDate }}</div>@endif
        @if($displayTime)<div>Time: {{ $displayTime }}</div>@endif
        @if($displayZone)<div>Timezone: {{ $displayZone }}</div>@endif
    </div>
@endif

  <p>Below is the status of your saved and pending decisions:</p>

  {{-- Saved Decisions --}}
  @if (!empty($saved))
    <h4>Saved Decisions:</h4>
    <ul>
      @foreach ($saved as $item)
        <li>{{ ucwords(str_replace('_', ' ', $item)) }}</li>
      @endforeach
    </ul>
  @endif

  {{-- Not Saved Decisions --}}
  @if (!empty($notSaved))
    <h4 style="color: #d9534f;">Pending Decisions</h4>
    <p>Please complete the following items before the quarter closes:</p>
    <ul>
      @foreach ($notSaved as $item)
        <li><strong>{{ ucwords(str_replace('_', ' ', $item)) }}</strong></li>
      @endforeach
    </ul>
  @endif
  <br>
  <p>If you have already completed all decisions, you may ignore this email.</p>
@endsection
