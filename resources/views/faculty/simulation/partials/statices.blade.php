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
