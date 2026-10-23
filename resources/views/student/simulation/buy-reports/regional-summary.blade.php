@php $pageTitle = 'Regional Summary Analysis'; @endphp
@php $price = config('currency.symbol') . 5000; @endphp
@php $routeParams = ['simulation_id' => request('simulation_id'), 'quarter_id' => request('quarter_id') ]; @endphp

@extends('student.simulation.buy-reports.layout')
@section('title', $pageTitle)
@section('downloadlink', route('student.simulation.download.balance-sheet', $routeParams))
@section('reportTemplate')
  <div class="report-info">
    <h1>{{ $pageTitle }}</h1>
    <p>This study summarizes sales, profits, and performance metrics by region. It helps identify which markets are most
      profitable and where expansion or corrective action may be needed.</p>
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
    <h5><img src="{{ asset('images/icon/excel.svg') }}" alt=""> Sample Output — Research Study (Regional Summary Analysis)</h5>
    <h6>Region 1 — Smart Home Assistant (Channel 1)</h6>

    <table class="table table-striped mb-4">
      <thead class="thead-light">
        <tr>
          <th>Product</th>
          <th class="text-right">Volume</th>
          <th class="text-center">Market Share</th>
          <th class="text-right">Price</th>
          <th class="text-right">PQ</th>
          <th class="text-right">SQ</th>
          <th class="text-right">Av</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>1-1</td>
          <td class="text-right">15,906</td>
          <td class="text-center">
            <div class="market-bar">
              <div class="market-bar-fill" style="width: 9.9%;"></div>
            </div>
            <div class="market-text">9.9%</div>
          </td>
          <td class="text-right">707+</td>
          <td class="text-right">41</td>
          <td class="text-right">21</td>
          <td class="text-right">54+</td>
        </tr>
        <tr>
          <td>5-1</td>
          <td class="text-right">9,391</td>
          <td class="text-center">
            <div class="market-bar">
              <div class="market-bar-fill mid" style="width: 5.9%;"></div>
            </div>
            <div class="market-text">5.9%</div>
          </td>
          <td class="text-right">439</td>
          <td class="text-right">43</td>
          <td class="text-right">39</td>
          <td class="text-right">42</td>
        </tr>
        <tr>
          <td>7-1</td>
          <td class="text-right">32,519</td>
          <td class="text-center">
            <div class="market-bar">
              <div class="market-bar-fill light" style="width: 20.4%;"></div>
            </div>
            <div class="market-text">20.4%</div>
          </td>
          <td class="text-right">439</td>
          <td class="text-right">64</td>
          <td class="text-right">38</td>
          <td class="text-right">43</td>
        </tr>
        <tr>
          <td>8-1</td>
          <td class="text-right">16,096</td>
          <td class="text-center">
            <div class="market-bar">
              <div class="market-bar-fill" style="width: 10.1%;"></div>
            </div>
            <div class="market-text">10.1%</div>
          </td>
          <td class="text-right">650</td>
          <td class="text-right">34</td>
          <td class="text-right">18</td>
          <td class="text-right">43</td>
        </tr>
      </tbody>
    </table>

    <h6>Region 1 — Smart Home Assistant (Channel 2)</h6>
    <table class="table table-striped mb-4">
      <thead class="thead-light">
        <tr>
          <th>Product</th>
          <th class="text-right">Volume</th>
          <th class="text-center">Market Share</th>
          <th class="text-right">Price</th>
          <th class="text-right">PQ</th>
          <th class="text-right">SQ</th>
          <th class="text-right">Av</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>1-2</td>
          <td class="text-right">13,238</td>
          <td class="text-center">
            <div class="market-bar">
              <div class="market-bar-fill" style="width: 8.3%;"></div>
            </div>
            <div class="market-text">8.3%</div>
          </td>
          <td class="text-right">670+</td>
          <td class="text-right">40–</td>
          <td class="text-right">18</td>
          <td class="text-right">40–</td>
        </tr>
        <tr>
          <td>5-2</td>
          <td class="text-right">12,162</td>
          <td class="text-center">
            <div class="market-bar">
              <div class="market-bar-fill mid" style="width: 7.6%;"></div>
            </div>
            <div class="market-text">7.6%</div>
          </td>
          <td class="text-right">390+</td>
          <td class="text-right">9+</td>
          <td class="text-right">9+</td>
          <td class="text-right">33+</td>
        </tr>
        <tr>
          <td>8-2</td>
          <td class="text-right">13,225</td>
          <td class="text-center">
            <div class="market-bar">
              <div class="market-bar-fill light" style="width: 8.3%;"></div>
            </div>
            <div class="market-text">8.3%</div>
          </td>
          <td class="text-right">653</td>
          <td class="text-right">35</td>
          <td class="text-right">20</td>
          <td class="text-right">26</td>
        </tr>
      </tbody>
    </table>

    <h6>Region 1 — Smart Thermostat (Channel 1)</h6>
    <table class="table table-bordered mb-4">
      <thead class="thead-light">
        <tr>
          <th>Product</th>
          <th class="text-right">Volume</th>
          <th class="text-center">Market Share</th>
          <th class="text-right">Price</th>
          <th class="text-right">PQ</th>
          <th class="text-right">SQ</th>
          <th class="text-right">Av</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>1-2</td>
          <td class="text-right">3,323</td>
          <td class="text-center">
            <div class="market-bar">
              <div class="market-bar-fill light" style="width: 3.3%;"></div>
            </div>
            <div class="market-text">3.3%</div>
          </td>
          <td class="text-right">918+</td>
          <td class="text-right">46</td>
          <td class="text-right">29</td>
          <td class="text-right">55+</td>
        </tr>
        <tr>
          <td>3-2</td>
          <td class="text-right">12,860</td>
          <td class="text-center">
            <div class="market-bar">
              <div class="market-bar-fill" style="width: 12.7%;"></div>
            </div>
            <div class="market-text">12.7%</div>
          </td>
          <td class="text-right">708+</td>
          <td class="text-right">42+</td>
          <td class="text-right">29</td>
          <td class="text-right">49+</td>
        </tr>
        <tr>
          <td>7-2</td>
          <td class="text-right">8,895</td>
          <td class="text-center">
            <div class="market-bar">
              <div class="market-bar-fill mid" style="width: 8.8%;"></div>
            </div>
            <div class="market-text">8.8%</div>
          </td>
          <td class="text-right">699+</td>
          <td class="text-right">34</td>
          <td class="text-right">32</td>
          <td class="text-right">43</td>
        </tr>
      </tbody>
    </table>

    <h6 class="mt-4">Notes:</h6>
    <ul>
      <li><strong>Volume:</strong> sales volume in units.</li>
      <li><strong>PQ:</strong> perceived product quality; <strong>SQ:</strong> service quality; <strong>Av:</strong>
        availability.</li>
      <li>“+” or “–” shows increase/decrease from previous month.</li>
      <li>“r” denotes reconfigured product; “u” denotes unfilled orders.</li>
    </ul>
  </div>


@endsection
