@extends('faculty.simulation.layout')
@section('title', 'Student Group Members')
@section('sectionTitle', 'Add Students to Simulation')
@section('sectionDescription',
  'Use the form below to add a single student or upload an Excel file for bulk student
  import.')
@section('inheritTemplate')
  <div class="addStudent">
    <div class="templateCrud">
      <h5>Bulk Invite Students</h5>
      <p>Upload an Excel or CSV file to invite multiple students at once. <br> Each student will receive an email with a
        registration link and can create their own login credentials to join the simulation.</p>
      <div class="dataForm">
        <form action="" enctype="multipart/form-data" autocomplete="off">
          @csrf
          <div class="dataRow">
            <div class="">
              <img src="{{ asset('images/icon/excel.svg') }}" alt="" width="60px">
            </div>
            <div class="inputField">
              <label for="file">Select File to Upload *</label>
              <input type="file" name="file" required>
              <small class="help_text">Supported formats: .xlsx, .xls, .csv</small>
            </div>
          </div>
          <div class="dataRow action_button">
            <button type="submit">Bulk Invite</button>
          </div>
        </form>
      </div>
    </div>
    <div class="templateCrud">
      <h5>Add Single Student</h5>
      <p>Enter the details below to add a new student individually into the simulation.</p>
      <div class="dataForm">
        <form action="{{ route('faculty.courses.storeStudent', $simulationOverview->id) }}" method="POST"
          enctype="multipart/form-data" autocomplete="off">
          <input type="hidden" name="simulation_id" value="{{ $simulationOverview->id }}" id="">
          @csrf
          <div class="dataRow">
            <div class="inputField">
              <label for="simulation">Simulation *</label>
              <input readonly type="text" value="{{ $simulationOverview->title }}">
              <small class="help_text">This student will be added to the selected simulation.</small>
            </div>
          </div>
          <div class="dataRow">
            <div class="inputField">
              <label for="first_name">First Name *</label>
              <input type="text" name="first_name" maxlength="70" placeholder="Ex: John"
                value="{{ old('first_name') }}">
              <small class="help_text">Enter the student's given name.</small>
              @error('first_name')
                <small class="error_text">{{ $message }}</small>
              @enderror
            </div>
            <div class="inputField">
              <label for="last_name">Last Name *</label>
              <input type="text" name="last_name" maxlength="70" placeholder="Ex: Doe" value="{{ old('last_name') }}">
              <small class="help_text">Enter the student's surname.</small>
              @error('last_name')
                <small class="error_text">{{ $message }}</small>
              @enderror
            </div>
            <div class="inputField">
              <label for="email">Email *</label>
              <input type="email" name="email" maxlength="70" placeholder="Ex: john.doe@example.com"
                value="{{ old('email') }}">
              <small class="help_text">Enter a valid, unique email for the invitation.</small>
              @error('email')
                <small class="error_text">{{ $message }}</small>
              @enderror
            </div>
          </div>
          <div class="dataRow">
            <div class="inputField">
              <label for="phone">Phone</label>
              <input type="text" name="phone" maxlength="10" placeholder="Ex: 9876543210"
                value="{{ old('phone') }}">
              <small class="help_text">Optional. Enter a 10-digit contact number.</small>
              @error('phone')
                <small class="error_text">{{ $message }}</small>
              @enderror
            </div>

            <div class="inputField">
              <label for="emergency_contact">Emergency Contact</label>
              <input type="text" name="emergency_contact" maxlength="10" placeholder="Ex: 9876543210"
                value="{{ old('emergency_contact') }}">
              <small class="help_text">Optional. Enter an alternate emergency contact number.</small>
              @error('emergency_contact')
                <small class="error_text">{{ $message }}</small>
              @enderror
            </div>
          </div>
          <div class="dataRow action_button">
            <button type="submit">Add Student</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection
