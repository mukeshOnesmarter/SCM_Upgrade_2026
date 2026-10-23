@extends('faculty.simulation.layout')
@section('title', 'Courses')
@section('inheritTemplate')
    @if ($simulation->is_deploying)
        <div class="simulationSync">
            <div class="message">
                <div class="loader"></div>
                <h3>Simulation Deployment in Progress</h3>
                <p>The simulation has been deployed successfully.</p>
                <p>The system is now initializing the simulation, setting up teams, and preparing data for the upcoming
                    quarter.
                </p>
            </div>
        </div>
    @endif

    @if ($simulation->is_syncing)
        <div class="simulationSync">
            <div class="message">
                <div class="loader"></div>
                <h3>Quarter Closing in Progress</h3>
                <p>Your quarter closing request has been submitted successfully.</p>
                <p>The simulation is currently synchronizing results, generating reports, and updating scorecards.</p>
            </div>
        </div>
    @endif

    @if ($simulation->is_published_by_current_faculty != 'No')
        <div class="courseDetail">
            <div class="pageTitle">
                <h4>Simulation Group Overview <small>The simulation has been deployed</small></h4>
                <p>You can now monitor group performance, review participation, and manage ongoing simulation tasks. Student
                    assignment changes are disabled during the simulation period.</p>
            </div>
            @include('faculty.simulation.partials.inviteStudent')
            @include('faculty.simulation.partials.statices')
            @include('faculty.simulation.partials.aiAnalysisControl')

            <div class="studentInviteGroups">
                @include('faculty.simulation.partials.bulkInvite')
                @include('faculty.simulation.partials.shareUrl')
            </div>

            @include('faculty.simulation.partials.performanceOverview')
            @include('faculty.simulation.partials.paymentMode')
            @include('faculty.simulation.partials.widgets')
            @include('faculty.simulation.partials.assignGroupPending')
            @include('faculty.simulation.partials.quarters')
            <p>&nbsp;</p>
        </div>
    @else
        <div class="courseDetail">
            <div class="pageTitle">
                <h4>Group Assignment Dashboard</h4>
                <p>Assign students to simulation groups and prepare for deployment. You can invite new students, assign
                    enrolled
                    students to groups, and start the simulation once all groups are ready.</p>
            </div>
            @include('faculty.simulation.partials.deploySimulation')

            <div class="firmManagement">
                <div class="sectionTitle">
                    <h5>Simulation Firms</h5>
                    <p>
                        Add or remove firms before deploying the simulation.
                        Firm changes are disabled after deployment.
                    </p>
                </div>

                <div class="firmManagementActions">

                    {{-- Remove Firm --}}
                    <form method="POST" action="{{ route('faculty.courses.removeFirm', $simulation->id) }}"
                        onsubmit="return confirm('Are you sure you want to remove the last firm?');">
                        @csrf

                        <button type="submit" id="removeFirm" class="btn btn-danger"
                            {{ $simulation->is_published_by_current_faculty != 'No' || $simulation->simulationTeams->count() <= 2 ? 'disabled' : '' }}>
                            <i class="fa fa-minus"></i>
                            Remove Firm
                        </button>
                    </form>

                    {{-- Firm Count --}}
                    <span class="firmCount">
                        {{ $simulation->simulationTeams->count() }} Firms
                    </span>

                    {{-- Add Firm --}}
                    <form method="POST" action="{{ route('faculty.courses.addFirm', $simulation->id) }}">
                        @csrf

                        <button type="submit" id="addFirm" class="btn btn-success"
                            {{ $simulation->is_published_by_current_faculty != 'No' ? 'disabled' : '' }}>
                            <i class="fa fa-plus"></i>
                            Add Firm
                        </button>
                    </form>

                </div>
            </div>

            {{-- FIRM / GROUP MANAGEMENT --}}
            <div class="simulationTeamGroup">
                {{-- LIST ALL TEAMS / GROUPS --}}
                @if ($simulation->simulationTeams)
                    <div class="wrapGroups" id="groupList">
                        @foreach ($simulation->simulationTeams as $group)
                            <div class="group {{ $loop->first ? 'active' : '' }}"
                                data-id="targetGroup{{ str_pad($group->group_number, 2, '0', STR_PAD_LEFT) }}">
                                <label for="title"> GROUP
                                    {{ str_pad($group->group_number, 2, '0', STR_PAD_LEFT) }}</label>
                                @if (count($group->teamMembers) > 0)
                                    <small> {{ count($group->teamMembers) }}-Student Team</small>
                                @else
                                    <small class="unassigned">Unassigned Currently</small>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
                {{-- STUDENTS --}}
                <div class="studentWrap" id="studentsGroup">
                    @foreach ($simulation->simulationTeams as $group)
                        <div class="studentGroup {{ $loop->first ? 'active' : '' }}"
                            id="targetGroup{{ str_pad($group->group_number, 2, '0', STR_PAD_LEFT) }}">
                            <div class="teamMembers">
                                @if (count($group->teamMembers) > 0)
                                    <div class="widgetTitle">
                                        @foreach ($group->teamMembers->take(1) as $member)
                                            {{ $member->student->first_name . ' ' . $member->student->last_name }} & Others
                                        @endforeach
                                        @if (count($group->teamMembers) > 1)
                                            <a
                                                href="{{ route('faculty.courses.studentsGroup', [$simulation->id, $group->group_number]) }}">
                                                <img src="{{ URL::asset('images/icon/external_icon.svg') }}"
                                                    alt=""> </a>
                                        @endif
                                    </div>
                                    <div class="studentsList" style="height: 624px;">
                                        @foreach ($group->teamMembers as $member)
                                            <div class="student">
                                                <div class="item profilePicture">
                                                    @if (is_null($member->student->profile?->profile_picture))
                                                        <img src="{{ URL::asset('images/avatar-user.svg') }}"
                                                            alt="">
                                                    @else
                                                        <img src="{{ URL::asset('storage/uploads/student/' . $member->student->profile?->profile_picture) }}"
                                                            alt="">
                                                    @endif
                                                </div>
                                                <div class="item studentInfo">
                                                    <label
                                                        for="heading">{{ $member->student->first_name . ' ' . $member->student->last_name }}</label>
                                                    <small> <i class="fa fa-envelope-o" aria-hidden="true"></i>
                                                        {{ strtolower($member->student->email) }} </small>
                                                </div>
                                                <div class="item action">
                                                    <form
                                                        action="{{ route('faculty.courses.removeStudentFromGroup', ['course_id' => $simulation->id]) }}"
                                                        method="POST">
                                                        @csrf
                                                        <input type="hidden" name="course_id"
                                                            value="{{ $simulation->id }}">
                                                        <input type="hidden" name="faculty_id"
                                                            value="{{ $sharedActiveUserData->id }}">
                                                        <input type="hidden" name="group_number"
                                                            value="{{ $group->group_number }}">
                                                        <input type="hidden" name="student_id"
                                                            value="{{ $member->student->id }}">
                                                        <button class="trash"><i class="fa fa-trash-o"
                                                                aria-hidden="true"></i></button>
                                                    </form>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="noRecords">
                                        <h5>Group {{ str_pad($group->group_number, 2, '0', STR_PAD_LEFT) }} : No Member /
                                            Student Found</h5>
                                        <p>No members or students found in the selected group. Please invite students and
                                            add them to the
                                            group to get started.</p>
                                        <small>Note: At least two or more students must be added to each group.</small>
                                    </div>
                                @endif
                            </div>
                            {{-- Students Enrolled --}}
                            @if (count($students) > 0)
                                <div class="assignStudentToGroup">
                                    <div class="widgetTitle"> Students Awaiting Group Assignment </div>
                                    <p class="note">Note: You can add up to {{ $simulation->licence_count }} students as
                                        per licence
                                        purchased <br> Enrolled {{ count($students) }} Students</p>
                                    <div class="studentsList" style="height: 567px;">
                                        @foreach ($students as $item)
                                            <div class="student">
                                                <div class="item profilePicture">
                                                    @if (is_null($item->student->profile?->profile_picture))
                                                        <img src="{{ URL::asset('images/avatar-user.svg') }}"
                                                            alt="">
                                                    @else
                                                        <img src="{{ URL::asset('storage/uploads/student/' . $item->student->profile?->profile_picture) }}"
                                                            alt="">
                                                    @endif
                                                </div>
                                                <div class="item studentInfo">
                                                    <label
                                                        for="heading">{{ $item->student->first_name . ' ' . $item->student->last_name }}</label>
                                                    <small> <i class="fa fa-envelope-o" aria-hidden="true"></i>
                                                        {{ strtolower($item->student->email) }} </small>
                                                </div>
                                                <div class="item action">
                                                    <form
                                                        action="{{ route('faculty.courses.assignStudentToGroup', ['course_id' => $simulation->id]) }}"
                                                        method="POST">
                                                        @csrf
                                                        <input type="hidden" name="course_id"
                                                            value="{{ $simulation->id }}">
                                                        <input type="hidden" name="faculty_id"
                                                            value="{{ $sharedActiveUserData->id }}">
                                                        <input type="hidden" name="group_number"
                                                            value="{{ $group->group_number }}">
                                                        <input type="hidden" name="student_id"
                                                            value="{{ $item->student->id }}">
                                                        <button class="assignGroup">Assign to Group</button>
                                                    </form>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            {{-- assignGroup --}}
            <div class="assignGroup">
                @if (count($students) > 0)
                    <div class="assignGroupContainer">
                        <div class="sectionTitle">
                            <img src="{{ URL::asset('images/icon/arrow-up.svg') }}" alt="">
                            <label for="heading">Add the participants to the group from the list</label>
                            <p>Fair & Random Student Distribution – Balanced Groups Every Time! <br>
                                You can assign up to {{ $simulation->licence_count }} students to groups based on your
                                licence.</p>
                            <span> <a href="{{ route('faculty.courses.studentsDistribute', $simulation->id) }}">Random
                                    Group
                                    Allocation</a></span>
                        </div>
                    </div>
                @endif
            </div>

            @if ($simulation->is_published_by_current_faculty == 'No' && count($students) < 1)
                <p>&nbsp;</p>
                <a href="{{ route('faculty.importStudent', $simulation->id) }}" target="_blank">Setup Test Team</a>
                <p>&nbsp;</p>
            @endif
        </div>
    @endif
@endsection
@section('templateScript')
    <script type="text/javascript">
        $(document).ready(function() {
            $("#groupList").on("click", "div.group", function(e) {
                e.preventDefault();
                // GET THE TARGET CONTAINER ID
                var targetContainer = $(this).data("id");
                // REMOVE ACTIVE CLASS FROM ALL GROUPS AND SET ACTIVE ON THE CLICKED ONE
                $("#groupList").find("div.group").removeClass("active");
                $(this).addClass("active");
                // UPDATE STUDENTSGROUP VISIBILITY
                $("#studentsGroup").find("div.studentGroup").removeClass("active");
                $("#studentsGroup").find("#" + targetContainer).addClass("active");
            });

            // DEPLOY-SIMULATION.
            $("#deploySimulation").click(function(e) {
                e.preventDefault();

                let url = $(this).attr('href'); // GET REQUEST URL.
                let button = $(this); // STORE REFERENCE TO THE CLICKED BUTTON. 

                // DISABLE BUTTON TO PREVENT MULTIPLE CLICKS
                button.prop('disabled', true).text('Processing...');

                $.get(url)
                    .done(function(response) {
                        // alert('Simulation published successfully.'); // Show success message
                        location.reload(); // Reload the current page to reflect changes
                    })
                    .fail(function(xhr) {
                        alert('Something went wrong. ' + (xhr.responseJSON?.message ||
                            'Please try again.'));
                    })
                    .always(function() {
                        button.prop('disabled', false).text('Deploy Simulation'); // Re-enable button
                    });
            });

        });

        // COPY REGISTRATION LINK.
        function copyRegistrationLink() {
            let copyText = document.getElementById("registrationLink");

            navigator.clipboard.writeText(copyText.value)
                .then(function() {
                    alert("Registration link copied successfully!");
                })
                .catch(function() {
                    alert("Failed to copy link.");
                });
        }
    </script>
@endsection
