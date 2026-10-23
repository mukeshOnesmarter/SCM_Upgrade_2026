<div class="asideLeft">
  <div class="logo"> <a href="{{ route('student.dashboard') }}"> <img
        src="{{ URL::asset('images/logo-student-admin.svg') }}" alt=""> </a>
  </div>
  <div class="decisions">
    <div class="section">
      @php $routeParams = ['simulation_id' => request('simulation_id'), 'quarter_id' => request('quarter_id', 3)]; @endphp
      <h6>DECISIONS</h6>
      <div class="navigation">
        <ul>
          @isset($commonHeader)
            @php
              $quarter = $commonHeader->quarter_inprocess ?? null;
              $isCurrentQuarter = $quarter && $quarter->quarter_name === request('quarter_id');
              $isQuarterClosed =
                  $quarter &&
                  ($quarter->end_date < now()->toDateString() ||
                      ($quarter->end_date == now()->toDateString() && $quarter->end_time <= now()->format('H:i:s')));
            @endphp
            @if ($isCurrentQuarter && !$isQuarterClosed)
              <li class="disabled"> DASHBOARD </li>
            @else
              <li><a class="{{ request()->routeIs('student.simulation.simulation-dashboard') ? 'active' : '' }}"
                  href="{{ route('student.simulation.simulation-dashboard', $routeParams) }}">
                  DASHBOARD</a></li>
            @endif
          @endisset

          @foreach ($commonHeader->decisionEnable as $item)
            <li><a class="{{ request()->routeIs('student.simulation.decisions.' . $item['route']) ? 'active' : '' }}"
                href="{{ route('student.simulation.decisions.' . $item['route'], $routeParams) }}">
                {{ Str::upper($item['title']) }}</a></li>
          @endforeach
          {{-- <a class="{{ (request()->routeIs('student.simulation.buyreports.*') 
                || request()->routeIs('student.simulation.reports.extra.consulting-reports')) ? 'active' : '' }}"
                href="{{ route('student.simulation.buyreports.earnings', $routeParams) }}">
                BUY REPORTS </a> --}}
        </ul>
      </div>
    </div>
    @isset($commonHeader)
      @php
        $quarter = $commonHeader->quarter_inprocess ?? null;
        $isCurrentQuarter = $quarter && $quarter->quarter_name === request('quarter_id');
        $isQuarterClosed =
            $quarter &&
            ($quarter->end_date < now()->toDateString() ||
                ($quarter->end_date == now()->toDateString() && $quarter->end_time <= now()->format('H:i:s')));
      @endphp
      <div class="section">
        <h6>REPORTS</h6>
        <div class="downloadReport">
          @if ($isCurrentQuarter && !$isQuarterClosed)
            <small class="info">Visible after quarter end only.</small>
            <ul>
              <li class="disabled">CORPORATE P&L</li>
              <li class="disabled">PRODUCT P&L</li>
              <li class="disabled">BALANCE SHEET</li>
              <li class="disabled">CASH FLOW ANALYSIS</li>
              <li class="disabled">INVENTORY STOCK</li>
              <li class="disabled">ADDITIONAL REPORTS</li>
            </ul>
          @else
            <ul>
              <li><a class="{{ request()->routeIs('student.simulation.reports.corporate-pnl') ? 'active' : '' }}"
                  href="{{ route('student.simulation.reports.corporate-pnl', $routeParams) }}">
                  CORPORATE P&L</a></li>
              <li><a class="{{ request()->routeIs('student.simulation.reports.product-pnl') ? 'active' : '' }}"
                  href="{{ route('student.simulation.reports.product-pnl', $routeParams) }}">
                  PRODUCT P&L</a></li>
              <li><a class="{{ request()->routeIs('student.simulation.reports.balance-sheet') ? 'active' : '' }}"
                  href="{{ route('student.simulation.reports.balance-sheet', $routeParams) }}">
                  BALANCE SHEET</a></li>
              <li><a class="{{ request()->routeIs('student.simulation.reports.cash-flow') ? 'active' : '' }}"
                  href="{{ route('student.simulation.reports.cash-flow', $routeParams) }}">
                  CASH FLOW ANALYSIS</a></li>
              <li><a class="{{ request()->routeIs('student.simulation.reports.inventory-stock') ? 'active' : '' }}"
                  href="{{ route('student.simulation.reports.inventory-stock', $routeParams) }}">
                  INVENTORY STOCK</a></li>
              <li><a
                  class="{{ request()->routeIs('student.simulation.reports.extra.*') &&
                  !request()->routeIs('student.simulation.reports.extra.consulting-reports')
                      ? 'active'
                      : '' }}"
                  href="{{ route('student.simulation.reports.extra.finished-goods', $routeParams) }}">
                  ADDITIONAL REPORTS </a></li>
            </ul>
          @endif
        </div>
      </div>
    @endisset
  </div>
  <div class="supportingLinks">
    {{-- <div class="item">
      <a href="{{ route('student.simulation.products', request('simulation_id')) }}"><img
          src="{{ URL::asset('images/icon/products.svg') }}" alt=""> PRODUCTS</a>
    </div> --}}
    <div class="item">
      <a
        href="{{ route('student.simulation.team-member', ['simulation_id' => request('simulation_id'), 'quarter_id' => 4]) }}"><img
          src="{{ URL::asset('images/icon/group.svg') }}" alt=""> TEAM MEMBERS</a>
    </div>
  </div>
</div>
