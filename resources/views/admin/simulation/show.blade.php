@extends('admin.layout.simulation')
@section('title', 'Simulation: ' . $simulation->title)
@section('simulationDetail')
  <div class="wrapSection">
    <div class="sectionContainer">
      @if ($simulation->is_published == 'No')
        <div class="headingContainer">
          <h3>Simulation Deployment</h3>
          <p>Assign simulations to faculty members, set per-student fees, and define the maximum student capacity. Manage
            simulation timelines, update schedules, and ensure proper licensing limits for each course.</p>
        </div>
        <div class="modificationContainer">
          <div class="templateCrud">
            <form action="{{ route('admin.courses.deploySimulation', $simulation->id) }}" method="POST" autocomplete="off"
              enctype="multipart/form-data">
              @csrf
              <div class="dataForm">
                <div class="dataRow" style="padding: 0 1rem">
                  <div class="inputField">
                    <label for="">Fee per Student *</label>
                    <input type="text" readonly name="fee" maxlength="3" placeholder="Ex: 100"
                      value="{{ old('fee') ?? '100' }}" onkeypress="return event.charCode >= 48 && event.charCode <= 57"
                      value="100">
                    <small class="help_text">Enter the total course fee in numbers only. Maximum 3 digits.</small>
                    @error('fee')
                      <small class="error_text">{{ $message }}</small>
                    @enderror
                  </div>
                  <div class="inputField">
                    <label for="">Maximum Students Allowed *</label>
                    <input type="text" name="licence_count" maxlength="3" placeholder="Ex: 10"
                      value="{{ old('licence_count') ?? $simulation->teams }}"
                      onkeypress="return event.charCode >= 48 && event.charCode <= 57" value="{{ $simulation->teams }}">
                    <small class="help_text">Enter the maximum number of licences allowed for this course. Numbers only,
                      up to 3
                      digits.</small>
                    @error('licence_count')
                      <small class="error_text">{{ $message }}</small>
                    @enderror
                  </div>
                </div>
              </div>
              <div class="deployButton">
                <input type="hidden" name="simulation_id" value="{{ $simulation->id }}">
                <button><img src="{{ URL::asset('images/deploy-button-faculty.svg') }}" alt=""></button>
              </div>
            </form>
          </div>

          <div class="widgetsContainer">
            <div class="widget">
              <div class="widgetheading">
                <h5>Basic Information</h5>
                <small>View or update course name, dates, and structure</small>
              </div>
              <div class="widgetInfo">
                <form action="#">
                  @csrf
                  <div class="inputContainer">
                    <label for="simulation_title">Simulation Title *</label>
                    <input type="text" name="simulation_title"
                      value="{{ old('simulation_title', $simulation->title) }}">
                    @error('simulation_title')
                      <small class="error_text">{{ $message }}</small>
                    @enderror
                  </div>
                  <div class="inputContainer">
                    <label for="simulation_title">Organization / Institution *</label>
                    <input type="text" name="organization"
                      value="{{ old('organization', $simulation->organization) }}">
                    @error('organization')
                      <small class="error_text">{{ $message }}</small>
                    @enderror
                  </div>
                  <button type="submit">Update</button>
                </form>
              </div>
            </div>
            <div class="widget">
              <div class="widgetheading">
                <h5>Faculty Assignment</h5>
                <small>Assign or manage faculty members for this course</small>
              </div>
              <div class="widgetInfo nopadding">
                <div class="widgetInfo nopadding">
                  @foreach ($simulation->simulationFaculties as $item)
                    <div class="faculty">
                      <label for="faculty"></label>
                      {{ $item->faculty->first_name . ' ' . $item->faculty->last_name }}
                      <small>{{ $item->faculty->email }}</small>
                    </div>
                  @endforeach
                </div>
              </div>
            </div>
            <div class="widget">
              <div class="widgetheading">
                <h5>Delete Course</h5>
                <small>Permanently remove this course and all associated data.</small>
              </div>
              <div class="widgetInfo">
                <p>To confirm deletion, type <strong>{{ $simulation->simulation_code }}</strong> in the text box below
                  and
                  click the <strong>Delete</strong> button.</p>

                <form action="{{ route('admin.simulation.destroy', $simulation->id) }}" id="createRecord" method="POST"
                  enctype="multipart/form-data" autocomplete="off">
                  @csrf
                  @method('DELETE')
                  <div class="inputContainer">
                    <label for="confirmation_code">Enter Confirmation Code *</label>
                    <input type="text" name="confirmation_code" value="{{ old('confirmation_code') }}"
                      onkeypress="return event.charCode >= 48 && event.charCode <= 57">
                    @error('confirmation_code')
                      <small class="error_text">{{ $message }}</small>
                    @enderror
                  </div>

                  <input type="hidden" name="validateToken" value="{{ $simulation->simulation_code }}">
                  <button type="submit">Delete Course</button>
                </form>
              </div>
            </div>
          </div>

          <div class="manageQuarter">
            <div class="widgetheading">
              <h5>Quarter Setup</h5>
              <small>Manage simulation quarters and schedule timelines</small>
            </div>
            <div class="quarterSchedule">
              @foreach ($simulation->simulationQuarters as $quarter)
                @if ($loop->iteration > 3)
                  <div class="quarterdateTiming">
                    <label for="title">Quarter {{ $quarter->quarter_name }}</label>
                    <div class="removeQuarter">
                      @if ($loop->iteration > 4)
                        <a
                          href="{{ route('admin.simulation.removeQuarter', ['simulation_id' => $quarter->simulation_id, 'quarter_id' => $quarter->id]) }}">
                          <i class="fa fa-trash-o" aria-hidden="true"></i></a>
                      @else
                        <span><i class="fa fa-ban" aria-hidden="true"></i></span>
                      @endif
                    </div>
                    <div class="timing">
                      <form class="scheduleForm" action="#" id="createRecord" method="POST"
                        enctype="multipart/form-data" autocomplete="off">
                        @csrf
                        <input type="hidden" name="start_time" value="{{ old('start_date', $quarter->start_time) }}">
                        <input type="hidden" name="end_time" value="{{ old('start_date', $quarter->end_time) }}">
                        <input type="hidden" name="simulation_id" value="{{ $quarter->simulation_id }}">
                        <input type="hidden" name="quarter_id" value="{{ $quarter->id }}">
                        <input type="hidden" name="is_schedule" value="Yes">
                        <input type="hidden" name="updation_type" value="single">
                        <div class="inputField">
                          <label for="title"><i class="fa fa-calendar-check-o" aria-hidden="true"></i> Start Date
                          </label>
                          <input disabled type="text" name="start_date" maxlength="70"
                            placeholder="Ex: 2025-01-15 (YYYY-MM-DD)"
                            value="{{ old('start_date', $quarter->start_date) }}">
                        </div>
                        <div class="inputField">
                          <label for="title"><i class="fa fa-calendar-times-o" aria-hidden="true"></i> End Date
                          </label>
                          <input disabled type="text" name="end_date" maxlength="70"
                            placeholder="Ex: 2025-01-15 (YYYY-MM-DD)"
                            value="{{ old('start_date', $quarter->end_date) }}">
                        </div>

                      </form>
                    </div>
                  </div>
                @endif
              @endforeach
            </div>

          </div>
        </div>
      @else
        <div class="headingContainer">
          <h3>Simulation Overview</h3>
          <p>This simulation is currently active and assigned to your faculty account. You can monitor student
            participation, manage capacity, and review deployment progress.</p>
        </div>
        <div class="paymentCollection">
          <h5>Invoice: {{ config('currency.symbol') . number_format($simulation->licence_count * $simulation->fee, 2) }}
          </h5>
        </div>
        <div class=" deploysuccess">
          <img src="{{ URL::asset('images/deploy-button-success.svg') }}" alt="">
        </div>

        <div class="quickStatices">
          <div class="item">
            <label for="title">Total Licenses</label>
            <p>Total number of student seats available for this simulation.</p>
            <strong>{{ str_pad($simulation->licence_count, 2, '0', STR_PAD_LEFT) }}</strong>
          </div>
          <div class="item">
            <label for="title">Students Enrolled</label>
            <p>Total students who have registered for this simulation course.</p>
            <strong>{{ str_pad($totalStudentEnroll, 2, '0', STR_PAD_LEFT) }}</strong>
          </div>
          <div class="item">
            <label for="title">Students Assigned to Groups</label>
            <p>Students currently placed into simulation teams or groups.</p>
            <strong>{{ str_pad($totalStudentAssigned, 2, '0', STR_PAD_LEFT) }}</strong>
          </div>
          <div class="item">
            <label for="title">Paid Students</label>
            <p>Students who have successfully completed the simulation payment.</p>
            <strong>{{ str_pad($totalPaidStudents, 2, '0', STR_PAD_LEFT) }}</strong>
          </div>
          <div class="item">
            <label for="title">Unpaid Students</label>
            <p>Students who still need to complete the simulation payment.</p>
            <strong>{{ str_pad($totalUnpaidStudents, 2, '0', STR_PAD_LEFT) }}</strong>
          </div>
        </div>


        <div class="simulationControlled">
          <div class="increaseLicence">
            <h6>Increase Student Capacity <small>Current student licences available:
                {{ $simulation->licence_count }}</small></h6>
            <div class="templateCrud">
              <div class="dataForm">
                <label for="licence_count">Additional Licences to Add *</label>
                <p>Add more licences to allow additional students to participate in this simulation. </p>
                <span>Enter a whole number, up to 3 digits.</span>
                <form action="{{ route('admin.simulation.increaseLicence', $simulation->id) }}" id="createRecord"
                  method="POST" enctype="multipart/form-data" autocomplete="off">
                  @csrf
                  <input type="hidden" name="simulation_id" value="{{ $simulation->id }}">
                  <div class="dataRow">
                    <div class="inputField">
                      <input type="text" readonly value="Current: {{ $simulation->licence_count }}">
                    </div>
                    <div class="inputField">
                      <input type="text" name="licence_count" value="{{ old('licence_count') }}" maxlength="3"
                        placeholder="Enter number of licences to add"
                        onkeypress="return event.charCode >= 48 && event.charCode <= 57">
                      @error('licence_count')
                        <small class="error_text">{{ $message }}</small>
                      @enderror
                    </div>
                    <div class="inputField">
                      <button type="submit">Add Licences</button>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <div class="deleteCourse">
            <h6>Delete Course
              <small>Permanently remove this course and all associated data. This action cannot be undone.</small>
            </h6>
            <div class="templateCrud">
              <div class="dataForm">
                <label for="licence_count">Enter Confirmation Code *</label>
                <p>To confirm deletion, type <strong>{{ $simulation->simulation_code }}</strong> in the text box below.
                </p>
                <span>Type the exact confirmation code shown above to proceed.</span>
                <form action="{{ route('admin.simulation.destroy', $simulation->id) }}" id="createRecord"
                  method="POST" enctype="multipart/form-data" autocomplete="off">
                  @csrf
                  @method('DELETE')
                  <input type="hidden" name="validateToken" value="{{ $simulation->simulation_code }}">
                  <div class="inputContainer">
                    <div class="dataRow">
                      <div class="inputField">
                        <input type="text" readonly value="Code: {{ $simulation->simulation_code }}">
                      </div>
                      <div class="inputField">
                        <input type="text" name="confirmation_code" value="{{ old('confirmation_code') }}"
                          onkeypress="return event.charCode >= 48 && event.charCode <= 57"
                          placeholder="Enter Confirmation Code">
                        @error('confirmation_code')
                          <small class="error_text">{{ $message }}</small>
                        @enderror
                      </div>
                      <div class="inputField">
                        <button type="submit">Delete Course</button>
                      </div>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>

        </div>
        <div class="aiAnalysisControl">
          <div class="templateCrud">
            <div class="dataForm">
              <form action="{{ route('admin.simulation.aiAnalysis', $simulation->id) }}" method="POST"
                enctype="multipart/form-data" autocomplete="off">
                @csrf
                <input type="hidden" name="simulation_id" value="{{ $simulation->id }}">
                <div class="dataRow">
                  <div class="inputField">
                    <h6>AI Analysis Report <small>Enable or disable AI-generated quarterly analysis reports.</small></h6>
                    @if ($simulation->is_analysis_enabled)
                      <p>AI analysis is currently enabled. Turn OFF to skip AI analysis generation during initialization
                        and quarter close.</p>
                    @else
                      <p>AI analysis is currently disabled. Turn ON to enable AI analysis generation during initialization
                        and quarter close.</p>
                    @endif
                  </div>
                  <div class="inputField">
                    <button type="submit">
                      @if ($simulation->is_analysis_enabled)
                        <i class="fa fa-toggle-on"></i> Disable
                      @else
                        <i class="fa fa-toggle-off"></i> Enable
                      @endif
                    </button>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div class="coFacultyControl">
          <div class="templateCrud">
            <div class="item">
              <h6> Co-Faculty Members <small>Assign additional faculty members to assist with managing this
                  simulation.</small></h6>
              <p> Co-faculty members can help manage students, monitor simulation progress, and assist the primary faculty
                in overseeing simulation activities.</p>
            </div>
            <div class="item actionButton">
              <a href="{{ route('admin.simulation.selectCoFaculty', $simulation->id) }}"> Add Co-Faculty </a>
            </div>
          </div>
        </div>

        <div class="facultyContainer">
          <div class="headingContainer">
            <h6>Faculty Simulation Status</h6>
            <p>Track student invitations, group formation, and simulation deployment progress.</p>
          </div>

          @foreach ($simulation->simulationFaculties as $item)
            <div class="faculty">
              <div class="profile">
                <label for="faculty">{{ $item->faculty->first_name . ' ' . $item->faculty->last_name }}
                  ({{ $item->role == 'faculty' ? 'Faculty' : 'Co-Faculty' }})
                  <small>{{ $item->faculty->email }}</small></label>
                  @if ($item->role === 'faculty')
                    @if ($item->is_published === 'No')
                      <p>Pending: Student invitation, group allocation, and simulation deployment.</p>
                    @else
                      <p>Group assignments are complete and the simulation has been successfully deployed.</p>
                      <small>Total Licences in Use: {{ $simulation->licence_count }}</small>
                    @endif
                @endif
              </div>
            </div>
          @endforeach
           
           
            
            {{-- LIST OF STUDENTS --}}
            @if (count($students) > 0)
              <div class="studentsWrap">
                <h6>Assigned Student Groups <small>Below is the list of students assigned to groups in this
                    simulation.</small></h6>
                <div class="studentList">
                  @foreach ($students as $student)
                    <div class="studentRecord">
                      <div class="item">
                        <label
                          for="">{{ $student->student->first_name . ' ' . $student->student->last_name }}</label>
                        <small>Student Unique Code: {{ $student->student->student_code }} |
                          Email: {{ $student->student->email }}</small>
                      </div>
                    </div>
                  @endforeach
                </div>
              </div>
            @endif
           
        </div>
        <div class="modificationContainer">
          <div class="manageQuarter">
            <div class="widgetheading">
              <h5>Simulation Quarters</h5>
              <small>Configure simulation rounds and scheduling timelines</small>
            </div>
            <div class="quarterSchedule">
              @foreach ($simulation->simulationQuarters as $quarter)
                @if ($loop->iteration > 3)
                  <div class="quarterdateTiming">
                    <label for="title">Quarter {{ $quarter->quarter_name }}</label>
                    <div class="removeQuarter">
                      <span><i class="fa fa-calendar" aria-hidden="true"></i></span>
                    </div>
                    <div class="timing">
                      <div class="scheduleForm">
                        <div class="inputField">
                          <label for="title"><i class="fa fa-calendar-check-o" aria-hidden="true"></i> Start Date
                          </label>
                          <input disabled type="text" name="start_date" maxlength="70"
                            placeholder="Ex: 2025-01-15 (YYYY-MM-DD)"
                            value="{{ old('start_date', $quarter->start_date) }}">
                        </div>
                        <div class="inputField">
                          <label for="title"><i class="fa fa-calendar-times-o" aria-hidden="true"></i> End Date
                          </label>
                          <input disabled type="text" name="end_date" maxlength="70"
                            placeholder="Ex: 2025-01-15 (YYYY-MM-DD)"
                            value="{{ old('start_date', $quarter->end_date) }}">
                        </div>
                      </div>
                    </div>
                  </div>
                @endif
              @endforeach
            </div>
          </div>
        </div>
      @endif
    </div>
  </div>
@endsection
