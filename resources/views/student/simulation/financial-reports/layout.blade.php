@extends('student.simulation.layout')
@section('title', 'Financial Reports')
@section('template')
  <div class="financialReports" id="financialReports">
    @include('student.simulation.financial-reports.partials.commonHeader')
    <div class="pageTitle">
      <h4>{{ request()->routeIs('student.simulation.reports.extra.*') ? 'Additional Reports' : 'Financial Reports' }}</h4>
      <p>Understanding and tracking your firm's financial and operational metrics is crucial for effective management and
        decision-making in the FLEXEE simulation.</p>
    </div>

    <div class="reportContainer">
      <div class="contentSection">
        <div class="sectionTitle">
          <div class="item"><img src="{{ asset('images/icon/report.svg') }}" alt=""></div>
          <div class="item">
            <h6>{{ $pageTitle ?? '' }}</h6>
          </div>
          <div class="item download"> <a href="@yield('downloadlink')" target="_blank"> <i class="fa fa-file-excel-o"
                aria-hidden="true"></i> Download </a> </div>
        </div>
        <div class="reportDescription">
          @yield('reportTemplate')
        </div>
        <div class="reportdownload"> <a href="@yield('downloadlink')" target="_blank"><i class="fa fa-file-excel-o"
              aria-hidden="true"></i> Download</a> </div>
      </div>
      @if (request()->routeIs('student.simulation.reports.extra.*'))
        @include('student.simulation.financial-reports.partials.extraReports')
      @else
        @include('student.simulation.financial-reports.partials.financialReports')
      @endif
    </div>
  </div>
@endsection
