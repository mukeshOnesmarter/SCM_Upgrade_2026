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
          <h4>Select Primary Faculty</h4>
          <p>Choose the primary faculty member responsible for managing this simulation. Additional co-faculty members can be added later to assist in monitoring
            and guiding the simulation activities.</p>
        </div>
        <form class="facultySelection" id="createRecord" method="POST"
          action="{{ route('admin.courses.saveFaculty', $course->id) }}" enctype="multipart/form-data" autocomplete="off">
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
            <button type="submit">Save Faculty</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection
