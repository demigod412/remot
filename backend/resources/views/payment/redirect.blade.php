<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirecting to Payment...</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #0f0f14; color: #e2e8f0; font-family: 'Segoe UI', Arial, sans-serif;
               display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { text-align: center; padding: 48px 32px; }
        .spinner { width: 48px; height: 48px; border: 4px solid rgba(108,71,255,0.2);
                   border-top-color: #6c47ff; border-radius: 50%; animation: spin 0.8s linear infinite;
                   margin: 0 auto 24px; }
        @keyframes spin { to { transform: rotate(360deg); } }
        h2 { font-size: 20px; font-weight: 600; margin-bottom: 8px; }
        p  { color: #94a3b8; font-size: 14px; }
    </style>
</head>
<body>
<div class="card">
    <div class="spinner"></div>
    <h2>Redirecting to payment...</h2>
    <p>Please do not close this window.</p>
</div>

<form id="payment-form" method="{{ $method }}" action="{{ $action }}">
    @foreach ($fields as $name => $value)
        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    @endforeach
</form>

<script>
    document.getElementById('payment-form').submit();
</script>
</body>
</html>
