@extends('faculty.simulation.layout')
@section('title', 'Quarter')
@section('inheritTemplate')
  <div class="quarterInfo">
    <div class="sectionTitle">
      <h4>FIRM PERFORMANCE QUARTER {{ str_pad($quarterInfo->quarter_name, 2, '0', STR_PAD_LEFT) }} </h4>
      <p>Quarter Duration: {{ \Carbon\Carbon::parse($quarterInfo->start_date)->format('m/d/Y') }} to
        {{ \Carbon\Carbon::parse($quarterInfo->end_date)->format('m/d/Y') }}</p>
      <ul></ul>
      <p class="text-center"> <a href="{{ route('faculty.courses.show', $simulationOverview->id) }}"><i
            class="fa fa-angle-left" aria-hidden="true"></i> Back to Simulation Details</a></p>
    </div> 
    {{-- analysisSummary --}}
    <div class="analysisSummary"> 
      <table border="1" cellpadding="5" cellspacing="0">
        @foreach ($summary as $regionData)
          <tr>
            <td colspan="100%" class="region"> Region: {{ $regionData['region']->id }}
              ({{ $regionData['region']->name }})
              {{ $regionData['region']->custome_name }} </td>
          </tr>
          @foreach ($regionData['products'] as $productData)
            <tr>
              <td colspan="100%" class="product"> Product: {{ $productData['product']->id }}
                {{ '(' . $productData['product']->sort_name . ') - ' . $productData['product']->name }}
              </td>
            </tr>
            @foreach ($productData['channels'] as $channelData)
              <tr>
                <td colspan="100%" class="channel"> Channel: {{ $channelData['channel']->id }}
                  ({{ $channelData['channel']->title }})
                </td>
              </tr>
              <tr class="topheader">
                <th colspan="3">Data Summary</th>
                <th colspan="6">Current Data</th>
                <th colspan="3">Past Data</th>
                <th colspan="3">Change (%)</th>
                <th>Demand</th>
              </tr>
              <tr>
                <th>Firm</th>
                <th>Volume</th>
                <th>Market Share</th>
                <th>Price</th>
                <th class="market_price">Mkt Price</th>
                <th>PQ</th>
                <th>SQ</th>
                <th>AQ</th>
                <th>CAI</th>

                <th class="past">CAI</th>
                <th class="past">Price</th>
                <th class="past market_price">Mkt Price</th>

                <th class="center">CI</th>
                <th class="center">Price</th>
                <th class="center">Net Volume</th>

                <th class="demandShifted">New</th>
              </tr>
              @foreach ($channelData['firms'] as $firmData)
                <tr>
                  <td>{{ $firmData['firm']->group_number }}</td>
                  <td>{{ $firmData['volume'] }}</td>
                  <td>{{ $firmData['market_share'] . ' ' . $firmData['market_bar'] }}</td>
                  <td>{{ $firmData['price'] }}</td>
                  <td class="market_price">{{ $firmData['market_price'] }}</td>
                  <td>{{ $firmData['pq'] }}</td>
                  <td>{{ $firmData['sq'] }}</td>
                  <td>{{ $firmData['aq'] }}</td>
                  <td>{{ $firmData['cai'] }}</td>

                  <td class="past">{{ $firmData['pastCI'] }}</td>
                  <td class="past">{{ $firmData['pastPrice'] }}</td>
                  <td class="past">{{ $firmData['past_market_price'] }}</td>

                  <td class="center" width="150">{!! $firmData['ci_change'] !!}</td>
                  <td class="center" width="150">{!! $firmData['price_change'] !!}</td>
                  <td class="center" width="100">{!! $firmData['net_volume_change'] !!}</td>

                  <td class="demandShifted" width="75">{{ $firmData['adjusted_demand'] }}</td>
                </tr>
              @endforeach
            @endforeach
          @endforeach
        @endforeach
      </table>
    </div>
    <div>
      <strong>Notes:</strong><br>
      (1) "Volume" is sales volume in units.<br>
      (2) "Price", "PQ", "SQ", and "Av" = end-customer price, product quality, service quality, availability.<br>
      (3) "*" indicates change of more than 2% vs. last month.<br>
      (4) "#" indicates reconfigured product this month.<br>
      (5) "#" with "*" = reconfigured product with unfilled orders.
    </div>
    <p>&nbsp;</p>

  </div>
@endsection
