@extends('student.simulation.layout')
@section('title', request()->routeIs('student.simulation.reports.') ? 'Financial Reports' : 'Additional Reports')
@section('template')
  <div class="financialReports" id="financialReports">
    <div class="pageTitle">
      <h4>Reports</h4>
      <p>Understanding and tracking your firm's financial and operational metrics is crucial for effective management and
        decision-making in the FLEX simulation.</p>
    </div>
    @php
      $routeParams = ['simulation_id' => request('simulation_id'), 'quarter_id' => request('quarter_id', 3)];

      $financialReports = [
          'corporate-pnl' => 'Corporate Profit & Loss Statements',
          'product-pnl' => 'Product Profit And Loss Statements',
          'balance-sheet' => 'Balance Sheet',
          'cash-flow' => 'Cash Flow Analysis Report',
      ];

      $extraReports = [
          'finished-goods' => 'Finished Goods Inventory Report',
          'procurement' => 'Procurement Inventory Report',
          'forecast-accuracy' => 'Forecasting Accuracy Report',
          'service-ops' => 'Service Center Operations Reports',
          'decision-vars' => 'Other Decision Variables Report',
          'industry-bulletin' => 'Products Industry Bulletin',
      ];

    @endphp

    @if (request()->routeIs('student.simulation.reports.extra.'))
      <h4>Additional Reports (Supplementary Notes)</h4>
      <p>For more detailed insights, several additional reports are available as supplementary notes:</p>
      <ul>
        @foreach ($extraReports as $route => $label)
          <li><a href="{{ route("student.simulation.reports.extra.$route", $routeParams) }}">{{ $label }}</a></li>
        @endforeach
      </ul>
    @else
      <h4>Financial Reports</h4>
      <ul>
        @foreach ($financialReports as $route => $label)
          <li><a href="{{ route("student.simulation.reports.$route", $routeParams) }}">{{ $label }}</a></li>
        @endforeach
      </ul>
      <h4>Additional Reports (Supplementary Notes)</h4>
      <p>For more detailed insights, several additional reports are available as supplementary notes:</p>
      <ul>
        @foreach ($extraReports as $route => $label)
          <li><a href="{{ route("student.simulation.reports.extra.$route", $routeParams) }}">{{ $label }}</a></li>
        @endforeach
      </ul>
    @endif
  </div>
@endsection
