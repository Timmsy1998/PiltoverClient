<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <title>{{ $title ?? 'PiltoverClient API Docs' }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <style>
    body { margin:0; padding:0; font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; }
    header { position: sticky; top:0; background:#111; color:#fff; padding:12px 16px; z-index:10; }
    header .brand { font-weight: 700; letter-spacing:.3px; }
    main { height: calc(100vh - 48px); }
    redoc { height: 100%; }
    a { color: #93c5fd; text-decoration: none; }
  </style>
</head>
<body>
<header>
  <div class="brand">🧠 PiltoverClient — API Docs</div>
</header>
<main>
  <redoc spec-url="{{ $specUrl }}"></redoc>
</main>
<script src="https://cdn.redoc.ly/redoc/latest/bundles/redoc.standalone.js" defer></script>
</body>
</html>
