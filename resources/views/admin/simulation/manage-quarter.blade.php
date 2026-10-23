@extends('admin.layout')
@section('title', 'Manage Quarters Schedule')
@section('template')
  <div class="wraptemplateBody">
    <div class="templateBody">
      <div class="simulationTemplate" id="simulationTemplate">
        <div class="manageQuarter">
          <div class="alertNot">
            <h4>Manage Simulation Quarter</h4>
            <p>Based on your selected simulation start date and the number of quarters ({{ $course->quarters }}),
    all quarters are automatically scheduled with a gap of 4 days between each quarter.</p>
            <p>You may adjust the schedule for individual quarters if needed.
    Once you are satisfied, click <strong>Proceed with Schedule</strong> to continue to the next step.</p>
            <form action="{{ route('admin.courses.saveQuarterSchedule', $course->id) }}" id="createRecord" method="POST"
              enctype="multipart/form-data" autocomplete="off">
              @csrf
              <input type="hidden" name="simulation_id" value="{{ $course->id }}">
              <input type="hidden" name="updation_type" value="bulk">
              <button type="submit">Proceed with Schedule</button>
            </form>
          </div>
          {{-- simulationQuarter --}}
          <div class="simulationQuarter">
            {{-- QUARTERAUTOSETUP --}}
            <div class="quarterAutosetup">
              @foreach ($quarters as $quarter)
                @if ($loop->iteration <= 3)
                  <div class="item">
                    <label for="quarterTitle">Quarter {{ $quarter->quarter_name }}</label>
                    <small>Start Date: {{ $quarter->start_date }} | End Date: {{ $quarter->end_date }}</small>
                  </div>
                @endif
              @endforeach
            </div>
            {{-- QUARTERSCHEDULE --}}
            <div class="quarterSchedule">
              @foreach ($quarters as $quarter)
                @if ($loop->iteration > 3)
                  <div class="quarterdateTiming">
                    {{-- QUARTER NAME --}}
                    <label for="title">Quarter {{ $quarter->quarter_name }}</label>
                    {{-- OPTION TO REMOVE IF QUARTER ARE > 4 --}}
                    <div class="removeQuarter">
                      @if ($loop->iteration > 4)
                        <a
                          href="{{ route('admin.courses.removeQuarter', ['courses_id' => $quarter->simulation_id, 'quarter_id' => $quarter->id]) }}"><i
                            class="fa fa-trash-o" aria-hidden="true"></i></a>
                      @else
                        <span><i class="fa fa-ban" aria-hidden="true"></i></span>
                      @endif
                    </div>
                    {{-- DATE & TIMING --}}
                    <div class="timing">
                      @if ($quarter->is_schedule == 'No' && $quarter->quarter_name > 3)
                        <form class="scheduleForm"
                          action="{{ route('admin.courses.saveQuarterSchedule', $quarter->simulation_id) }}"
                          id="createRecord" method="POST" enctype="multipart/form-data" autocomplete="off">
                          @csrf
                          <input type="hidden" name="start_time" value="{{ old('start_date', $quarter->start_time) }}">
                          <input type="hidden" name="end_time" value="{{ old('start_date', $quarter->end_time) }}">
                          <input type="hidden" name="simulation_id" value="{{ $quarter->simulation_id }}">
                          <input type="hidden" name="quarter_id" value="{{ $quarter->id }}">
                          <input type="hidden" name="is_schedule" value="Yes">
                          <input type="hidden" name="updation_type" value="single">
                          <div class="inputField">
                            <label for="title"><i class="fa fa-calendar-check-o" aria-hidden="true"></i> Start Date
                              *</label>
                            <input readonly type="text" name="start_date" maxlength="70"
                              placeholder="Ex: 2025-01-15 (YYYY-MM-DD)"
                              value="{{ old('start_date', $quarter->start_date) }}">
                          </div>
                          <div class="inputField">
                            <i class="fa fa-pencil" aria-hidden="true"></i>
                            <label for="title"><i class="fa fa-calendar-times-o" aria-hidden="true"></i> End Date
                              *</label>
                            <input class="datepicker" type="text" name="end_date" maxlength="70"
                              placeholder="Ex: 2025-01-15 (YYYY-MM-DD)"
                              value="{{ old('start_date', $quarter->end_date) }}">
                          </div>
                          <div class="actionButton">
                            <button type="submit">Save Schedule</button>
                          </div>
                        </form>
                      @else
                        <h4>Quarter Schedule</h4>
                        Start Date & Time: {{ $quarter->start_date }} {{ $quarter->start_time }} <br>
                        End Date & Time: {{ $quarter->end_date }} {{ $quarter->end_time }}
                      @endif
                    </div>
                  </div>
                @endif
              @endforeach
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
