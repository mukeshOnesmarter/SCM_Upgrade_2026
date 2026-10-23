@php
  $routeParams = ['simulation_id' => request('simulation_id'), 'quarter_id' => request('quarter_id', 3)];

  $extraReports = [
      'finished-goods' => 'Finished Goods Inventory Report',
      'procurement' => 'Procurement Inventory Report',
      'forecast-accuracy' => 'Forecasting Accuracy Report',
      'service-ops' => 'Service Center Operations Reports',
      'decision-vars' => 'Other Decision Variables Report',
      'industry-bulletin' => 'Industry Bulletin',
      // 'consulting-reports' => 'Purchased Reports',
  ];

@endphp
<div class="listSection">
  <div class="sectionTitle">
    {{-- <div class="item"><img src="{{ asset('images/icon/report.svg') }}" alt=""></div> --}}
    <div class="item">
      <h6>Additional Reports</h6>
    </div>
  </div>
  <ul>
    @foreach ($extraReports as $route => $label)
      <li><a class="{{ request()->routeIs("student.simulation.reports.extra.$route") ? 'active' : '' }}"
          href="{{ route("student.simulation.reports.extra.$route", $routeParams) }}"><i class="fa fa-angle-double-right" aria-hidden="true"></i> {{ $label }}</a></li>
    @endforeach
  </ul>
</div>
