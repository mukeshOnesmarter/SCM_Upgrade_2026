@if ($lastSavedBy)
  <div class="lastSavedBy">
    <div class="item">
      <img src="{{ asset('images/icon/changes-saved.svg') }}" alt="">
    </div>
    <div class="item profileImage">
      @if (!empty($lastSavedBy->savedByStudent->profile?->profile_picture))
        <img src="{{ URL::asset('storage/uploads/student/' . $lastSavedBy->savedByStudent->profile?->profile_picture) }}"
          alt="">
      @else
        <img src="{{ URL::asset('images/avatar-user.svg') }}" alt="">
      @endif
    </div>
    <div class="item studentinfo">
      <label
        for="heading">{{ $lastSavedBy->savedByStudent->first_name . ' ' . $lastSavedBy->savedByStudent->last_name }}</label>
      <small><strong>Email:</strong> {{ Str::lower($lastSavedBy->savedByStudent->email) }}</small>
      <small><strong>Last changes Saved on:</strong>
        {{ \Carbon\Carbon::parse($lastSavedBy->change_saved_timestamp)->format('M d, Y') }} at
        {{ \Carbon\Carbon::parse($lastSavedBy->change_saved_timestamp)->format('h:i A') }}</small>
    </div>
  </div>
@else 
  <div class="nodecisionSaved">
    Note: No entries have been saved yet. The system has provided sample reference data. Yellow-highlighted fields are editable.
  </div> 
@endif
