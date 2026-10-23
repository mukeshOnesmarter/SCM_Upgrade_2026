@extends('student.simulation.layout')
@section('title', 'Buy Reports')
@section('template')
  <div class="buyReports" id="buyReports">
    @include('student.simulation.buy-reports.partials.commonHeader')
    <div class="reportContainer">
      <div class="contentSection">
        @yield('reportTemplate')
      </div>
      @include('student.simulation.buy-reports.partials.reports')
    </div>
  </div>
@endsection
