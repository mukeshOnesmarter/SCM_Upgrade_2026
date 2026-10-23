@php
  $routeParams = ['simulation_id' => request('simulation_id'), 'quarter_id' => request('quarter_id', 3)];

  $financialReports = [
      'corporate-pnl' => 'Corporate Profit & Loss Statements',
      'product-pnl' => 'Product Profit & Loss Statements',
      'balance-sheet' => 'Balance Sheet',
      'cash-flow' => 'Cash Flow Analysis Report',
  ];

@endphp
<div class="listSection">
  <div class="sectionTitle">
    {{-- <div class="item"><img src="{{ asset('images/icon/report.svg') }}" alt=""></div> --}}
    <div class="item">
      <h6>Financial Reports</h6>
    </div>
  </div>
  <ul>
    @foreach ($financialReports as $route => $label)
      <li><a class="{{ request()->routeIs("student.simulation.reports.$route") ? 'active' : '' }}"
          href="{{ route("student.simulation.reports.$route", $routeParams) }}"><i class="fa fa-angle-double-right" aria-hidden="true"></i> {{ $label }}</a></li>
    @endforeach
  </ul>
</div>
