<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <title>Quarter Closed – Industry Summary</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      color: #333;
      line-height: 1.6;
    }

    p {
      margin-bottom: 10px;
    }

    strong {
      font-weight: bold;
    }

    ul {
      margin-top: 0;
    }

    table {
      border-collapse: collapse;
      margin-top: 10px;
    }

    td {
      padding: 3px 5px;
      vertical-align: top;
    }
  </style>
</head> 

<body>
  <h2>Industry Status After Round {{ $quarterName }}</h2>

  <p><strong>Total Units Sold:</strong> {{ number_format($totalUnitsSold) }}</p>

  <p><strong>Market Share:</strong></p>
  <ul>
    @foreach ($marketShares as $firm => $share)
      <li>Firm {{ $firm }} market share is {{ $share }}%</li>
    @endforeach
  </ul>

  {{-- <p><strong>Top Revenue Leaders:</strong></p>
  <ul>
    @foreach ($topRevenues as $firm => $revenue)
      <li>Firm {{ $firm }} with ${{ number_format($revenue, 2) }}</li>
    @endforeach
  </ul> --}}

  <p><strong>DC Activity:</strong></p>
  <ul>
    @if (count($openedDcs) > 0)
      @foreach ($openedDcs as $dc)
        <li>{{ $dc['firm'] }} opened a DC in {{ $dc['region'] }}</li>
      @endforeach
    @else
      <li>No new DC's were opened</li>
    @endif

    @if (count($closedDcs) > 0)
      @foreach ($closedDcs as $dc)
        <li>{{ $dc['firm'] }} closed a DC in {{ $dc['region'] }}</li>
      @endforeach
    @else
      <li>No DC closings were reported</li>
    @endif
  </ul>

  <p><strong>Alerts:</strong></p>
  <ul>
    @if (count($priceAlerts) > 0)
      @foreach ($priceAlerts as $alert)
        <li>{{ $alert['supplier'] }} price for {{ $alert['component'] }} has increased from
          ${{ $alert['old_price'] }} to ${{ $alert['new_price'] }} — this price change is in effect only for the next
          quarter.</li>
      @endforeach
    @else
      <li>No supplier price changes detected for next quarter.</li>
    @endif
  </ul>

  <p>Kind regards,<br /> The Flexee Team</p>
</body>

</html>
