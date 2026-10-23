  @extends('student.simulation.layout')
  @section('title', 'Performance Trends')
  @section('template')
    <div class="decisionTemplate">
      @include('student.simulation.decisions.partials.commonHeader')
      <div class="dashboardTemplate">
        <div class="tabs">
          <ul>
            <li><a
                href="{{ route('student.simulation.simulation-dashboard', [
                    'simulation_id' => request('simulation_id'),
                    'quarter_id' => request('quarter_id'),
                ]) }}"><i
                  class="fa fa-table" aria-hidden="true"></i> SCM Performance Table</a>
            </li>
            <li>|</li>
            <li><i class="fa fa-bar-chart" aria-hidden="true"></i> SCM Performance Trends</li>
          </ul>
        </div>
        @if (in_array(request('quarter_id'), [1, 2]))
          <p>Performance graphs are available from Quarter 3. No graph is shown for this quarter.</p>
        @else
          <img src="{{ $performanceTrend }}" alt="Performance Trends">
        @endif
      </div>
    </div>
  @endsection
