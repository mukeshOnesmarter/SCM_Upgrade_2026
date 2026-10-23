<div class="performanceOverview">
  <div class="item"><img src="{{ asset('images/icon/analytics.svg') }}" alt=""></div>
  <div class="item">
    <h6>Performance Overview <small>Monitor Group Results</small></h6>
    <p>Click the button below to view a detailed performance overview of all firms in the simulation. Analyze key
      metrics, compare team results, and gain insights into each group’s performance trends.</p>
    <a
      href="{{ route('faculty.courses.simulationPerformance', [
          'course_id' => request('course_id'),
          'quarter_id' => 3,
      ]) }}">View
      Performance Overview</a>

    | <a
      href="{{ route('faculty.courses.firmScorecard', [
          'course_id' => request('course_id'),
          'quarter_id' => 1,
      ]) }}">Firm
      Dashboard KPIs Performance Summary</a>
  </div>
</div>
