{{-- Pending Students Awaiting Group Assignment --}}
@if (count($students) > 0)
  <div class="assignGroupPending">
    <div class="assignStudentToGroup">
      <h5>Students Awaiting Group Assignment<small>Manage and Monitor Simulation Quarters - {{ count($students) }}
          Students Pending Assignment</small></h5>
      <div class="studentsList">
        @foreach ($students as $item)
          <div class="student">
            <div class="item studentInfo">
              <label for="heading">{{ $item->student->first_name . ' ' . $item->student->last_name }}</label>
              <small> <i class="fa fa-envelope-o" aria-hidden="true"></i> {{ strtolower($item->student->email) }}
              </small>
            </div>
            <div class="item assignGroup">
              @if ($simulation->simulationTeams)
                <form action="{{ route('faculty.courses.assignStudentToGroup', ['course_id' => $simulation->id]) }}"
                  method="POST" enctype="multipart/form-data" autocomplete="off">
                  @csrf
                  <select name="group_number" id="">
                    @foreach ($simulation->simulationTeams->slice(0, $simulation->simulationTeams->count() - 0) as $group)
                      <option value="{{ $group->group_number }}">Group {{ $group->group_number }}</option>
                    @endforeach
                  </select>
                  <input type="hidden" name="course_id" value="{{ $simulation->id }}">
                  <input type="hidden" name="faculty_id" value="{{ $sharedActiveUserData->id }}">
                  <input type="hidden" name="student_id" value="{{ $item->student_id }}">
                  <button>Assign to Group</button>
                </form>
              @endif
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>
@endif
