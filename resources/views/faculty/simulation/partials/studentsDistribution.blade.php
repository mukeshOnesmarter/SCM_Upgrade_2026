{{-- STUDENTSDISTRIBUTION --}}
<div class="studentsDistribution">
  {{-- MOVEINSTRUCTION --}}
  <div class="moveInstruction">
    <i class="fa fa-angle-double-left" aria-hidden="true"></i>
    <span>Shuffle the participants between groups</span>
    <i class="fa fa-angle-double-right" aria-hidden="true"></i>
  </div>
  {{-- GROUPSWRAP --}}
  <div class="groupsWrap">
    @if ($simulation->simulationTeams)
      {{-- GROUPHEADER --}}
      <div class="groupHeader">
        @foreach ($simulation->simulationTeams as $group)
          <div class="item">
            <img src="{{ URL::asset('images/icon/group-icon.svg') }}" alt="">
            <label for="heading"> GROUP 0{{ $group->team_name }}
              <small> {{ count($group->teamMembers) }}-Student Team</small>
            </label>
          </div>
        @endforeach
      </div>
      {{-- MEMBERS --}}
      <div class="studentsWrap">
        @foreach ($simulation->simulationTeams as $group)
          <div class="item">
            <label for="heading">Move Students Between Groups</label>
            @if (count($group->teamMembers) > 0)
              <div class="studentList">
                @foreach ($group->teamMembers as $item)
                  @if ($loop->parent->first)
                    {{-- MOVE RIGHT ONLY --}}
                    <div class="student">
                      <div class="stditem fixedLeft"></div>
                      <div class="stditem">{{ $item->student->first_name . ' ' . $item->student->last_name }}
                      </div>
                      <div class="stditem fixedRight">
                        <a href="#" class="trash"><i class="fa fa-trash-o" aria-hidden="true"></i></a>
                        <a href="#" class="moveArrow"><i class="fa fa-angle-right" aria-hidden="true"></i></a>
                      </div>
                    </div>
                  @elseif($loop->parent->last)
                    {{-- MOVE LEFT ONLY --}}
                    <div class="student">
                      <div class="stditem fixedLeft">
                        <a href="#" class="moveArrow"><i class="fa fa-angle-left" aria-hidden="true"></i></a>
                      </div>
                      <div class="stditem">{{ $item->student->first_name . ' ' . $item->student->last_name }}
                      </div>
                      <div class="stditem fixedRight">
                        <a href="#" class="trash"><i class="fa fa-trash-o" aria-hidden="true"></i></a>
                      </div>
                    </div>
                  @else
                    {{-- MOVE MEMBER LEFT & RIGHT --}}
                    <div class="student">
                      <div class="stditem fixedLeft">
                        <a href="#" class="moveArrow"><i class="fa fa-angle-left" aria-hidden="true"></i></a>
                      </div>
                      <div class="stditem">{{ $item->student->first_name . ' ' . $item->student->last_name }}
                      </div>
                      <div class="stditem fixedRight">
                        <a href="#" class="trash"><i class="fa fa-trash-o" aria-hidden="true"></i></a>
                        <a href="#" class="moveArrow"><i class="fa fa-angle-right" aria-hidden="true"></i></a>
                      </div>
                    </div>
                  @endif
                @endforeach
              </div>
            @endif
          </div>
        @endforeach
      </div>
    @endif
  </div>
</div>

{{-- assignGroup --}}
<div class="assignGroup">
  @if (count($students) > 0)
    <div class="assignGroupContainer">
      <div class="sectionTitle">
        <img src="{{ URL::asset('images/icon/arrow-up.svg') }}" alt="">
        <label for="heading">Add the participants to the group from the list</label>
        <p>Fair & Random Student Distribution – Balanced Groups Every Time!</p>
        <span> <a href="{{ route('faculty.courses.studentsDistribute', $simulation->id) }}">Random Group
            Allocation</a></span>
      </div>
      <div class="studentWrap">
        @foreach ($students as $item)
          <div class="student">{{ $item->student->first_name . ' ' . $item->student->last_name }} </div>
        @endforeach
      </div>
    </div>
  @endif
</div>


@if ($simulation->is_published_by_current_faculty == 'No')
   <a href="{{ route('faculty.importStudent', $simulation->id) }}" target="_blank">Setup Test Team</a>
   <p>&nbsp;</p>
@endif
