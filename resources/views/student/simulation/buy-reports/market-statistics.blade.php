@php $pageTitle = 'Market Statistics'; @endphp
@php $price = config('currency.symbol') . 2500; @endphp
@php $routeParams = ['simulation_id' => request('simulation_id'), 'quarter_id' => request('quarter_id') ]; @endphp

@extends('student.simulation.buy-reports.layout')
@section('title', $pageTitle)
@section('downloadlink', route('student.simulation.download.balance-sheet', $routeParams))
@section('reportTemplate')
  <div class="report-info">
    <h1>{{ $pageTitle }}</h1>
    <p>This research provides an overview of market performance, including total demand, market share, and sales by
      region.
      It helps you understand broader market trends and your competitive positioning.</p>
    <p>Cost: {{ $price }}</p>

    <div class="download">
      @if ($hasPurchased)
        <div>This report has already been purchased for Quarter {{ $quarter }}.</div>
      @else
        <form action="{{ route('student.simulation.buyreports.buyConsultingReport', $routeParams) }}" method="POST">
          @csrf
          <input type="hidden" name="simulation_id" value="{{ $simulation_id }}">
          <input type="hidden" name="quarter" value="{{ $quarter }}">
          <input type="hidden" name="consulting_fee_id" value="{{ $consulting_fee_id }}">
          <button>Buy Report</button>
        </form>
      @endif
    </div>
  </div>

  <div class="report-sample">
    <h5><img src="{{ asset('images/icon/excel.svg') }}" alt=""> Sample Output — Research Study (Market Statistics)</h5> 
    <h6>Industry Demand</h6>
    
    <table class="table table-striped mb-4">
      <thead class="thead-light">
        <tr>
          <th>Product / Region</th>
          <th class="text-right">QTR 04</th>
          <th class="text-right">QTR 05</th>
          <th class="text-right">QTR 06</th>
          <th class="text-right">QTR 07</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Smart Home Assistant Demand (Region 1)</td>
          <td class="text-right">60,231</td>
          <td class="text-right">59,075</td>
          <td class="text-right">59,244</td>
          <td class="text-right">59,165</td>
        </tr>
        <tr>
          <td>Smart Home Assistant Unfilled (Region 1)</td>
          <td class="text-right">0</td>
          <td class="text-right">0</td>
          <td class="text-right">0</td>
          <td class="text-right">0</td>
        </tr>
        <tr>
          <td>Smart Thermostat Demand (Region 1)</td>
          <td class="text-right">29,940</td>
          <td class="text-right">31,385</td>
          <td class="text-right">31,145</td>
          <td class="text-right">30,422</td>
        </tr>
        <tr>
          <td>Smart Thermostat Unfilled (Region 1)</td>
          <td class="text-right">0</td>
          <td class="text-right">0</td>
          <td class="text-right">0</td>
          <td class="text-right">0</td>
        </tr>
        <tr>
          <td colspan="5" class="text-center">… (Region 2 and others omitted) …</td>
        </tr>
      </tbody>
    </table>

    <h6>Overall Market Shares</h6>
    <table class="table table-striped mb-4">
      <thead class="thead-light">
        <tr>
          <th>Firm</th>
          <th class="text-right">QTR 04</th>
          <th class="text-right">QTR 05</th>
          <th class="text-right">QTR 06</th>
          <th class="text-right">QTR 07</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Firm 1</td>
          <td class="text-right">18.0</td>
          <td class="text-right">26.6</td>
          <td class="text-right">25.3</td>
          <td class="text-right">20.7</td>
        </tr>
        <tr>
          <td>Firm 2</td>
          <td class="text-right">19.5</td>
          <td class="text-right">17.4</td>
          <td class="text-right">18.8</td>
          <td class="text-right">17.9</td>
        </tr>
      </tbody>
    </table>

    <h6>Retail Channel Inventory [Units]</h6>
    <table class="table table-striped mb-4">
      <thead class="thead-light">
        <tr>
          <th>Product / Region</th>
          <th class="text-right">QTR 04</th>
          <th class="text-right">QTR 05</th>
          <th class="text-right">QTR 06</th>
          <th class="text-right">QTR 07</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Product 1-1 SHA (Region 1)</td>
          <td class="text-right">2,128</td>
          <td class="text-right">2,260</td>
          <td class="text-right">2,257</td>
          <td class="text-right">2,653</td>
        </tr>
        <tr>
          <td>Product 2-1 ST (Region 1)</td>
          <td class="text-right">2,178</td>
          <td class="text-right">2,377</td>
          <td class="text-right">2,345</td>
          <td class="text-right">2,266</td>
        </tr>
      </tbody>
    </table>

    <h6>Retail Channel Inventory [Months of Inventory at Current Sales Volume]</h6>
    <table class="table table-striped mb-4">
      <thead class="thead-light">
        <tr>
          <th>Product / Region</th>
          <th class="text-right">QTR 04</th>
          <th class="text-right">QTR 05</th>
          <th class="text-right">QTR 06</th>
          <th class="text-right">QTR 07</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Product 1-1 SHA (Region 1)</td>
          <td class="text-right">0.38</td>
          <td class="text-right">0.33</td>
          <td class="text-right">0.40</td>
          <td class="text-right">0.39</td>
        </tr>
        <tr>
          <td>Product 2-1 ST (Region 1)</td>
          <td class="text-right">0.51</td>
          <td class="text-right">0.37</td>
          <td class="text-right">0.45</td>
          <td class="text-right">0.40</td>
        </tr>
      </tbody>
    </table>

    <h6>Retail Channel Margin</h6>
    <table class="table table-striped">
      <thead class="thead-light">
        <tr>
          <th>Product / Region</th>
          <th class="text-right">QTR 04</th>
          <th class="text-right">QTR 05</th>
          <th class="text-right">QTR 06</th>
          <th class="text-right">QTR 07</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Product 1-1 SHA (Region 1)</td>
          <td class="text-right">1,459,436</td>
          <td class="text-right">1,608,804</td>
          <td class="text-right">1,743,830</td>
          <td class="text-right">1,244,650</td>
        </tr>
        <tr>
          <td>Product 2-1 SHA (Region 1)</td>
          <td class="text-right">1,462,715</td>
          <td class="text-right">1,278,837</td>
          <td class="text-right">1,342,770</td>
          <td class="text-right">1,296,460</td>
        </tr>
      </tbody>
    </table>
  </div>
@endsection
