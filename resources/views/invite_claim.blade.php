<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Accept Invite</title>
  <style>body{font-family:Arial,Helvetica,sans-serif;padding:24px;background:#f7f7f8;color:#111}</style>
</head>
<body>
  <h1>Accept Invite</h1>
  @if($username)
    <p>This invite is for user: <strong>{{ $username }}</strong></p>
  @else
    <p>This invite can be claimed by any user. Please enter your username to continue.</p>
  @endif

  <form method="POST" action="{{ url('/invite/claim') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div style="margin:12px 0">
      <label>Username</label><br>
      <input name="username" required style="padding:8px;width:320px" value="{{ $username ?? '' }}">
    </div>
    <button type="submit" style="padding:8px 12px">Accept Invite</button>
  </form>
</body>
</html>
