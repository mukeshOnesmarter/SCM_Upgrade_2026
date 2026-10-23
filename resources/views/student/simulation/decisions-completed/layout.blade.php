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
            You can review past performance data, including:
          </p>
          <ul>
            @php $param = ['simulation_id' => $commonHeader->id, 'quarter_id' => $commonHeader->quarter_selected] @endphp
            <li><a href="{{ route('student.simulation.reports.product-pnl', $param) }}"><i class="fa fa-angle-double-right"
                  aria-hidden="true"></i> Product Profit And Loss Statements</a></li>
            <li><a href="{{ route('student.simulation.reports.cash-flow', $param) }}"><i class="fa fa-angle-double-right"
                  aria-hidden="true"></i> Cash Flow Analysis Report</a></li>
            <li><a href="{{ route('student.simulation.reports.extra.finished-goods', $param) }}"><i
                  class="fa fa-angle-double-right" aria-hidden="true"></i> Finished Goods Inventory Report</a></li>
            <li><a href="{{ route('student.simulation.reports.extra.procurement', $param) }}"><i
                  class="fa fa-angle-double-right" aria-hidden="true"></i> Procurement Inventory Report</a></li>
            <li><a href="{{ route('student.simulation.reports.extra.forecast-accuracy', $param) }}"><i
                  class="fa fa-angle-double-right" aria-hidden="true"></i> Forecasting Accuracy Report</a></li>
          </ul>
          <p><a class="moveBack"
              href="{{ route(Route::currentRouteName(), [$commonHeader->id, $commonHeader->quarter_inprocess->quarter_name]) }}">
              <img src="{{ URL::asset('images/icon/circle-back.svg') }}" alt=""> Move To Currently Active
              Quarter</a> </p>
        </div>
      </div>
      <div class="reportSection">
        <div class="sectionTitle">
          <h4>Quarterly Closure Insights <img src="{{ URL::asset('images/icon/reports.svg') }}" alt=""></h4>
        </div>
        <div class="quarterClosureInfo" id="quarterClosureInfo">
          @yield('quarterClosureInfo')
        </div>
      </div>
    </div>
  </div>
@endsection
