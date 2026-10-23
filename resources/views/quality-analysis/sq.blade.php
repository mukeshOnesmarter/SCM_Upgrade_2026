<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Service Quality</title>
</head>

<body>
  <h2>Service Quality (SQ) - Quarter {{ $quarter }}</h2>
  @if (count($sqResults) > 0)
    <table border="1">
      <thead>
        <tr>
          <th>Firm</th>
          <th>Region</th>
          <th>Base SQ</th>
          <th>Adjusted SQ</th>
          <th>SQ Guarantee</th>
          <th>Final SQ</th>
          <th>Units Sold</th>
          <th>Call Volume</th>
          <th>Cost/Call</th>
          <th>Total Cost</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($sqResults as $result)
          <tr>
            <td>{{ $result['firm'] }}</td>
            <td>{{ $result['region_id'] }}</td>
            <td>{{ number_format($firmServiceLevels[$result['firm']], 2) }}</td>
            <td>{{ number_format($result['adjusted_sq'], 2) }}</td>
            <td>{{ number_format($result['sq_guarantee'], 2) }}</td>
            <td>{{ number_format($result['final_sq'], 2) }}</td>
            <td>{{ $result['units_sold'] ?? 'N/A' }}</td>
            <td>{{ $result['calls'] }}</td>
            <td>${{ $result['cost_per_call'] }}</td>
            <td>${{ number_format($result['total_cost'], 2) }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @else
    <p>No SQ data available for this simulation and quarter.</p>
  @endif
</body>

</html>
