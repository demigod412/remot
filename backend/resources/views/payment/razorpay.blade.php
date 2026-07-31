<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay with Razorpay</title>
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
    <h2>Opening Razorpay...</h2>
    <p>Please do not close this window.</p>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    var options = {
        key:          "{{ $rzp['key'] }}",
        amount:       {{ $rzp['amount'] }},
        currency:     "{{ $rzp['currency'] }}",
        name:         "{{ $rzp['name'] }}",
        description:  "{{ $rzp['description'] }}",
        order_id:     "{{ $rzp['order_id'] }}",
        callback_url: "{{ $rzp['callback_url'] }}",
        prefill:      {},
        theme:        { color: "#6c47ff" },
    };

    var rzp = new Razorpay(options);
    rzp.on('payment.failed', function(response) {
        window.location.href = "{{ $rzp['cancel_url'] }}";
    });
    rzp.open();
</script>
</body>
</html>
