<div class="inviteStudent">
  <div class="avatar">
    <img src="{{ asset('images/avatar-user.svg') }}" alt="">
  </div>
  <label for="">INVITE STUDENT</label>
  <form action="{{ route('faculty.courses.invite', $simulation->id) }}" method="POST" autocomplete="off"
    class="sendInvite">
    @csrf
    <input type="hidden" name="simulation_id" value="{{ $simulation->id }}">
    <input type="hidden" name="faculty_id" value="{{ $sharedActiveUserData->id }}"> 
    <div class="textInput">
      <input type="text" name="email" id="" value="{{ old('email') }}"
        placeholder="Ex: alice.johnson@example.com">
      @error('email')
        <small class="error_text">{{ $message }}</small>
      @enderror
    </div>
    <button>Send Invite</button>
  </form>
  <div class="externalButton">
    <a href="{{ route('faculty.courses.invitees', $simulation->id) }}">List Of Invitees</a>
    <a href="{{ route('faculty.courses.students', $simulation->id) }}">List Of Students</a>
  </div>
</div>