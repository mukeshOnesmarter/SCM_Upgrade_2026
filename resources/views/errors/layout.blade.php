<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · FLEXEE Supply Chain Management</title>
    <style>
        body { margin: 0; font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background: #f6f7f9; color: #1f2933; }
        main { max-width: 32rem; margin: 12vh auto; padding: 2rem; background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        h1 { font-size: 1.4rem; margin: 0 0 .75rem; }
        p { line-height: 1.5; margin: 0 0 1.25rem; }
        a.button { display: inline-block; padding: .6rem 1rem; background: #1d4ed8; color: #fff; border-radius: 6px; text-decoration: none; }
        .code { color: #6b7280; font-size: .85rem; margin-top: 1.5rem; }
    </style>
</head>
<body>
<main>
    <h1>@yield('heading')</h1>
    <p>@yield('message')</p>
    <a class="button" href="{{ \App\Support\HomeByRole::url() }}">{{ \App\Support\HomeByRole::route() ? 'Go to my home page' : 'Go to the front page' }}</a>
    <p class="code">Error @yield('code')</p>
</main>
</body>
</html>
