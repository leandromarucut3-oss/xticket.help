<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Redirecting…</title>
  <style>
    body{display:flex;align-items:center;justify-content:center;height:100vh;margin:0;background:#111;color:#fff;font-family:Arial,Helvetica,sans-serif}
    .card{text-align:center}
    .dot{width:12px;height:12px;background:#fff;border-radius:50%;display:inline-block;margin:6px;opacity:0;animation:blink 1s infinite}
    .dot:nth-child(2){animation-delay:0.15s}
    .dot:nth-child(3){animation-delay:0.3s}
    @keyframes blink{0%{opacity:0.15}50%{opacity:1}100%{opacity:0.15}}
    .msg{margin-top:12px;color:#ddd}
  </style>
  <meta name="robots" content="noindex,nofollow">
</head>
<body>
  <div class="card">
    <div style="font-size:20px">Preparing your access</div>
    <div style="margin-top:12px">
      <span class="dot"></span>
      <span class="dot"></span>
      <span class="dot"></span>
    </div>
    <div class="msg">You will be redirected in <span id="count">{{ $seconds ?? 3 }}</span>s…</div>
  </div>
  <script>
    (function(){
      var seconds = parseInt('{{ $seconds ?? 3 }}', 10) || 3;
      var el = document.getElementById('count');
      var redirect = '{{ $redirect ?? '/' }}';
      var t = setInterval(function(){ seconds--; el.textContent = seconds; if (seconds<=0){ clearInterval(t); window.location.href = redirect; } }, 1000);
    })();
  </script>
</body>
</html>
