@extends('auth.layout.login')
@section('title', 'Admin Login')
@section('template')
  <div class="loginForm" id="loginForm">
    <h5>ADMIN LOGIN</h5>
    <form action="{{ route('admin.submitLogin') }}" method="POST" autocomplete="off" id="signinForm">
      @csrf
      <div class="inputComponent">
        <label for="title">Registered Email *</label>
        <div class="inputField">
          <input type="email" name="email" id="email" placeholder="youremail@example.com"
            value="vikram@flexee.org">
          <span class="icon"><i class="fa fa-envelope-o" aria-hidden="true"></i></span>
        </div>
      </div>
      <div class="inputComponent">
        <label for="title">Password</label>
        <div class="inputField">
          <input type="password" name="password" id="password" placeholder="*******" value="admin@vikram">
          <span class="icon">
            <a href="#" id="showPassword"><i class="fa fa-eye-slash" aria-hidden="true"></i></a></span>
        </div>
      </div>
      <div class="inputComponent action_button">
        <button type="submit">Login</button>
        <a href="{{ route('password.request', 'admin') }}">Forgot Password?</a>
      </div>
    </form>
  </div>
@endsection
@section('templateScript')
  <script type="text/javascript" defer>
    $(document).ready(function() {

      // SUBMIT FORM
      $("#signinForm").on("submit", function(e) {
        e.preventDefault();
        let form = $(this);

        $.ajax({
          url: form.attr("action"),
          method: "POST",
          data: form.serialize(),
          dataType: "json",
          beforeSend: function() {
            $("#waitingContainer").fadeIn();
          },
          success: function(response) {
            if (response.success) {
              $("#signinForm").trigger("reset");
              $("div.loginSuccessfully").fadeIn();

              setTimeout(function() {
                window.location.href = response.redirect;
              }, 2000);
            } else {
              $("#loginForm").addClass("animated bounce");
              $("div.alertMessage").addClass("error").html(response.message).fadeIn();

              setTimeout(function() {
                $("#loginForm").removeClass("animated bounce");
                $("div.alertMessage").fadeOut().removeClass("error").html('');
              }, 2000);
            }
          },
          error: function(xhr) {
            if (xhr.status === 401 || xhr.status === 422) {
              // HANDLE INVALID CREDENTIALS.
              $("#loginForm").addClass("animated bounce");
              $("div.alertMessage").addClass("error").html(xhr.responseJSON.message).fadeIn();

              setTimeout(function() {
                $("#loginForm").removeClass("animated bounce");
                $("div.alertMessage").fadeOut().removeClass("error").html('');
              }, 2000);

            } else {
              // HANDLE OTHER ERRORS.
              let errorMessage = "An unexpected error occurred.";
              if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
              }
              $("div.alertMessage").addClass("error").html(errorMessage).fadeIn();

              setTimeout(function() {
                $("div.alertMessage").fadeOut().removeClass("error").html('');
              }, 2000);
            }

          },
          complete: function() {
            $("#waitingContainer").fadeOut();
          }
        });
      });


      // SHOW PASSWORD
      $("#showPassword").mousedown(function(e) {
        e.preventDefault();
        $("#password").prop("type", "text");
      });

      // HIDE PASSWORD
      $("#showPassword").mouseup(function(e) {
        e.preventDefault();
        $("#password").prop("type", "password");
      });

    });
  </script>
@endsection
