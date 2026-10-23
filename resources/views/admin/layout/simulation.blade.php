@extends('admin.layout')
@section('template')
  <div class="courseDetail">
    <div class="courseTitle">
      <div class="titleContainer">
        <div class="item">
          <h5>{{ $course->title }}</h5>
          <p><strong>Managing Entity:</strong> {{ $course->organization }}</p>
          <p><strong>Start Date:</strong> {{ $course->start_date }} | <strong>End Date:</strong> {{ $course->end_date }}
          </p>
        </div>
      </div>
    </div>
    @yield('simulationDetail')
  </div>
@endsection
