<!DOCTYPE html>
<html>
<head>
    <title>OAuth Callback</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f5f5f5;
        }
        .container {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success {
            color: #10b981;
        }
        .error {
            color: #ef4444;
        }
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3b82f6;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        @if(session('error'))
            <div class="error">
                <h3>❌ Connection Failed</h3>
                <p>{{ session('error') }}</p>
                <p>This window will close automatically.</p>
            </div>
        @else
            <div class="success">
                <div class="spinner"></div>
                <h3>✅ Account Connected Successfully!</h3>
                <p>This window will close automatically.</p>
            </div>
        @endif
    </div>
    
    <script>
        // Close the popup and notify the parent window
        setTimeout(function() {
            if (window.opener) {
                // This will trigger the popup closure detection in the parent
                window.close();
            } else {
                // Fallback if not in popup (direct access)
                window.location.href = '{{ route("admin.social-accounts.index") }}';
            }
        }, 2000); // Wait 2 seconds to show the message
    </script>
</body>
</html>
