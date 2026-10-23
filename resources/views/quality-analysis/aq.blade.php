<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Availability Quality</title>
</head>

<body>
  <h2>Availability Quality (AQ) Report - Q{{ $quarter }}</h2>
  
     <table border="1">
    <thead>
        <tr align="center">
            <th>Firm</th>
            <th>Mkt Spend</th>
            <th>Firm Rank (Spend)</th>
            <th>Region × Channels</th>
            <th>Unfilled Quarters</th>
            <th>Fulfilled Quarters</th>
            <th>Availability Quality (AQ)</th>
        </tr>
    </thead>
    <tbody align="center">
        @foreach ($aqResults as $data)
            <tr>
                <td>{{ $data['firm'] }}</td>
                <td>{{ number_format($data['total_spend'], 2) }}</td>
                <td>{{ $data['spend_rank'] }}</td>
                <td>{{ $data['region_channel_count'] }}</td>
                <td>{{ $data['unfilled_orders'] }}</td>
                <td>{{ $data['fulfilled_quarters'] }}</td>
                <td>{{ number_format($data['AQ'] ) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
</table>
</body>

</html>
