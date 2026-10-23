@extends('admin.layout')
@section('title', 'Add New Course')
@section('template')
  <div class="wraptemplateBody">
    <div class="templateBody">
      <div class="simulationTemplate" id="simulationTemplate">
        {{-- COURSE HEADER --}}
        <div class="commonHeader">
          <h3>{{ Str::upper($course->title) }}</h3>
          <p><strong>Session Code:</strong> {{ $course->simulation_code }} | <strong>Organization:</strong>
            {{ $course->organization }} | <strong>Start Date:</strong> {{ $course->start_date }} | <strong>End
              Date:</strong> {{ $course->end_date }} </p>
        </div>
        {{-- PAGETITLE --}}
        <div class="pageTitle">
          <h4>Add Co-Faculty Members</h4>
          <p>Select additional faculty members who will assist the primary faculty in managing and monitoring this simulation. Co-faculty can support student
            activities, review progress, and help oversee the simulation.</p>
        </div>
        <form class="facultySelection" id="createRecord" method="POST"
          action="{{ route('admin.simulation.saveCoFaculty', $course->id) }}" enctype="multipart/form-data" autocomplete="off">
          @csrf
          <input type="hidden" name="simulation_id" value="{{ $course->id }}">
          @foreach ($faculties as $item)
            <div class="faculty">
              <div class="item">
                <input type="radio" name="faculty" value="{{ $item->id }}"
                  {{ old('faculty') == $item->id ? 'checked' : '' }}>
              </div>
              <div class="item title">
                <label for="title">{{ $item->first_name . ' ' . $item->last_name }}</label>
                <small><strong>Department:</strong> {{ $item->department }} | <strong>Designation:</strong>
                  {{ $item->designation }}</small>
                <small><strong>Email:</strong> {{ $item->email }}</small>
              </div>
            </div>
          @endforeach
          <div class="actionButton">
            <button type="submit">Add Co-Faculty</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection
