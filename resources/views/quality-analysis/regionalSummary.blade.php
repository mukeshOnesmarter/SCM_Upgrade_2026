<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Regional Summary</title>

</head>

<body>
  <table border="1" cellpadding="5" cellspacing="0">
    @foreach ($summary as $regionData)
      <tr>
        <td colspan="100%"> Region: {{ $regionData['region']->id }} ({{ $regionData['region']->name }})
          {{ $regionData['region']->custome_name }} </td>
      </tr>
      @foreach ($regionData['products'] as $productData)
        <tr>
          <td colspan="100%"> Product: {{ $productData['product']->id }}
            {{ '(' . $productData['product']->sort_name . ') - ' . $productData['product']->name }}
          </td>
        </tr>
        @foreach ($productData['channels'] as $channelData)
          <tr>
            <td colspan="100%"> Channel: {{ $channelData['channel']->id }} ({{ $channelData['channel']->title }}) </td>
          </tr>
          <tr>
            <th>Firm</th>
            <th>Volume</th>
            <th>Market Share</th>
            <th>Price</th>
            <th>PQ</th>
            <th>SQ</th>
            <th>AQ</th>
            <th>CAI</th>

            <th>Past CAI</th>
            <th>Past Price</th>
            <th>Demand (Shifted)</th>

            <th>CI Change (%)</th>
            <th>Price Change (%)</th>
            <th>Net Volume Change (%)</th>
          </tr>
          @foreach ($channelData['firms'] as $firmData)
            <tr>
              <td>{{ $firmData['firm']->group_number }}</td>
              <td>{{ $firmData['volume'] }}</td>
              <td>{{ $firmData['market_share'] . ' ' . $firmData['market_bar'] }}</td>
              <td>{{ $firmData['price'] }}</td>
              <td>{{ $firmData['pq'] }}</td>
              <td>{{ $firmData['sq'] }}</td>
              <td>{{ $firmData['aq'] }}</td>
              <td>{{ $firmData['cai'] }}</td>

              <td>{{ $firmData['pastCI'] }}</td>
              <td>{{ $firmData['pastPrice'] }}</td>
              <td>{{ $firmData['adjusted_demand'] }}</td>

              <td>{{ $firmData['ci_change'] }}</td>
              <td>{{ $firmData['price_change'] }}</td>
              <td>{{ $firmData['net_volume_change'] }}</td>
            </tr>
          @endforeach
        @endforeach
      @endforeach
    @endforeach
  </table>

  <div style="font-family: monospace; font-size: 0.85rem;">
    <strong>Notes:</strong><br>
    (1) "Volume" is sales volume in units.<br>
    (2) "Price", "PQ", "SQ", and "Av" = end-customer price, product quality, service quality, availability.<br>
    (3) "*" indicates change of more than 2% vs. last month.<br>
    (4) "#" indicates reconfigured product this month.<br>
    (5) "#" with "*" = reconfigured product with unfilled orders.
  </div>
</body>

</html>
