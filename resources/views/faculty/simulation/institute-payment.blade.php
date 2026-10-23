@extends('faculty.simulation.layout')
@section('title', 'Faculties')
@section('sectionTitle', 'Payment Details for Institute Submission')
@section('sectionDescription',
  'You are viewing the payment summary for students enrolled in the simulation from your
  institute.')
@section('inheritTemplate')
  <div class="institutePayment">
    <p>Please review the fee details below. Once verified, you may forward this to your institute for payment
      processing.</p>

    <div class="summaryContainer">
      <h5>Student Enrollment Summary</h5>
      <div class="infoContainer resetPadding" style=" overflow-y: scroll;
        height: 333px;">
        @if (count($students) > 0)
          @foreach ($students as $student)
            <div class="student">
              <div class="item">
                <label for=""> {{ $student->student->first_name . ' ' . $student->student->last_name }}</label>
                <small>{{ $student->student->email }} </small>
              </div>
            </div>
          @endforeach
        @else
          <p>No Students are enrolled yet.</p>
        @endif
      </div>
    </div>

    <div class="summaryContainer">
      <h5>Total Charges</h5>
      <div class="infoContainer">
        <ul>
          <li>Course Fee per Student: {{ config('currency.symbol') . number_format($course_fee) }} </li>
          <li>Total Students Enrolled: {{ str_pad(count($students), 2, '0', STR_PAD_LEFT) }}</li>
          <li>Total Payable Amount: {{ config('currency.symbol') . number_format(count($students) * $course_fee) }} </li>
        </ul>
      </div>
    </div>

    <div class="summaryContainer">
      <h5>Bank Details for Payment
        <small>Please ensure that the payment is made via cheque or NEFT using the details below:</small>
      </h5>
      <div class="infoContainer">
        <p><strong> Bank Name:</strong> ABC Bank<br>
          <strong>Account Name:</strong> OneSmarter .Inc<br>
          <strong>Account Number:</strong> 1234567890<br>
          <strong>IFSC Code:</strong> ABCD0123456<br>
          <strong>Branch:</strong> Dayton, OH 45459, STE 223
        </p>

        <p>Cheques can also be dropped at the program office or sent via post. Please mention the institute name on the
          back
          of the cheque.</p>
      </div>
    </div>

    <div class="summaryContainer">
      <h5>Faculty Confirmation
        <small>Before proceeding, please confirm the accuracy of the student list and total fee.</small>
      </h5>
      <div class="infoContainer">
        <form action="{{ route('faculty.courses.institutePayment.submit', $simulationOverview->id) }}" method="POST"
          class="requestSubmission">
          @csrf
          <div class="inputField">
            <label for="">Name of the Institute</label>
            <input type="text" name="institute_name" placeholder="Enter full name of your institute">
            <small>Please provide the official name of your institute for billing and communication purposes.</small>
          </div>
          <label for="acceptance"><input type="checkbox" name="acceptance" id="acceptance"> I confirm that the above
            student
            list and fee details are accurate, and this information is ready to be submitted to my institute for
            payment.</label>
          <h6>Total Payable Amount: {{ config('currency.symbol') . number_format(count($students) * $course_fee) }} </h6>
          <div class="submitButton">
            <button type="submit">Confirm & Submit to Institute</button>
          </div>
        </form>
      </div>
    </div>

  </div>

@endsection
