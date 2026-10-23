@php
  $routeParams = ['simulation_id' => request('simulation_id'), 'quarter_id' => request('quarter_id', 3)];

  $getReports = [
      'earnings' => 'Earnings Report',
      'balancesheet' => 'Balance Sheet Report',
      'procurement' => 'Procurement Report',
      'manufacturing' => 'Manufacturing Report',
      'distribution' => 'Distribution Report',
      'transportation' => 'Transportation Report',
      'generatedemand' => 'Generate Demand Report',
      'it' => 'Information Technology Report',
      'operating' => 'Operating Statistics Report',
      'market' => 'Market Statistics Report',
      'region' => 'Regional Summary Report',
      // 'customersatisfaction' => 'Customer Satisfaction Report',
      // 'pricesensitivity' => 'Price Sensitivity Analysis',
  ];

@endphp
<div class="listSection">
  <ul>
    @foreach ($getReports as $route => $label)
      <li><a class="{{ request()->routeIs("student.simulation.buyreports.$route") ? 'active' : '' }}"
          href="{{ route("student.simulation.buyreports.$route", $routeParams) }}"><i class="fa fa-angle-double-right"
            aria-hidden="true"></i> {{ $label }}</a></li>
    @endforeach
  </ul>
</div>
