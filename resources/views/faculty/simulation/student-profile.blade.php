@extends('faculty.simulation.layout')
@section('title', 'Student Profile : ' . ucfirst($student->first_name . ' ' . $student->last_name))
@section('sectionTitle', 'Student Profiles')
@section('sectionDescription',
  'View a detailed profile of the selected student participating in the simulation. Explore
  their group affiliation, simulation activity, and personal progress.')
@section('inheritTemplate')
  <div class="studentProfileTemplate">
    <div class="studentBio">
      <h3>{{ ucwords($student->first_name . ' ' . $student->last_name) }} <small>{{ $student->email }}</small>
      </h3>
    </div>
    <div class="demographics">
      <ul style="list-style-type: none">
        <li><strong>Current Status: </strong> {{ $student->status }}</li>
        <li><strong>Phone: </strong> {{ $student->phone }}</li>
        <li><strong>Emergency Contact: </strong> {{ $student->profile?->emergency_contact }}</li>
        <li><strong>Dob: </strong> {{ $student->dob }}</li>
        <li><strong>Gender: </strong> {{ $student->gender }}</li>
        <li><strong>Department: </strong> {{ $student->department }}</li>
      </ul>
      <div class="mimicProfile">
        @if ($student->is_course_joined)
          <a
            href="{{ route('faculty.mimic.start', ['student_id' => $student->id, 'simulation_id' => $simulationOverview->id]) }}">
            <img src="{{ asset('images/mimic.svg') }}" alt="">
            <span>Mimic Profile</span>
          </a>
        @else
          <img src="{{ asset('images/mimic-disabled.svg') }}" alt="">
        @endif
      </div>
    </div>

    <div class="actionContainer">
      @if (is_null($student->is_course_joined))
        <div class="section joiningPending">
          <h5>Student Not Joined</h5>
          <p>The student has been assigned to this simulation but has not yet logged in or started participating.</p>
        </div>
      @else
        @if ($student->payment_status === 'unpaid')
          <div class="section paymentPending">
            <h5><i class="fa fa-exclamation-circle" aria-hidden="true"></i> Payment Pending</h5>
            <p>The student has not completed the simulation payment. Due date: {{ $student->payment_due_date }}. You may
              extend the payment due date if required.
              <small>If the due date has passed, the next due date will be 3 days from today; otherwise, it will be 3 days
                after the current due date.</small>
            </p>
            <form action="{{ route('faculty.courses.extendPaymentDueDate', ['course_id' => $simulationOverview->id]) }}"
              method="POST" enctype="multipart/form-data" autocomplete="off">
              @csrf
              <input type="hidden" name="simulation_id" value="{{ $simulationOverview->id }}">
              <input type="hidden" name="student_id" value=" {{ $student->id }}">
              <button type="submit">Extend Now</button>
            </form>
          </div>
        @else
          <div class="section paymentSuccessful">
            <h5>Payment Successful</h5>
            @if ($paymentMode == 'institute')
              <p>This student's simulation fee has been paid by the institute.</p>
              <ul>
                <li><strong>- Payment Mode:</strong> Institute Sponsored</li>
                <li><strong>- Status:</strong> Paid (Covered by Institute)</li>
              </ul>
            @else
              <p>The student has successfully completed the simulation payment.</p>
              <ul>
                <li><strong>- Amount:</strong> {{ config('currency.symbol') . '1000' }}</li>
              </ul>
            @endif
          </div>
        @endif
      @endif

      <div class="section">
        <h5><i class="fa fa-money" aria-hidden="true"></i>
          {{ $student->payment_status === 'paid' ? 'Mark as Unpaid' : 'Mark as Paid' }}
        </h5>
        <p>Manually update the student’s payment status. Use this option if payment was received outside the platform or
          needs administrative correction.</p>
        <form action="{{ route('faculty.courses.togglePaymentStatus', ['course_id' => $simulationOverview->id]) }}"
          method="POST" autocomplete="off">
          @csrf
          <input type="hidden" name="simulation_id" value="{{ $simulationOverview->id }}">
          <input type="hidden" name="student_id" value="{{ $student->id }}">
          <button type="submit">
            {{ $student->payment_status === 'paid' ? 'Mark Unpaid' : 'Mark Paid' }}
          </button>
        </form>
      </div>

      <div class="section">
        <h5><i class="fa fa-unlock-alt" aria-hidden="true"></i> Reset Password</h5>
        <p>Reset the student’s account password if they are unable to log in. The new password will be set to
          <strong>{{ strtolower($student->first_name . '@' . $student->student_code) }}</strong>.
          Ask the student to change it after logging in.
        </p>
        <form action="{{ route('faculty.courses.resetPassword', ['course_id' => $simulationOverview->id]) }}"
          method="POST" enctype="multipart/form-data" autocomplete="off">
          @csrf
          <input type="hidden" name="simulation_id" value="{{ $simulationOverview->id }}">
          <input type="hidden" name="student_id" value=" {{ $student->id }}">
          <button type="submit">Reset Password</button>
        </form>
      </div>
      <div class="section">
        <h5><i class="fa fa-ban" aria-hidden="true"></i>
          {{ $student->is_blocked ? 'Unblock Student' : 'Block Student' }} </h5>
        <p>Temporarily restrict the student’s access to the simulation without removing them from the course. This can be
          used for disciplinary reasons or technical issues.
          You can restore access at any time.</p>
        <form action="{{ route('faculty.courses.toggleTemporaryBlock', ['course_id' => $simulationOverview->id]) }}"
          method="POST" enctype="multipart/form-data" autocomplete="off">
          @csrf
          <input type="hidden" name="simulation_id" value="{{ $simulationOverview->id }}">
          <input type="hidden" name="student_id" value=" {{ $student->id }}">
          <button type="submit">{{ $student->is_blocked ? 'Unblock Student' : 'Block Student' }}</button>
        </form>
      </div>
      <div class="section">
        <h5><i class="fa fa-minus-circle" aria-hidden="true"></i>
          {{ $student->is_removed ? 'Rejoin Course' : 'Remove from Course' }}</h5>
        <p>Remove the student from active participation in this simulation.
          The student will remain in the system for record purposes but will no longer
          be able to participate unless re-added.</p>
        <form action="{{ route('faculty.courses.toggleRemoveFromCourse', ['course_id' => $simulationOverview->id]) }}"
          method="POST" enctype="multipart/form-data" autocomplete="off">
          @csrf
          <input type="hidden" name="simulation_id" value="{{ $simulationOverview->id }}">
          <input type="hidden" name="student_id" value=" {{ $student->id }}">
          <button type="submit">{{ $student->is_removed ? 'Rejoin Course' : 'Remove from Course' }}</button>
        </form>
      </div>

      <div class="section">
        <h5><i class="fa fa-trash"></i> Permanently Delete Student </h5>
        <p>Permanently remove this student from the simulation and delete all associated group assignments. This action
          cannot be undone.</p>
        <form action="{{ route('faculty.courses.permanentDeleteStudent', ['course_id' => $simulationOverview->id]) }}"
          method="POST"
          onsubmit="return confirm('Are you sure you want to permanently delete this student from the simulation?')">
          @csrf
          <input type="hidden" name="simulation_id" value="{{ $simulationOverview->id }}">
          <input type="hidden" name="student_id" value="{{ $student->id }}">
          <button type="submit" style="background:#d9534f;color:#fff;"> Permanently Delete </button>
        </form>
      </div>

      <div class="section">
        <h5><i class="fa fa-random" aria-hidden="true"></i> Change Student Group</h5>
        <p>Move this student to another team during the simulation.</p>
        <form action="{{ route('faculty.courses.changeStudentGroup', ['course_id' => $simulationOverview->id]) }}"
          method="POST" autocomplete="off">
          @csrf
          <input type="hidden" name="simulation_id" value="{{ $simulationOverview->id }}">
          <input type="hidden" name="student_id" value="{{ $student->id }}">
          <select name="new_group_id" required>
            <option value="">Select New Group</option>
            @foreach ($teams as $team)
              @if ($team->group_number != $student->group_assign)
                <option value="{{ $team->group_number }}">
                  Group {{ $team->group_number }}
                </option>
              @endif
            @endforeach
          </select>
          <p><br><button type="submit">Change Group</button></p>
        </form>
      </div>
    </div>
  </div>
@endsection
