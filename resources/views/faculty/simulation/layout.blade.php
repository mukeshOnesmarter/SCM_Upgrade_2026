@extends('faculty.layout')
@section('title', 'Courses')
@section('template')
  <div class="coursesTemplate">
    {{-- simulationHeader --}}
    <div class="simulationHeader">
      <div class="courseOverview">
        <div class="courseInfo">
          <div class="item"><span class="lable">COURSE</span> SESSION NUMBER - <span class="text-primary">
              {{ $simulationOverview->simulation_code }}</span> | TIMEZONE: <span>{{ str_replace('_', ' ', $simulationOverview->timezone) }}</span></div>
          <h4>{{ $simulationOverview->title }}</h4>
          <div class="item"><span class="text-gray">ORGANIZATION:</span>
            {{ ucfirst($simulationOverview->organization) }}</div>
        </div>
        <div class="shareCode">
          <strong>{{ $simulationOverview->share_code }}</strong>
          <small>CLICK TO COPY</small>
        </div>
      </div>
    </div>
    {{-- sectionContainer --}}
    <div class="sectionContainer">
      @hasSection('sectionTitle')
        <div class="sectionTitle">
          <h4>@yield('sectionTitle', 'Default Title')</h4>
          <p>@yield('sectionDescription', 'Default description text.')</p>
          <a href="{{ route('faculty.courses.show', $simulationOverview->id) }}"><i class="fa fa-angle-left"
              aria-hidden="true"></i> Back to Simulation Details</a>
        </div>
      @endif

      @yield('inheritTemplate')
    </div>
  </div>
@endsection
