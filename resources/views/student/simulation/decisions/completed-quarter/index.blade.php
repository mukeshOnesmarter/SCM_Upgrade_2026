@extends('student.simulation.layout')
@section('title', 'Forecasting')
@section('template')
  <div class="decisionTemplate">
    @include('student.simulation.decisions.partials.commonHeader')

    <div class="quarterCompleted">
      <div class="quarterSummary">
        <div class="sectionTitle">
          <h4>Completed <img src="{{ URL::asset('images/icon/completed.svg') }}" alt=""></h4>
        </div>
        <div class="relatedInfo">
          <p>This quarter was completed on
            <strong>{{ \Carbon\Carbon::parse($commonHeader->quarter_selected_info->end_date)->format('M d, Y') }}</strong>
            <br>
            You can review past performance data, including:</p>
          <ul>
            <li><i class="fa fa-angle-double-right" aria-hidden="true"></i> Sales and Revenue Reports</li>
            <li><i class="fa fa-angle-double-right" aria-hidden="true"></i> Inventory and Stock Levels</li>
            <li><i class="fa fa-angle-double-right" aria-hidden="true"></i> Supplier and Procurement Records</li>
            <li><i class="fa fa-angle-double-right" aria-hidden="true"></i> Production and Manufacturing Data</li>
            <li><i class="fa fa-angle-double-right" aria-hidden="true"></i> Logistics and Delivery Performance</li>
          </ul>
          <p><a class="moveBack"
              href="{{ route(Route::currentRouteName(), [$commonHeader->id, $commonHeader->quarter_inprocess->quarter_name]) }}">
              <img src="{{ URL::asset('images/icon/circle-back.svg') }}" alt=""> Move To Currently Active
              Quarter</a> </p>
        </div>
      </div>
      <div class="reportSection">
        <div class="sectionTitle">
          <h4>Reports <img src="{{ URL::asset('images/icon/reports.svg') }}" alt=""></h4>
        </div>
      </div>
    </div>
  </div>
@endsection
