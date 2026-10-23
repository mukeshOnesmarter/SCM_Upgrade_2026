<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Product Quality</title>
  <style type="text/css">
    body {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 14px;
    }
    h2 {
      margin-bottom: 5px;
      font-weight: 500px;
    }
    table {
      font-size: 12px;
    }
  </style>
</head>

<body>
  <div class="container">
    <h2 class="mb-4">Product Quality Results: Simulation ID: {{ $simulationId }} | Quarter:  {{ $quarter }}</h2>
    
    @foreach ($pqResults['firm'] as $firmId => $firmData)
      <h3>Firm {{ $firmId }} | PQ: {{ $firmData['summary']['pq'] }}</h3>

      @foreach ($firmData['region'] as $regionId => $regionData)
        @if ($regionId === 'summary')
          @continue
        @endif
        <h4>Region {{ $regionId }} | PQ: {{ $regionData['summary']['pq'] }}</h4>

        @foreach ($regionData['product'] as $productId => $productData)
          @if ($productId === 'summary')
            @continue
          @endif
          <h5>Product {{ $productId }} | PQ: {{ $productData['summary']['pq'] }}</h5>

          <table border="1" cellpadding="5" cellspacing="0">
            <thead>
              <tr>
                <th>Channel</th>
                <th>Quantity</th>
                <th>Total Failed</th>
                <th>Total Procured</th>
                <th>PQ</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($productData['channel'] ?? [] as $channelId => $details)
                <tr>
                  <td>{{ $channelId }}</td>
                  <td>{{ $details['quantity'] }}</td>
                  <td>{{ $details['total_failed'] }}</td>
                  <td>{{ $details['total_procured'] }}</td>
                  <td>{{ $details['pq'] }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        @endforeach
      @endforeach
    @endforeach
  </div>
</body>

</html>
