@extends('student.layout')
@section('template')
  <div class="joinSimulation">
    <div class="simulationContainer">
      <div class="shareCodeContainer">
        <form method="POST" action="{{ route('student.review-Simulation') }}" autocomplete="off" class="formContainer">
          @csrf
          <h4>ENTER THE CODE TO JOIN SIMULATION <small>Enter the code that has been sent to you by the faculty to join the simulation.</small></h4>
          <div class="inputField">
            <input class="allow-paste" type="text" name="simulation_code" id="simulation_code" placeholder="Ex: ZX569#@!38"
              value="{{ old('simulation_code') }}"> 
          </div>
          <div class="inputField actionButton">
            <button>JOIN SIMULATION</button>
          </div> 
          <div class="alertMessage">
            @error('simulation_code')
            <small class="error_text">{{ $message }}</small>
            @enderror
          </div> 
        </form>
        <div class="helpContainer">
          <h5>AFTER JOINING THE SIMULATION <small>WHAT ARE THE NEXT STEPS TO EXPLORE?</small></h5>
          <p>Forecast, Procurement, Manufacture, Distribution, Transport, Service, Demand, Inventory Management , Order
            Management: Supplier Management, Reports and Analytics etc.</p>
        </div>
        
         
      </div>
      <div class="graphic"><img src="{{ URL::asset('images/graphic-01.jpg') }}" alt=""></div>
    </div>
  </div>
  
@endsection
