@extends('admin.layout.static')
@section('title', 'Simulation Order')
@section('staticTemplate')
  <div class="pageTitle">
    <h4>Create Simulation Order</h4>
    <p>Provide details for the simulation. These settings will be used to create teams, enroll learners, and schedule
      decision runs.</p>
  </div>

  <div class="templateCrud">
    <div class="dataForm">
      <form action="{{ route('admin.simulation-order.store') }}" id="createRecord" method="POST" autocomplete="off">
        @csrf

        {{-- Course & Institution --}}
        <div class="dataRow">
          <div class="inputField">
            <label for="course_title">Course / Simulation Title *</label>
            <input type="text" id="course_title" name="course_title" maxlength="120"
              placeholder="Ex: Supply Chain Strategy (MBA 2025)" value="{{ old('course_title') }}">
            <small class="help_text">Enter the full course or simulation title (max 120 characters).</small>
            @error('course_title')
              <small class="error_text">{{ $message }}</small>
            @enderror
          </div>

          <div class="inputField">
            <label for="organization">Institution / Organization *</label>
            <input type="text" id="organization" name="organization" maxlength="120"
              placeholder="Ex: Global Logistics Institute" value="{{ old('organization') }}">
            <small class="help_text">Name of the institution or organization conducting the simulation.</small>
            @error('organization')
              <small class="error_text">{{ $message }}</small>
            @enderror
          </div>
        </div>

        {{-- Faculty --}}
        <div class="dataRow">
          <div class="inputField">
            <label for="faculty_id">Assign Faculty *</label>
            <div class="selectbox">
              <select id="faculty_id" name="faculty_id">
                <option value="">-- Select Faculty --</option>
                @foreach ($faculties as $faculty)
                  <option value="{{ $faculty->id }}" {{ old('faculty_id') == $faculty->id ? 'selected' : '' }}>
                    {{ Str::ucfirst($faculty->first_name . ' ' . $faculty->last_name) }}
                  </option>
                @endforeach
                <option value="0" {{ old('faculty_id') === '0' ? 'selected' : '' }}>Other</option>
              </select>
            </div>
            <small class="help_text">Select an existing faculty or choose "Other" to add a new one.</small>
            @error('faculty_id')
              <small class="error_text">{{ $message }}</small>
            @enderror
          </div>

          <div class="inputField">
            <label for="faculty_name">Faculty Name (if Other selected)</label>
            <input type="text" id="faculty_name" name="faculty_name" maxlength="120" placeholder="Ex: Dr. John Smith"
              value="{{ old('faculty_name') }}">
            <small class="help_text">Only required if you selected "Other" above.</small>
            @error('faculty_name')
              <small class="error_text">{{ $message }}</small>
            @enderror
          </div>

          <div class="inputField">
            <label for="faculty_email">Faculty Email (if Other selected)</label>
            <input type="email" id="faculty_email" name="faculty_email" maxlength="120"
              placeholder="Ex: john.smith@example.com" value="{{ old('faculty_email') }}">
            <small class="help_text">Required only if you selected "Other" above.</small>
            @error('faculty_email')
              <small class="error_text">{{ $message }}</small>
            @enderror
          </div>
        </div>

        {{-- Learners & Fees --}}
        <div class="dataRow">
          <div class="inputField">
            <label for="learners">Number of Learners / Participants *</label>
            <input type="number" id="learners" name="learners" min="1" step="1" placeholder="Ex: 30"
              value="{{ old('learners') }}">
            <small class="help_text">Total number of learners/participants to be enrolled.</small>
            @error('learners')
              <small class="error_text">{{ $message }}</small>
            @enderror
          </div>

          <div class="inputField">
            <label for="fee_per_learner">Fee per Learner (USD) *</label>
            <input type="number" id="fee_per_learner" name="fee_per_learner" step="1" min="100"
              placeholder="Ex: 100" value="{{ old('fee_per_learner', 100) }}">
            <small class="help_text">Enter fee per learner in USD (include any applicable taxes).</small>
            @error('fee_per_learner')
              <small class="error_text">{{ $message }}</small>
            @enderror
          </div>
        </div>

        {{-- Quarters & Teams --}}
        <div class="dataRow">
          <div class="inputField">
            <label for="quarters">Number of Quarters *</label>
            <input type="number" id="quarters" name="quarters" min="1" max="50" placeholder="Ex: 4"
              value="{{ old('quarters', 5) }}">
            <small class="help_text">Total number of quarters (simulation rounds).</small>
            @error('quarters')
              <small class="error_text">{{ $message }}</small>
            @enderror
          </div> 

          <div class="inputField">
            <label for="teams">Number of Firms / Groups *</label>
            <input type="number" id="teams" name="teams" min="1" step="1" placeholder="Ex: 6"
              value="{{ old('teams', 3) }}">
            <small class="help_text">Total number of competing firms/groups in the simulation.</small>
            @error('teams')
              <small class="error_text">{{ $message }}</small>
            @enderror
          </div>
        </div>

        {{-- Schedule --}}
        <div class="dataRow">
          <div class="inputField">
            <label for="start_date">Course Start Date *</label>
            <input type="date" id="start_date" name="start_date" value="{{ old('start_date') }}">
            <small class="help_text">Date the simulation begins.</small>
            @error('start_date')
              <small class="error_text">{{ $message }}</small>
            @enderror
          </div>

          <div class="inputField">
            <label for="payment_responsibility">Payment Responsibility *</label>
            <div class="selectbox">
              <select id="payment_responsibility" name="payment_responsibility">
                <option value="">-- Select --</option>
                <option value="learner" {{ old('payment_responsibility') == 'learner' ? 'selected' : '' }}>Learners pay
                </option>
                <option value="institution" {{ old('payment_responsibility') == 'institution' ? 'selected' : '' }}>
                  Institution pays</option>
              </select>
            </div>
            <small class="help_text">Choose who will be billed for learner fees.</small>
            @error('payment_responsibility')
              <small class="error_text">{{ $message }}</small>
            @enderror
          </div>

          <div class="inputField">
            <label for="time_zone">Time Zone (U.S.) *</label>
            <div class="selectbox">
              <select id="time_zone" name="time_zone">
                <option value="">-- Select U.S. Time Zone --</option>
                <option value="America/New_York" {{ old('time_zone') == 'America/New_York' ? 'selected' : '' }}>Eastern
                  (America/New_York)</option>
                <option value="America/Chicago" {{ old('time_zone') == 'America/Chicago' ? 'selected' : '' }}>Central
                  (America/Chicago)</option>
                <option value="America/Denver" {{ old('time_zone') == 'America/Denver' ? 'selected' : '' }}>Mountain
                  (America/Denver)</option>
                <option value="America/Phoenix" {{ old('time_zone') == 'America/Phoenix' ? 'selected' : '' }}>Arizona
                  (America/Phoenix, no DST)</option>
                <option value="America/Los_Angeles" {{ old('time_zone') == 'America/Los_Angeles' ? 'selected' : '' }}>
                  Pacific (America/Los_Angeles)</option>
                <option value="America/Anchorage" {{ old('time_zone') == 'America/Anchorage' ? 'selected' : '' }}>Alaska
                  (America/Anchorage)</option>
                <option value="Pacific/Honolulu" {{ old('time_zone') == 'Pacific/Honolulu' ? 'selected' : '' }}>Hawaii
                  (Pacific/Honolulu)</option>
              </select>
            </div>
            <small class="help_text">Select the time zone in which this simulation will run.</small>
            @error('time_zone')
              <small class="error_text">{{ $message }}</small>
            @enderror
          </div>
        </div>

        {{-- Special Instructions --}}
        <div class="dataRow">
          <div class="inputField">
            <label for="special_instructions">Special Instructions</label>
            <textarea id="special_instructions" name="special_instructions" rows="4" maxlength="250"
              placeholder="Ex: Run decision rounds weekly; allow one date extension.">{{ old('special_instructions') }}</textarea>
            <small class="help_text">Optional notes for scheduling or configuration (max 250 characters).</small>
            @error('special_instructions')
              <small class="error_text">{{ $message }}</small>
            @enderror
          </div>
        </div>

        <div class="dataRow action_button">
          <button type="submit">Create Simulation Order</button>
        </div>
      </form>
    </div>
  </div>
@endsection
