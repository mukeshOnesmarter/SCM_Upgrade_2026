@extends('student.layout')
@section('title', 'Simulation')
@section('template')
  <div class="invitationContainer">
    <div class="pageTitle">
      <h4>Your Simulation Activity</h4>
      <p>Track your active and upcoming simulations here.</p>
      <p>Monitor key dates, deadlines, progress updates, and simulation details to stay on track.</p>
    </div>
    @if (count($simulations) > 0)
      @foreach ($simulations as $item)
        <div class="invitation">
          <div class="item detail">
            <h6>{{ Str::ucfirst($item->simulation->title) }}</h6>
            <p><strong>Organization:</strong> {{ $item->simulation->organization }} | <strong>Faculty:</strong>
              {{ Str::ucfirst($item->faculty->first_name . ' ' . $item->faculty->last_name) }}</p>
            <p><strong>Simulation Starts On:</strong> {{ $item->simulation->start_date }} | <strong>Ends On:</strong>
              {{ $item->simulation->end_date }} | <strong>Duration: </strong>
              {{ str_pad($item->simulation->duration, 2, '0', STR_PAD_LEFT) }} Days </p>

            @if ($item->curent_quarter !== null)
              <p><strong>Quarter {{ $item->curent_quarter->quarter_name }} Deadline:</strong>
                {{ $item->curent_quarter->start_date }} | <strong>Ends:</strong> {{ $item->curent_quarter->end_date }} |
                @if ($item->curent_quarter->days_remaining == 0)
                  Ending Today
                @else
                  {{ str_pad($item->curent_quarter->days_remaining, 2, '0', STR_PAD_LEFT) }}
                  {{ Str::plural('Day', $item->curent_quarter->days_remaining) }} Remaining
                @endif
              </p>
            @endif

          </div>
          <div class="item status">
            @if ($item->curent_quarter !== null)
              <a
                href="{{ route('student.simulation.decisions.forecasting', [$item->simulation_id, $item->curent_quarter->quarter_name]) }}">View
                Detail</a>
            @else
              <a
                href="{{ route('student.simulation.simulation-dashboard', [$item->simulation_id, $item->simulation->quarters]) }}">Dashboard</a>
            @endif
          </div>
        </div>
      @endforeach
    @else
      <p>You have not participated in any simulation, and no simulation activity is currently available.</p>
    @endif
  </div>
@endsection
