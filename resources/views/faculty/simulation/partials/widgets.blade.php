<div class="simulationWidgets">
  <div class="widget">
    {{-- LIST ALL TEAMS / GROUPS --}}
    @if ($simulation->simulationTeams)
      <div class="wrapGroups" id="groupList">
        @foreach ($simulation->simulationTeams as $group)
          <div class="group {{ $loop->first ? 'active' : '' }}"
            data-id="targetGroup{{ str_pad($group->group_number, 2, '0', STR_PAD_LEFT) }}">
            <label for="title"> GROUP 0{{ $group->team_name }}</label>
            @if (count($group->teamMembers) > 0)
              <small> {{ count($group->teamMembers) }}-Student Team</small>
            @else
              <small class="unassigned">Unassigned Currently</small>
            @endif
          </div>
        @endforeach
      </div>
    @endif
  </div>
  <div class="widget">
    {{-- LIST ALL GROUPS WITH MEMBER(S) --}}
    <div class="studentWrap" id="studentsGroup">
      @foreach ($simulation->simulationTeams as $group)
        <div class="studentGroup {{ $loop->first ? 'active' : '' }}"
          id="targetGroup{{ str_pad($group->group_number, 2, '0', STR_PAD_LEFT) }}">
          @if (count($group->teamMembers) > 0)
            <div class="widgetTitle">
              @foreach ($group->teamMembers->take(1) as $member)
                <span>{{ $member->student->first_name . ' ' . $member->student->last_name }} & Others</span>
                <a class="mimic" href="{{ route('faculty.mimic.start', ['student_id' => $member->student->id, 'simulation_id' => $simulation->id]) }}">
                  <img src="{{ asset('images/mimic.svg') }}" alt=""></a> 
              @endforeach
              @if (count($group->teamMembers) > 0)
                <a class="extendGroup" href="{{ route('faculty.courses.studentsGroup', [$simulation->id, $group->group_number]) }}"> <img
                    src="{{ URL::asset('images/icon/external_icon.svg') }}" alt=""> </a>
              @endif
            </div>
            <div class="studentsList">
              @foreach ($group->teamMembers as $member)
                <div class="student">
                  <div class="item profilePicture">
                    @if (is_null($member->student->profile?->profile_picture))
                      <img src="{{ URL::asset('images/avatar-user.svg') }}" alt="">
                    @else
                      <img
                        src="{{ URL::asset('storage/uploads/student/' . $member->student->profile?->profile_picture) }}"
                        alt="">
                    @endif
                  </div>
                  <div class="item studentInfo">
                    <label for="heading">{{ $member->student->first_name . ' ' . $member->student->last_name }}</label>
                    <small> <strong>Student ID:</strong> #{{ $member->student->student_code }} | 
                      <strong>Join Date:</strong>
                      {{ \Carbon\Carbon::parse($member->student->join_date)->format('m/d/Y') }}</small>
                    <small> <i class="fa fa-envelope-o" aria-hidden="true"></i>
                      {{ strtolower($member->student->email) }} </small>
                  </div>
                  <div class="item paymentstatus">
                    @if ($member->payment_status == 'paid') 
                      <small class="paid"> <img src="{{ asset('images/alert-icon/success.svg')}}" alt=""> Paid</small>
                    @endif 
                  </div>
                  <div class="item action">
                    <a href="{{ route('faculty.courses.studentProfile', [$simulation->id, $member->student->id]) }}">
                      <i class="fa fa-angle-right" aria-hidden="true"></i></a>
                  </div>
                </div>
              @endforeach
            </div>
          @else
            <div class="noRecords">
              <h5>Group {{ str_pad($group->team_name, 2, '0', STR_PAD_LEFT) }} : No Member / Student Found</h5>
              <p>No members or students found in the selected group. Please invite students and add them to the group to
                get started.</p>
              <small>Note: At least two or more students must be added to each group.</small>
            </div>
          @endif
        </div>
      @endforeach
    </div>
  </div>
  <div class="widget">
    {{-- FACULTY --}}
    <div class="facultyWrap">
      <div class="widgetTitle">
        <label for="heading">Faculty</label>
        @if (count($simulation->simulationFaculties) > 1)
          <a href="{{ route('faculty.courses.faculties', $simulation->id) }}"> 
            <img src="{{ URL::asset('images/icon/external_icon.svg') }}" alt=""> </a>
        @endif
      </div>
      <div class="faculties">
        @foreach ($simulation->simulationFaculties as $item)
          <div class="faculty">
            <div class="item profilePicture">
              <img src="{{ URL::asset('images/avatar-user.svg') }}" alt=""></div>
            <div class="item facultyInfo">
              <label for="heading">{{ $item->faculty->first_name . ' ' . $item->faculty->last_name }}</label>
              <small> <strong>Email: </strong> {{ $item->faculty->email }}</small>
              <small>{{ $item->role === 'faculty' ? 'Main Faculty' : 'Co-Faculty' }}</small>
            </div>
            <div class="item action">
              <a href="{{ route('faculty.courses.facultyProfile', [$simulation->id, $item->faculty->id]) }}">
                <i class="fa fa-angle-right" aria-hidden="true"></i></a>
            </div>
          </div>
        @endforeach
        <div class="enrollmentStatus">
          <ul> 
            <li><strong>Total Licenses:</strong> {{ str_pad($simulation->licence_count, 2, '0', STR_PAD_LEFT) }}</li>
            <li><strong>Students Enrolled:</strong> {{ str_pad($totalStudentEnroll, 2, '0', STR_PAD_LEFT) }}</li>
            <li><strong>Students Assigned to Groups:</strong> {{ str_pad($totalStudentAssigned, 2, '0', STR_PAD_LEFT) }}</li>
            <li><strong>Paid Students:</strong> {{ str_pad($totalPaidStudents, 2, '0', STR_PAD_LEFT) }}</li>
            <li><strong>Unpaid Students:</strong> {{ str_pad($totalUnpaidStudents, 2, '0', STR_PAD_LEFT) }}</li>
          </ul>
        </div> 
      </div> 
    </div>
  </div>
</div>
