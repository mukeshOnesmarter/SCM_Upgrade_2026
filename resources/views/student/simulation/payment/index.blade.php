@extends('student.layout')
@section('title', 'Simulation')
@section('template')
  <div class="invitationContainer">
    <div class="pageTitle">
      <h4>Settle Payment to Continue</h4>
      <p>To begin your simulation, please complete the course fee payment below.</p>
      <p>Secure your seat by making the payment using the available methods.</p>
    </div>
    <div class="coursePayment">
      <h4>Total Payable Amount: {{ config('currency.symbol') . number_format('1000') }} </h4>

      <div class="summaryContainer">
        <h5>Payment Options</h5>
        <div class="infoContainer resetPadding">
          <div class="paymentoption">
            <h6>1. Pay Now (Self Payment)</h6>
            <p>Make an instant payment using your Debit or Credit Card.</p>
            <a href="{{ route('student.simulation.stripe.checkout', ['simulation_id' => request('simulation_id')]) }}">Pay
              with Card</a>
          </div>
          <div class="paymentoption">
            <h6>2. NEFT / Bank Transfer</h6>
            <p> You can make a bank transfer using the following details: </p>
            <ul>
              <li><strong>Account Name:</strong> Flexee LLC</li>
              <li><strong>Bank Name:</strong> ABC Bank</li>
              <li><strong>Account Number:</strong> 123456789012</li>
              <li><strong>IFSC Code:</strong> ABCD0123456</li>
              <li><strong>Branch:</strong> Dayton, OH 45459, STE 223</li>
            </ul>
            <p> After completing the transfer, please write your <strong>Simulation ID</strong> and <strong>Student
                ID</strong> in
              the transaction note (if possible), and share the payment confirmation with your institute coordinator or
              email it to <strong>payments@flexee.org</strong>.</p>
          </div>
          <div class="paymentoption">
            <h6>3. Drop a Cheque</h6>
            <p>If you prefer to pay via cheque, please issue it in favor of:</p>
            <p><strong>Payee Name:</strong> Flexee LLC</p>

            <p><strong>Important Instructions:</strong><br>
              Write your Simulation ID and Student ID clearly on the back of the cheque.<br>
              Submit the cheque to your institute’s finance/coordinator office / courier it directly to the address below:
            </p>

            <p><strong>Courier Address:</strong><br>
              Flexee LLC<br>
              6545 Market Ave. North STE 100<br>
              North Canton, OH 44721</p>

            <p>Note: Your payment will be marked as confirmed once the cheque is received and cleared.</p>
          </div>
        </div>
      </div>

      <div class="summaryContainer">
        <h5>Terms & Confirmation</h5>
        <div class="infoContainer">
          <ul style="list-style-type: none">
            <li>- This payment confirms your seat in the simulation.</li>
            <li>- Once paid, you'll receive confirmation via email and your dashboard will unlock.</li>
            <li>- Payment once made is non-refundable.</li>
          </ul>
          <p>Optional Notes: We use secure and encrypted payment gateways. Your details are safe with us.</p>
        </div>
      </div>
    </div>
  </div>
@endsection
