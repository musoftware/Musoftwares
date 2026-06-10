<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MuSync - Authentication Success</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #0f172a;
            color: #f8fafc;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            text-align: center;
        }
        .container {
            background: rgba(30, 41, 59, 0.7);
            padding: 40px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
        }
        h1 { color: #3b82f6; }
        p { color: #94a3b8; }
        .spinner {
            margin: 20px auto;
            width: 40px;
            height: 40px;
            border: 4px solid rgba(255,255,255,0.1);
            border-top: 4px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="container">
        <h1>Authentication Successful</h1>
        <p>Connecting to MuSync extension...</p>
        <div class="spinner"></div>
        <p id="status">Please wait while we securely transfer your session.</p>
    </div>

    <!-- Hidden token for content script to read -->
    <div id="musync-auth-data" style="display:none;" data-token="{{ $token }}"></div>

    <script>
        // The extension's content.js will read the token and redirect/close this page.
        // Fallback if extension is not installed/running
        setTimeout(() => {
            document.getElementById('status').innerText = "You can now close this tab and return to the extension.";
        }, 3000);
    </script>
</body>
</html>
