@extends('faculty.layout')
@section('title', 'Courses')
@section('template')

  <div class="coursesTemplate">
    @if (count($simulations) > 0)
      <div class="courseListing">
        @foreach ($simulations as $item)
          <div class="course {{ $loop->iteration == 1 ? 'active' : '' }}">
            <div class="quickInfo">
              <h5>{{ $item->title }}</h5>
              <div class="wrapTitle">
                @if (\Carbon\Carbon::parse($item->start_date) <= now())
                  <span
                    class="{{ $item->simulation_status == 'In Progress' ? 'status_progress' : 'status_completed' }}">Course
                    Status: {{ $item->simulation_status }}</span>
                @else
                  <span>Simulation Not Started Yet.</span>
                @endif
              </div>
              @if ($item->simulationTeams)
                <div class="groupsWrap">
                  <div class="groups">
                    @foreach ($item->simulationTeams->take(3) as $team)
                      <div class="group">
                        <span>GROUP 0{{ $team->team_name }}</span>
                        {{ $team->team_members ? $team->team_members . '-Students Team' : 'No Student Added Yet' }}
                      </div>
                    @endforeach
                  </div>
                </div>
              @endif
            </div>
            <div class="expandInfo">
              <a href="{{ route('faculty.courses.show', $item->id) }}">View</a>
            </div>
          </div>
        @endforeach
      </div>
    @else
      <p>No Record Found.</p>
    @endif
  </div>
@endsection
