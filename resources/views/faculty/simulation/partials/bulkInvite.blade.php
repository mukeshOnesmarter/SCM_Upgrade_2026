<div class="bulkInvite">
  <div class="sectionTitle">
    <h5>Bulk Invite Students</h5>
    <p>Upload an Excel or CSV file to invite multiple students at once. <span> Each student will receive an email with a
        registration link and can create their own login credentials to join the simulation.</span></p>
  </div>
  @if (session('bulkErrors'))
    <div class="errorText">
      <strong>Failed Emails:</strong>
      <p>{{ implode(', ', session('bulkErrors')) }}</p>
    </div>
  @endif
  <form action="{{ route('faculty.courses.bulkInvitees', $simulation->id) }}" enctype="multipart/form-data" method="POST"
    autocomplete="off">
    @csrf
    <input type="hidden" name="simulation_id" value="{{ $simulation->id }}">
    <input type="hidden" name="faculty_id" value="{{ $sharedActiveUserData->id }}">
    <div class="inviteDataForm">
      <div class="uploadFile">
        <div class="item">
          <img src="{{ asset('images/icon/excel.svg') }}" alt="" width="60px">
        </div>
        <div class="item">
          <label for="file">Select File to Upload *</label>
          <input type="file" name="file" required>
          <small class="help_text">Supported formats: .xlsx, .xls, .csv</small>
        </div>
        <div class="item downloadFormat">
          <a href="{{ asset('storage/bulk-invites.xlsx') }}" target="_blank"><i class="fa fa-file-excel-o"
              aria-hidden="true"></i> Download Format</a>
        </div>
      </div>
      <div class="uploadSubmit">
        <button type="submit"><i class="fa fa-file-text" aria-hidden="true"></i> Bulk Invite</button>
      </div>
    </div>
  </form>
</div>
