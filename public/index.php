<?php
/**
 * Kipay Gateway Main Entry Point
 */

// Serve a default page
header('Content-Type: text/html');
echo '<!DOCTYPE html>
<html>
<head>
    <title>Kipay Gateway</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; line-height: 1.6; }
        .container { max-width: 800px; margin: 0 auto; }
        h1 { color: #333; }
        .box { background: #f5f5f5; border-radius: 5px; padding: 20px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Kipay Payment Gateway</h1>
        <div class="box">
            <h2>API Status: Active</h2>
            <p>The Kipay Payment Gateway API is running. For documentation on how to use the API, please contact support.</p>
        </div>
    </div>
</body>
</html>';
