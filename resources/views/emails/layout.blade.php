<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>@yield('title')</title>
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
  <table width="100%">
    <tr>
      <td align="center"><img src="{{ config('app.host_url') . '/images/email-header.svg' }}" alt=""></td>
    </tr>
    <tr>
      <td> @yield('emailBody')</td>
    </tr>
    <tr>
      <td>
        <strong>Support</strong>
        <p><strong>Emergency Support:</strong> {{ config('app.support_number') }}<br>
          <strong>Contact:</strong> {{ config('app.contact_number') }}
        </p>
      </td>
    </tr>
    <tr>
      <td>
        <p>Kind regards,<br /> The FLEXEE Team</p>
      </td>
    </tr>
  </table>
</body>

</html>
