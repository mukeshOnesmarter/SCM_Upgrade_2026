<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <title>Password Reset</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f9fafb;
      margin: 0;
      padding: 0;
    }

    .container {
      max-width: 600px;
      margin: 40px auto;
      background: #ffffff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    h2 {
      color: #333;
    }

    p {
      color: #555;
      font-size: 15px;
      line-height: 1.6;
    }

    .button {
      display: inline-block;
      margin-top: 20px;
      padding: 12px 25px;
      background: #ed1c24;
      color: #ffffff;
      text-decoration: none;
      border-radius: 6px;
      font-weight: bold;
    }

    .footer {
      margin-top: 30px;
      font-size: 13px;
      color: #888;
      text-align: center;
    }
  </style>
</head>

<body>
  <div class="container">
    <h2>Hello {{ $name }},</h2>
    <p>You requested a password reset. Click the button below to reset your password:</p>
    <p>
      <a href="{{ $url }}" class="button" style="color: #ffffff">Reset Password</a>
    </p>
    <p>If you did not request this, you can safely ignore this email.</p>
    <div class="footer">
      &copy; {{ date('Y') }} FLEXEE SCM Simulation Platform
    </div>
  </div>
</body>

</html>
