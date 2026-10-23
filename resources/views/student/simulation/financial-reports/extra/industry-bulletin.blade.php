@php $pageTitle = 'Industry Bulletin'; @endphp
@php $routeParams = ['simulation_id' => request('simulation_id'), 'quarter_id' => request('quarter_id') ]; @endphp
@extends('student.simulation.financial-reports.layout')
@section('title', 'Products Industry Bulletin')
@section('downloadlink', route('student.simulation.download.industry-bulletin', $routeParams))
@section('reportTemplate')
  <div class="industryBulletin" id="industryBulletin">
    <div class="bulletinSection">
      <h5>Total Units Sold:</h5>
      <p> {{ $data['totalUnitsSold'] }}</p>
    </div>
    <div class="bulletinSection">
      <h5>Market Shares:
        <x-info-popover title="What is market shares?" chapter="12" route="{{ route('student.manual.chapter12') }}">
          Percentage of total units sold attributed to each firm.
        </x-info-popover>
      </h5>
      <ul>
        @foreach ($data['marketShares'] as $firm => $share)
          <li>Firm {{ $firm }}: <span>{{ $share }}%</span></li>
        @endforeach
      </ul>
    </div>
    <div class="bulletinSection">
      <h5>Top Revenue Leaders:
        <x-info-popover title="What is top revenue leaders?" chapter="12" route="{{ route('student.manual.chapter12') }}">
          Firms with the highest total revenue this quarter.
        </x-info-popover>
      </h5>
      <ul>
        @foreach ($data['revenueLeaders'] as $firm => $revenue)
          <li>Firm {{ $firm }} - <span>{{ config('currency.symbol') . number_format($revenue, 2) }}</span></li>
        @endforeach
      </ul>
    </div>
    <div class="bulletinSection">
      <h5>Opened DCs:</h5>
      @if (count($data['openedDCs']) > 0)
        <ul>
          @foreach ($data['openedDCs'] as $dc)
            <li>{{ $dc['firm'] }}: <span>Region {{ $dc['region'] }}</span></li>
          @endforeach
        </ul>
      @else
        <p>No Distribution Centers are .</p>
      @endif
    </div>
    <div class="bulletinSection">
      <h5>Closed DCs:</h5>
      @if (count($data['closedDCs']) > 0)
        <ul>
          @foreach ($data['closedDCs'] as $dc)
            <li>{{ $dc['firm'] }}: <span>{{ $dc['region'] }}</span></li>
          @endforeach
        </ul>
      @else
        <p>No Distribution Centers have been closed.</p>
      @endif
    </div>
    <div class="bulletinSection">
      <h5>Supplier Price Alerts:</h5>
      <ul>
        @forelse($data['supplierPriceAlerts'] as $alert)
          <li>{{ $alert['supplier'] }} - {{ $alert['component'] }}:
            <span>{{ config('currency.symbol') . $alert['old_price'] }} →
              {{ config('currency.symbol') . $alert['new_price'] }}</span>
          </li>
        @empty
          <li>No price increases this quarter.</li>
        @endforelse
      </ul>
    </div>
  </div>
  @if ($reportAnalysis && $reportAnalysis->response)
    <div class="reportAnalysis">
      <div class="headingSection">
        <img src="{{ asset('images/ChatGPT-Logo.png') }}" alt="">
        <h2>Quarter {{ request('quarter_id') }} - Industry Bulletin & Insights</h2>
        <p>Summarizing key industry developments, trends, and events affecting the business landscape.</p>
      </div>
      {!! $reportAnalysis->response !!}
    </div>
  @endif
@endsection
