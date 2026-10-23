@extends('faculty.simulation.layout')
@section('title', 'Group Performance Overview')
@section('sectionTitle', 'Group ' . request('group_id') . ': Quarterly Performance Overview')
@section('sectionDescription',
  'This graph highlights the performance of the selected group, showing key metrics and
  trends over the simulation period.')
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
              <a
                href="{{ route('faculty.courses.groupPerformance', [
                    'course_id' => request('course_id'),
                    'group_id' => request('group_id'),
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
