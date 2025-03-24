<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>M-Pesa STK Push Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, button {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover {
            background-color: #45a049;
        }
        #response {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #f9f9f9;
            white-space: pre-wrap;
            display: none;
        }
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <h1>M-Pesa STK Push Test</h1>
    
    <form id="stkForm">
        <div class="form-group">
            <label for="phone">Phone Number (2547XXXXXXXX)</label>
            <input type="text" id="phone" required placeholder="2547XXXXXXXX">
        </div>
        
        <div class="form-group">
            <label for="amount">Amount (KES)</label>
            <input type="number" id="amount" required value="1" min="1">
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <input type="text" id="description" value="Test STK Push">
        </div>
        
        <button type="submit">Send STK Push</button>
    </form>
    
    <div id="queryForm" class="hidden">
        <h2>Check Transaction Status</h2>
        <div class="form-group">
            <label for="checkoutRequestId">Checkout Request ID</label>
            <input type="text" id="checkoutRequestId" required>
        </div>
        <button type="button" id="queryButton">Query Status</button>
    </div>
    
    <div id="response"></div>
    
    <script>
        document.getElementById('stkForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const phone = document.getElementById('phone').value;
            const amount = document.getElementById('amount').value;
            const description = document.getElementById('description').value;
            
            const responseDiv = document.getElementById('response');
            responseDiv.style.display = 'block';
            responseDiv.textContent = 'Processing...';
            
            // Call the STK Push API with .php extension
            fetch('/api/stk-push.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer MY_SECRET_API_KEY_1'
                },
                body: JSON.stringify({
                    phone_number: phone,
                    amount: amount,
                    description: description
                })
            })
            .then(response => response.json())
            .then(data => {
                responseDiv.textContent = JSON.stringify(data, null, 2);
                
                if (data.success) {
                    // Show query form
                    document.getElementById('queryForm').classList.remove('hidden');
                    
                    // Set checkout request ID
                    document.getElementById('checkoutRequestId').value = data.data.checkout_request_id;
                    
                    // If successful, poll for status updates
                    checkStatus(data.data.reference);
                }
            })
            .catch(error => {
                responseDiv.textContent = 'Error: ' + error.message;
            });
        });
        
        document.getElementById('queryButton').addEventListener('click', function() {
            const checkoutRequestId = document.getElementById('checkoutRequestId').value;
            const responseDiv = document.getElementById('response');
            
            responseDiv.textContent += '\n\nQuerying status...';
            
            // Call the query API with .php extension
            fetch(`/api/stk-query.php?checkout_request_id=${checkoutRequestId}`, {
                headers: {
                    'Authorization': 'Bearer MY_SECRET_API_KEY_1'
                }
            })
            .then(response => response.json())
            .then(data => {
                responseDiv.textContent += '\n\nQuery response: ' + JSON.stringify(data, null, 2);
            })
            .catch(error => {
                responseDiv.textContent += '\n\nQuery error: ' + error.message;
            });
        });
        
        function checkStatus(reference) {
            // Check status every 5 seconds
            const interval = setInterval(() => {
                fetch(`/api/payments/status?reference=${reference}`, {
                    headers: {
                        'Authorization': 'Bearer MY_SECRET_API_KEY_1'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    const responseDiv = document.getElementById('response');
                    responseDiv.textContent += '\n\nStatus Update: ' + data.data.status;
                    
                    if (data.data.status === 'COMPLETED' || data.data.status === 'FAILED') {
                        clearInterval(interval);
                    }
                })
                .catch(error => {
                    console.error('Error checking status:', error);
                });
            }, 5000);
        }
    </script>
</body>
</html>
