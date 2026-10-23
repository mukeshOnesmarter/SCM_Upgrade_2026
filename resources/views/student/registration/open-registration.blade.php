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

    @if (session('alreadyEnrolled'))
      <div class="alertContainer">
        <div class="alert alert-warning">
          <h4> Already Enrolled </h4>
          <p> You are already enrolled in this simulation. </p>
          <p> Please <a href="{{ route('student.login') }}"><strong>log in</strong></a> using your existing password.
            <br> <small>If you have forgotten your password, please reset it to continue.</small>
          </p>
        </div>
      </div>
    @endif

    @if (session('accountCreated'))
      <div class="alertContainer">
        <div class="alert alert-success">
          <h4>Registration Successful</h4>
          <p>Thank you for registering with Flexee.<br>
            Once the simulation becomes available, you will be able to access and participate in it. </p>
          <p>You may <a href="{{ route('student.login') }}"><strong>log in</strong></a> using your registered email
            address and password.<br>
            <small>If you have forgotten your password, please use the reset option to continue.</small>
          </p>
        </div>
      </div>
    @endif

    <div class="registrationContainer">
      <div class="crud_header">
        <h5>Student Registration - Dr. Vikram Sethi</h5>
        <small>Fields marked with * are mandatory</small>
      </div>
      <div class="dataForm">
        <form action="{{ route('self-student-registration.store') }}" method="POST" enctype="multipart/form-data"
          autocomplete="off">
          @csrf
          <div class="dataRow">
            <div class="inputComponent">
              <label for="title">* First Name</label>
              <div class="inputField">
                <input type="text" name="first_name" maxlength="70" value="{{ old('first_name') }}" autocomplete="off">
              </div>
              @error('first_name')
                <small class="error_text">{{ $message }}</small>
              @enderror
            </div>
            <div class="inputComponent">
              <label for="title">* Last Name</label>
              <div class="inputField">
                <input type="text" name="last_name" maxlength="70" value="{{ old('last_name') }}" autocomplete="off">
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
                <input type="email" name="email" maxlength="70" value="{{ old('email') }}" autocomplete="new-email">
                <span class="icon"><i class="fa fa-envelope-o" aria-hidden="true"></i></span>
              </div>
              @error('email')
                <small class="error_text">{{ $message }}</small>
              @enderror
            </div>
            <div class="inputComponent">
              <label for="title">* Password</label>
              <div class="inputField">
                <input type="password" name="password" maxlength="70" value="{{ old('password') }}" autocomplete="new-password">
                <span class="icon"><i class="fa fa-unlock-alt" aria-hidden="true"></i></span>
              </div>
              @error('password')
                <small class="error_text">{{ $message }}</small>
              @enderror
            </div>
          </div>
          <div class="dataRow">
            <input type="hidden" name="faculty_id" value="{{ $faculty->id }}">
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
