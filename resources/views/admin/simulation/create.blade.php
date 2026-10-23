@extends('admin.layout')
@section('title', 'Add New Course')
@php
  $selectedTz = old('timezone', 'America/New_York'); 
  $allZones = \DateTimeZone::listIdentifiers(); 
  $usZones = array_filter($allZones, fn($z) => str_starts_with($z, 'America/') && !str_contains($z, 'Argentina') && !str_contains($z, 'Sao_Paulo'));
  $otherZones = array_diff($allZones, $usZones);
@endphp
@section('template')
  <div class="wraptemplateBody">
    <div class="templateBody">
      <div class="templateCrud">
        <div class="crud_header">
          <h5>Add Course / Simulation</h5>
          <small>Fields marked with * are mandatory</small>
        </div>
        <div class="dataForm">
          <form action="{{ route('admin.simulation.store') }}" id="createRecord" method="POST" enctype="multipart/form-data"
            autocomplete="off">
            @csrf 
            <div class="dataRow">
              <div class="inputField">
                <label for="title">Name of the Course / Simulation *</label>
                <input type="text" name="title" maxlength="70" placeholder="Ex: Supply Chain Fundamentals 101."
                  value="{{ old('title') ?? 'Ecosystem Management Simulator' }}">
                <small class="help_text">Enter the name of the course or simulation you are creating.</small>
                @error('title')
                  <small class="error_text">{{ $message }}</small>
                @enderror
              </div>
              <div class="inputField">
                <label for="title">Name of the Organization *</label>
                <input type="text" name="organization" maxlength="70" placeholder="Ex: Global Logistics Institute."
                  value="{{ old('organization') ?? 'Wright State University' }}">
                <small class="help_text">Enter the name of the organization conducting this simulation or course.</small>
                @error('organization')
                  <small class="error_text">{{ $message }}</small>
                @enderror
              </div>
            </div>
            <div class="dataRow">
              <div class="inputField">
                <label for="title">Start Date *</label>
                <div class="input_with_icon calendar">
                  <input class="datepicker" type="text" name="start_date" maxlength="70"
                    placeholder="Ex: 01-30-2025 (MM-DD-YYYY)" value="{{ old('start_date') }}">
                </div>
                <small class="help_text">Select the start date of the simulation.</small>
                @error('start_date')
                  <small class="error_text">{{ $message }}</small>
                @enderror
              </div>
              <div class="inputField">
                <label for="title">End Date *</label>
                <div class="input_with_icon calendar">
                  <input class="datepicker" type="text" name="end_date" maxlength="70"
                    placeholder="Ex: 02-15-2025 (MM-DD-YYYY)" value="{{ old('end_date') }}">
                </div>
                <small class="help_text">Select the end date of the simulation.</small>
                @error('end_date')
                  <small class="error_text">{{ $message }}</small>
                @enderror
              </div> 

              <div class="inputField">
                <label for="timezone">Timezone *</label>
                <div class="selectbox">
                  <select name="timezone" id="timezone" class="select_field" required>
                     <optgroup label="US Timezones">
                        @foreach($usZones as $tz)
                          <option value="{{ $tz }}" {{ $selectedTz === $tz ? 'selected' : '' }}>
                            {{ $tz }}
                          </option>
                        @endforeach
                      </optgroup>

                      <optgroup label="Other Timezones (Worldwide)">
                        @foreach($otherZones as $tz)
                          <option value="{{ $tz }}" {{ $selectedTz === $tz ? 'selected' : '' }}>
                            {{ $tz }}
                          </option>
                        @endforeach
                      </optgroup>
                  </select>
                </div>
                <small class="help_text">Select the timezone for the simulation.</small>
                @error('timezone')
                  <small class="error_text">{{ $message }}</small>
                @enderror
              </div>
            </div>
            <div class="dataRow">
              <div class="inputField">
                <label for="title">Specify the Number of Quarters *</label>
                <input type="text" name="quarters" maxlength="2" placeholder="Ex: 4"
                  value="{{ old('quarters') ?? '5' }}" onkeypress="return event.charCode >= 48 && event.charCode <= 57">
                <small class="help_text">Specify the total number of quarters for the simulation.</small>
                @error('quarters')
                  <small class="error_text">{{ $message }}</small>
                @enderror
              </div>
              <div class="inputField">
                <label for="title">Specify the Number of Firms / Group *</label>
                <input type="text" name="firms" maxlength="2" placeholder="Ex: 3"
                  value="{{ old('firms') ?? '3' }}" onkeypress="return event.charCode >= 48 && event.charCode <= 57">
                <small class="help_text">Indicate how many firms will participate in this simulation.</small>
                @error('firms')
                  <small class="error_text">{{ $message }}</small>
                @enderror
              </div>
              <div class="inputField">
                <label for="title">Specify the Days for Each Quarter *</label>
                <input type="text" name="quarter_duration" maxlength="2" placeholder="Ex: 4"
                  value="{{ old('quarter_duration') ?? '4' }}"
                  onkeypress="return event.charCode >= 48 && event.charCode <= 57">
                <small class="help_text">Enter a number of days for each quarter between 1 and 10.</small>
                @error('quarter_duration')
                  <small class="error_text">{{ $message }}</small>
                @enderror
              </div>
            </div>
            <div class="dataRow action_button">
              <button type="submit"> Save & Manage Quarters</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
