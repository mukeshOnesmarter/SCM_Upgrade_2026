@if (Session::has('message'))
  <div class="screenAlert success" id="screenAlert">
    <div class="alertContainer">
      <div class="alert">
        <p class="message">{{ Session::get('message') }}</p>
      </div>
    </div>
  </div>
@endif

@if (Session::has('success'))
  <div class="screenAlert success" id="screenAlert">
    <div class="alertContainer">
      <div class="alert">
        <p class="message">{{ Session::get('success') }}</p>
      </div>
    </div>
  </div>
@endif

@if (Session::has('error'))
  <div class="screenAlert error" id="screenAlert">
    <div class="alertContainer">
      <div class="alert">
        <p class="message">{{ Session::get('error') }}</p>
      </div>
    </div>
  </div>
@endif

@if (Session::has('info'))
  <div class="screenAlert warning" id="screenAlert">
    <div class="alertContainer">
      <div class="alert">
        <p class="message">{{ Session::get('info') }}</p>
      </div>
    </div>
  </div>
@endif

@if (Session::has('warning'))
  <div class="screenAlert warning" id="screenAlert">
    <div class="alertContainer">
      <div class="alert">
        <p class="message">{{ Session::get('warning') }}</p>
      </div>
    </div>
  </div>
@endif

{{-- @if ($errors->any())
  <div class="screenAlert error" id="screenAlert">
    <div class="alertContainer">
      <div class="alert">
        @foreach ($errors->all() as $error)
          <p class="message">{{ $error }}</p>
        @endforeach
      </div>
    </div>
  </div>
@endif --}}
