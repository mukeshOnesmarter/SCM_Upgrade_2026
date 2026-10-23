@extends('admin.layout.static')
@section('title', 'My Profile')
@section('staticTemplate')
  <div class="pageTitle">
    <h4>My Profile</h4>
    <p>Manage your account details and settings. Update your personal information, change your password, and configure
      preferences to keep your profile secure and up to date.</p>
  </div>

  <div class="templateCrud">
    {{-- PROFILE UPDATE --}}
    <div class="crud_header">
      <h5>Update Profile</h5>
      <small>Fields marked with * are mandatory</small>
    </div>
    <div class="dataForm">
      <form method="POST" action="{{ route('admin.profile.update') }}" autocomplete="off">
        @csrf
        @method('PUT')
        <div class="dataRow">
          <div class="inputField">
            <label for="title">Email (readonly)</label>
            <input type="text" readonly value="{{ $user->email }}">
            <small class="help_text">Your registered email address. This cannot be changed here.</small>
          </div>
          <div class="inputField">
            <label for="title">Phone</label>
            <input type="text" name="phone" maxlength="10" value="{{ old('phone', $user->phone) }}"
              onkeypress="return event.charCode >= 48 && event.charCode <= 57">
            <small class="help_text">Enter your 10-digit phone number.</small>
            @error('phone')
              <small class="error_text">{{ $message }}</small>
            @enderror
          </div>
        </div>
        <div class="dataRow">
          <div class="inputField">
            <label for="title">First Name *</label>
            <input type="text" name="first_name" maxlength="70" value="{{ old('first_name', $user->first_name) }}">
            <small class="help_text">Enter your first name as you want it displayed in the system.</small>
            @error('first_name')
              <small class="error_text">{{ $message }}</small>
            @enderror
          </div>
          <div class="inputField">
            <label for="title">Last Name *</label>
            <input type="text" name="last_name" maxlength="70" value="{{ old('last_name', $user->last_name) }}">
            <small class="help_text">Enter your last name as you want it displayed in the system.</small>
            @error('last_name')
              <small class="error_text">{{ $message }}</small>
            @enderror
          </div>
        </div>
        <div class="dataRow action_button">
          <button type="submit"> Update Profile </button>
        </div>
      </form>
    </div>

    {{-- CHANGE PASSWORD --}}
    <div class="crud_header">
      <h5>Change Password</h5>
      <small>Fields marked with * are mandatory</small>
    </div>
    <div class="dataForm">
      <form method="POST" action="{{ route('admin.profile.password') }}" autocomplete="off">
        @csrf
        @method('PUT')
        <div class="dataRow">
          <div class="inputField">
            <label for="title">Current Password *</label>
            <input type="password" name="current_password">
            <small class="help_text">Enter your current password to verify your identity.</small>
            @error('current_password')
              <small class="error_text">{{ $message }}</small>
            @enderror
          </div>
        </div>
        <div class="dataRow">
          <div class="inputField">
            <label for="title">New Password *</label>
            <input type="password" name="password">
            <small class="help_text">Enter a strong new password (at least 8 characters, mix of letters and
              numbers).</small>
            @error('password')
              <small class="error_text">{{ $message }}</small>
            @enderror
          </div>
          <div class="inputField">
            <label for="title">Confirm New Password *</label>
            <input type="password" name="password_confirmation">
            <small class="help_text">Re-enter your new password to confirm it matches exactly.</small>
            @error('password_confirmation')
              <small class="error_text">{{ $message }}</small>
            @enderror
          </div>
        </div>
        <div class="dataRow action_button">
          <button type="submit"> Update Password </button>
        </div>
      </form>
    </div>
  </div>
@endsection
