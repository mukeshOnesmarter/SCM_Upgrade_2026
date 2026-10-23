@extends('faculty.simulation.layout')
@section('title', 'Performance Overview')
@section('sectionTitle', 'Performance Overview – Quarter ' . request('quarter_id'))
@section('sectionDescription',
  'This graph shows a detailed comparison of all firms in the simulation, highlighting key
  metrics and performance trends across teams.')
@section('inheritTemplate')
  <div class="metricsOverview">
    <div class="quarterSelection">
      <ul>
        @foreach ($quarters as $item)
          @php
            $isDisabled = in_array($item->quarter_name, [1, 2]) || \Carbon\Carbon::parse($item->start_date)->isFuture();
          @endphp
          <li>
            @if ($isDisabled)
              <span class="disabled-quarter">
                QTR: {{ $item->quarter_name }}
                <small>
                  @if (in_array($item->quarter_name, [1, 2]))
                    Not Available
                  @else
                    Upcoming
                  @endif
                </small>
              </span>
            @else
              <a href="{{ route('faculty.courses.simulationPerformance', [
                    'course_id' => request('course_id'),
                    'quarter_id' => $item->quarter_name,
                ]) }}">
                <span>
                  QTR: {{ $item->quarter_name }}
                  <small>{{ $item->quarter_name == request('quarter_id') ? 'Selected' : '' }}</small>
                </span>
              </a>
            @endif
          </li>
        @endforeach
      </ul>
    </div>
    <div class="grpahic">
      @if (in_array(request('quarter_id'), [1, 2]))
        There is no overview generated for this quarter. It begins with QTR 3.
      @else
        <img src="{{ $metrics }}" alt="Performance Overview Graph">
      @endif
    </div>
  </div>
@endsection
