@extends('faculty.simulation.layout')
@section('title', 'Student Group Members')
@section('sectionTitle', 'Student Group Members')
@section('sectionDescription',
  'Browse the list of students assigned to a specific group in the simulation. Understand
  team composition and monitor each student’s group participation.')
@section('inheritTemplate')
  @if (count($students) > 0)


    <div class="performanceOverview"> 
      <div class="item"><img src="{{ asset('images/icon/analytics.svg') }}" alt=""></div>
      <div class="item">
        <h6>Performance Overview <small>Monitor Firm Results</small></h6>
        <p>Click the button below to view a detailed performance overview of the selected firm in the simulation. Track key metrics, analyze performance trends, and gain insights into the firm’s results.</p> 
        <a
      href="{{ route('faculty.courses.groupPerformance', [
          'course_id' => request('course_id'),
          'group_id' => request('group_id'),
          'quarter_id' => 3,
      ]) }}">View Performance Overview</a>
      </div>
    </div>  

    <div class="dataListing">
      @foreach ($students as $item)
        <div class="listingRow">
          <div class="item title">
            <label for="heading">{{ $item->student->first_name . ' ' . $item->student->last_name }}</label>
            <small>{{ $item->student->email }}</small>
          </div>
          <div class="item action">
            <a
              href="{{ route('faculty.courses.studentProfile', ['course_id' => $simulationOverview->id, 'student_id' => $item->student->id]) }}">
              <i class="fa fa-angle-right" aria-hidden="true"></i></a>
          </div>
        </div>
      @endforeach
    </div>
  @else
    <p>No Record Found.</p>
  @endif
@endsection
