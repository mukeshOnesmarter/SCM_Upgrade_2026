@extends('emails.layout')
@section('title', 'Welcome to FLEXEE - Student Registration')
@section('emailBody')
  <?php $studentemail = Crypt::encryptString($student_email);  ?>
  <h4>Dear Student,</h4>
  <p>You are now enrolled in the FLEXEE Supply Chain Management Simulation. FLEXEE gives you the opportunity to practice
    decision-making in areas such as procurement, manufacturing, forecasting, transportation, and customer service.</p>

  <h3>Course Details:</h3>
  <p><strong>Course Code:</strong> {{ $simulation->course_code }}<br />
    <strong>Faculty:</strong> {{ $faculty->first_name . ' ' . $faculty->last_name }}<br />
    <strong>Course Title:</strong> {{ $simulation->title }}<br />
    <strong>Course Start Date:</strong> {{ \Carbon\Carbon::parse($simulation->start_date)->format('m/d/Y') }}
  </p> 

  <p><a href="{{ config('app.student_registration_url') . "/{$faculty->faculty_code}" . "/{$simulation->share_code}" . "/{$studentemail}" }}"
      target="_blank">Click Here</a> to Register and Join FLEXEE</p>

  <h3>Simulation Calendar</h3>
  <table width="100%" border="1" cellpaddin="0">
    <tr>
      <td width="300px"><strong>Round</strong></td>
      <td><strong>Start Date</strong></td>
      <td><strong>End Date</strong></td>
    </tr>
    @foreach ($simulation->simulationQuarters as $quarter)
      @if ($quarter->quarter_name > 3)
        <tr>
          <td><strong>Quarter {{ $quarter->quarter_name }}</strong></td>
          <td>{{ \Carbon\Carbon::parse($quarter->start_date)->format('m/d/Y') }}</td>
          <td>{{ \Carbon\Carbon::parse($quarter->end_date)->format('m/d/Y') }}</td>
        </tr>
      @endif 
    @endforeach
  </table>

  <p>Once registered, you will be assigned to a team and gain access to the simulation portal where you will make
    decisions, analyze results, and track performance through the FLEXEE Scorecard.</p>
@endsection