@extends('student.simulation.layout')
@section('title', 'Forecasting')
@section('template')
  <div class="decisionTemplate">
    @include('student.simulation.decisions.partials.commonHeader')
    <div class="quarterUpcoming">
      <h4>Selected Quarter Not Started Yet</h4>
      <p>This quarter ({{ $commonHeader->quarter_selected }}) has not started yet and is scheduled to begin on
        <strong>{{ \Carbon\Carbon::parse($commonHeader->quarter_selected_info->start_date)->format('M d, Y') }}</strong>
      </p>

      <p>Please wait until the start date. Meanwhile, go through the quarter currently active and in process.<br>
        <a href="{{ route(Route::currentRouteName(), [$commonHeader->id, $commonHeader->quarter_inprocess->quarter_name]) }}"> Click</a> here to move to the currently active Quarter
      </p> 
      @if ($commonHeader->quarter_selected_info->days_remaining == 0)
        <span class="daysRemaing"> Ending Today</span>
      @else
        <div class="daysRemaing">
          <img src="{{ URL::asset('images/icon/days-remaining.svg') }}" alt="">
          <span> {{ str_pad($commonHeader->quarter_selected_info->days_remaining, 2, '0', STR_PAD_LEFT) }}
            {{ Str::plural('Day', $commonHeader->quarter_selected_info->days_remaining) }} Remaining</span>
        </div>
      @endif 
    </div> 
  </div>
@endsection
