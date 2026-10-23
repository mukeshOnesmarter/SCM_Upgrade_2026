@if ($simulationFaculty->payment_mode == 'student')
  <div class="switchPaymentMode">
    <h6>Payment Mode | Currently: Individual Payment</h6>
    <div class="description">
      <p>By default, each student is responsible for paying their own simulation fee (Individual Payment Mode).
        Students will be prompted to make the payment after logging in and accessing the simulation.</p>
      <p>As a faculty member, you have the option to switch to Institute Payment Mode, where your institute covers
        the total fee for all assigned students. Once selected, students will no longer be prompted for payment.</p>
      <p>To proceed with this change, you will be redirected to the payment page to complete the institute payment.
      </p>
      <a href="{{ route('faculty.courses.institutePayment', $simulation->id) }}">Switch to Institute Payment</a>
    </div>
  </div>
@endif
