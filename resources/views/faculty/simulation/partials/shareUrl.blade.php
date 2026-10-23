<div class="registrationLink">
  <div class="sectionTitle">
    <h5>Share Registration Link</h5>
    <p>Share this direct registration link with students. <span>Students can use this link to register themselves and
        join the simulation directly.</span> </p>
  </div>
  <div class="inviteDataForm">
    <div class="shareLink">
      <label>Registration URL</label>
      <textarea id="registrationLink" rows="1" readonly>{{ $registrationUrl }}</textarea>
      <small class="help_text">Click copy and share this link with students.</small>
    </div>
    <div class="uploadSubmit">
      <button type="button" onclick="copyRegistrationLink()">
        <i class="fa fa-link" aria-hidden="true"></i> Copy Link
      </button>
    </div>
  </div>
</div>
