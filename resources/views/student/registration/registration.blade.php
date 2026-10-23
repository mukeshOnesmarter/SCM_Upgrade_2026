<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Student Registration</title>
  <link rel="icon" type="image/x-icon" href="{{ asset('images/favicon.ico') }}">
  <link rel="stylesheet" type="text/css" href="{{ URL::asset('css/registration.css') }}" />
  <link rel="stylesheet" type="text/css" href="{{ URL::asset('css/font-awesome.min.css') }}" />
  <link rel="stylesheet" type="text/css" href="{{ URL::asset('css/animate.css') }}" />
</head>

<body>
  <div class="registration">

    @if (session('error'))
      <div class="alertContainer">
        <div class="alert alert-error">
          <h4>Registration Error</h4>
          <p>{!! session('error') !!}</p>
        </div>
      </div>
    @endif

    @if (session('invalidCode'))
      <div class="alertContainer">
        <div class="alert alert-error">
          <h4>Invalid Course Code</h4>
          <p>{!! session('invalidCode') !!}</p>
          <p>Please check for any typing errors and try again. If the issue persists, contact your instructor or support
            team for assistance.</p>
        </div>
      </div>
    @endif

    @if (session('duplicateAccount'))
      <div class="alertContainer">
        <div class="alert alert-warning">
          <p>{!! session('duplicateAccount') !!}</p>
          <p>Please <a href="{{ route('student.login') }}">log in</a> or use a different email to register.</p>
        </div>
      </div>
    @endif

    @if (session('accountCreated'))
      <div class="alertContainer">
        <div class="alert alert-success">
          <h4>Dear Student</h4>
          <p>Thank you for registering with Flexee.</p>
          <p>{!! session('accountCreated') !!}</p>
          <p>Please check your inbox and click the <strong>"Validate Account"</strong> link to activate your profile.
          </p>
        </div>
      </div>
    @endif

    @if (session('inviteAccepted'))
      <div class="alertContainer">
        <div class="alert alert-success">
          <h4>Invitation Accepted Successfully</h4>
          <p>You have successfully accepted the invitation to join this simulation.<br>
            Once the simulation is ready to use, you will be able to access and participate in it.</p>
          <p>You are already enrolled with us.</p>
          <p>You may <a href="{{ route('student.login') }}">log in</a> using your existing password. <br> <small>If you
              have forgotten your password, please reset it to continue.</small></p>
        </div>
      </div>
    @endif

    @if (session('alreadyEnrolled'))
      <div class="alertContainer">
        <div class="alert alert-warning">
          <h4> Already Enrolled </h4>
          <p> You are already enrolled in this simulation. </p>
          <p> Please <a href="{{ route('student.login') }}"><strong>log in</strong></a> using your existing password.
            <br> <small>If you have forgotten your password, please reset it to continue.</small> </p>
        </div>
      </div>
    @endif

    <div class="registrationContainer">
      <div class="crud_header">
        <h5>Student Registration</h5>
        <small>Fields marked with * are mandatory</small>
      </div>
      <div class="dataForm">
        <form action="{{ route('save-student-registration') }}" method="POST" enctype="multipart/form-data"
          autocomplete="off">
          @csrf
          <div class="dataRow">
            <div class="inputComponent">
              <label for="title">* Simulation Code</label>
              <div class="inputField">
                {{-- <input type="text" name="simulationCode" maxlength="30" value="{{ old('simulationCode') }}"> --}}
                <input readonly type="text" name="simulationCode" maxlength="30"
                  value="{{ $simulation->share_code }}">
                <span class="icon"><i class="fa fa-pencil" aria-hidden="true"></i></span>
              </div>
              @error('simulationCode')
                <small class="error_text">{{ $message }}</small>
              @enderror
            </div>
          </div>
          <div class="dataRow">
            <div class="inputComponent">
              <label for="title">* First Name</label>
              <div class="inputField">
                <input type="text" name="first_name" maxlength="70" value="{{ old('first_name') }}">
              </div>
              @error('first_name')
                <small class="error_text">{{ $message }}</small>
              @enderror
            </div>
            <div class="inputComponent">
              <label for="title">* Last Name</label>
              <div class="inputField">
                <input type="text" name="last_name" maxlength="70" value="{{ old('last_name') }}">
              </div>
              @error('last_name')
                <small class="error_text">{{ $message }}</small>
              @enderror
            </div>
          </div>
          <div class="dataRow">
            <div class="inputComponent">
              <label for="title">* Email</label>
              <div class="inputField">
                {{-- <input type="text" name="email" maxlength="70" value="{{ old('email') }}"> --}}
                <input readonly type="text" name="email" maxlength="70" value="{{ $student_email }}">
                <span class="icon"><i class="fa fa-envelope-o" aria-hidden="true"></i></span>
              </div>
              @error('email')
                <small class="error_text">{{ $message }}</small>
              @enderror
            </div>
            <div class="inputComponent">
              <label for="title">* Password</label>
              <div class="inputField">
                <input type="password" name="password" maxlength="70" value="{{ old('password') }}">
                <span class="icon"><i class="fa fa-unlock-alt" aria-hidden="true"></i></span>
              </div>
              @error('password')
                <small class="error_text">{{ $message }}</small>
              @enderror
            </div>
          </div>
          <div class="dataRow">
            <input type="hidden" name="faculty_id" value="{{ $faculty->id }}">
            <input type="hidden" name="faculty_code" value="{{ $faculty->faculty_code }}">
            <input type="hidden" name="simulation_id" value="{{ $simulation->id }}">
            <input type="hidden" name="simulation_code" value="{{ $simulation->share_code }}">
            <div class="inputComponent action_button">
              <button type="submit">Submit</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</body>

</html>
