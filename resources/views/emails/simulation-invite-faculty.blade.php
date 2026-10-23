@extends('emails.layout')
@section('title', 'Faculty Enrollment and Course Setup')
@section('emailBody')
  <h4>Dear {{ $faculty->first_name . ' ' . $faculty->last_name }},</h4>
  <p>You have been enrolled as a faculty member on the FLEXEE Supply Chain Management Simulation platform.</p>
  <p>If you did not request this enrollment, please ignore this email and notify us immediately at support@flexee.org.</p>
  <p>Your course and simulation information are included below. FLEXEE is a supply chain management simulation that
    gives MBA students hands-on experience managing procurement, manufacturing, distribution, forecasting, and customer service decisions
    in a dynamic global environment.</p>

  <h3>Course Details:</h3>
  <p><strong>Course Code:</strong> {{ $simulation->course_code }}<br />
    <strong>Faculty:</strong> {{ $faculty->first_name . ' ' . $faculty->last_name }}<br />
    <strong>Course Title:</strong> {{ $simulation->title }}<br />
    <strong>Course Start Date:</strong> {{ \Carbon\Carbon::parse($simulation->start_date)->format('m/d/Y') }}
  </p>

  <h3>Team & Simulation Setup</h3>
  <ol>
    <li>Students register at <a
        href="{{ config('app.student_registration_url') . "/{$faculty->faculty_code}" . "/{$simulation->share_code}" }}"
        target="_blank">{{ config('app.student_registration_url') }}</a> using the course code above.</li>
    <li>Registered students appear in your Team Management page.</li>
    <li>Once team divisions are finalized, click “Deploy Simulation.” </li>
    <li>The system will create default simulations and logins. Students will then see an “Enter Simulation” button in
      their accounts.</li>
    <li>You may redeploy if team structures change.</li>
  </ol>

  <h3>Session Schedule</h3>
  <table width="100%" border="1" cellpaddin="0">
    <tr>
      <td width="300px"><strong>Quarter</strong></td>
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
@endsection
