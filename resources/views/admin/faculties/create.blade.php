@extends('admin.layout.static')
@section('title', 'Faculties')
@section('staticTemplate')
  <div class="pageTitle">
    <h4>Add Faculty</h4>
    <p>Manage faculty members and their details. Add new faculty profiles to the system.</p>
  </div>

  <div class="templateCrud">
    <div class="dataForm">
      <form action="{{ route('admin.faculties.store') }}" id="createRecord" method="POST" autocomplete="off">
        @csrf
        <div class="dataRow">
          <div class="inputField">
            <label for="first_name">First Name *</label>
            <input type="text" name="first_name" maxlength="70" placeholder="Ex: John" value="{{ old('first_name') }}">
            <small class="help_text">Enter the faculty's first name.</small>
            @error('first_name')
              <small class="error_text">{{ $message }}</small>
            @enderror
          </div>

          <div class="inputField">
            <label for="last_name">Last Name *</label>
            <input type="text" name="last_name" maxlength="70" placeholder="Ex: Doe" value="{{ old('last_name') }}">
            <small class="help_text">Enter the faculty's last name.</small>
            @error('last_name')
              <small class="error_text">{{ $message }}</small>
            @enderror
          </div>
        </div>

        <div class="dataRow">
          <div class="inputField">
            <label for="email">Email *</label>
            <input type="email" name="email" maxlength="70" placeholder="Ex: john.doe@example.com"
              value="{{ old('email') }}">
            <small class="help_text">Enter a valid and unique email address for the faculty.</small>
            @error('email')
              <small class="error_text">{{ $message }}</small>
            @enderror
          </div>

          <div class="inputField">
            <label for="phone">Phone</label>
            <input type="text" name="phone" maxlength="10" placeholder="Ex: 9876543210" value="{{ old('phone') }}">
            <small class="help_text">Enter a 10-digit phone number (optional).</small>
            @error('phone')
              <small class="error_text">{{ $message }}</small>
            @enderror
          </div>
        </div>
        <div class="dataRow action_button">
          <button type="submit">Save Faculty</button>
        </div>
      </form>
    </div>
  </div>
@endsection
