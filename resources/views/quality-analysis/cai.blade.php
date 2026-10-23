<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Customer Alignment Index</title>
</head>

<body>
  <h2 class="mb-4">Customer Alignment Index (CAI)</h2>
  
  <table border="1">
    <thead class="thead-dark">
      <tr>
        <th>Group ID</th>
        <th>Raw CAI</th>
        <th>Standardized CAI</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($rawCais as $groupId => $rawCai)
        <tr>
          <td>{{ $groupId }}</td>
          <td>{{ number_format($rawCai, 4) }}</td>
          <td>{{ number_format($standardizedCais[$groupId], 4) }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
</body>

</html>
