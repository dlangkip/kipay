<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kipay Payment Gateway Test</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #2c3e50;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        button {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 15px;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #2980b9;
        }
        .response {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #f9f9f9;
            display: none;
        }
        .payment-frame {
            margin-top: 20px;
            width: 100%;
            height: 600px;
            border: 1px solid #ddd;
            display: none;
        }
        .loading {
            display: none;
            text-align: center;
            margin: 20px 0;
        }
        .loading:after {
            content: "⟳";
            display: inline-block;
            animation: spin 1s linear infinite;
            font-size: 24px;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <h1>Kipay Payment Gateway Test</h1>
    
    <div class="form-group">
        <label for="amount">Amount</label>
        <input type="number" id="amount" name="amount" value="10.00" min="1" step="0.01">
    </div>
    
    <div class="form-group">
        <label for="description">Description</label>
        <input type="text" id="description" name="description" value="Test Payment">
    </div>
    
    <div class="form-group">
        <label for="currency">Currency</label>
        <select id="currency" name="currency">
            <option value="KES">KES (Kenyan Shilling)</option>
            <option value="USD">USD (US Dollar)</option>
            <option value="EUR">EUR (Euro)</option>
            <option value="TZS">TZS (Tanzanian Shilling)</option>
            <option value="UGX">UGX (Ugandan Shilling)</option>
        </select>
    </div>
    
    <div class="form-group">
        <label for="first_name">First Name</label>
        <input type="text" id="first_name" name="first_name" value="John">
    </div>
    
    <div class="form-group">
        <label for="last_name">Last Name</label>
        <input type="text" id="last_name" name="last_name" value="Doe">
    </div>
    
    <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="john.doe@example.com">
    </div>
    
    <div class="form-group">
        <label for="phone">Phone (optional)</label>
        <input type="text" id="phone" name="phone" value="+254700000000">
    </div>
    
    <div class="form-group">
        <button id="pay-button">Process Payment</button>
    </div>
    
    <div id="loading" class="loading"></div>
    
    <div id="response" class="response"></div>
    
    <iframe id="payment-frame" class="payment-frame" frameborder="0"></iframe>
    
    <script>
        document.getElementById('pay-button').addEventListener('click', function() {
            const loading = document.getElementById('loading');
            const response = document.getElementById('response');
            const paymentFrame = document.getElementById('payment-frame');
            
            // Show loading indicator
            loading.style.display = 'block';
            response.style.display = 'none';
            paymentFrame.style.display = 'none';
            
            // Get form values
            const amount = document.getElementById('amount').value;
            const description = document.getElementById('description').value;
            const currency = document.getElementById('currency').value;
            const firstName = document.getElementById('first_name').value;
            const lastName = document.getElementById('last_name').value;
            const email = document.getElementById('email').value;
            const phone = document.getElementById('phone').value;
            
            // Prepare payment data
            const paymentData = {
                amount: parseFloat(amount),
                description: description,
                currency: currency,
                first_name: firstName,
                last_name: lastName,
                email: email,
                phone: phone
            };
            
            // Make API request
            fetch('https://kipay.benfex.net/api/payments', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer MY_SECRET_API_KEY_1',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(paymentData)
            })
            .then(res => res.json())
            .then(data => {
                // Hide loading indicator
                loading.style.display = 'none';
                
                // Display response
                response.style.display = 'block';
                response.innerHTML = `
                    <h3>Payment Created</h3>
                    <p><strong>Reference:</strong> ${data.data.reference}</p>
                    <p><strong>Status:</strong> ${data.data.status}</p>
                    <p><strong>Created:</strong> ${data.data.created_at}</p>
                    <p>Opening payment page...</p>
                `;
                
                // Display payment iframe
                paymentFrame.style.display = 'block';
                paymentFrame.src = data.data.payment_url;
                
                // Set up payment status check
                checkPaymentStatus(data.data.reference);
            })
            .catch(err => {
                // Hide loading indicator
                loading.style.display = 'none';
                
                // Display error
                response.style.display = 'block';
                response.innerHTML = `
                    <h3>Error</h3>
                    <p>${err.message}</p>
                `;
            });
        });
        
        function checkPaymentStatus(reference) {
            // Check status every 5 seconds
            const statusCheck = setInterval(() => {
                fetch(`https://kipay.benfex.net/api/payments/status?reference=${reference}`, {
                    headers: {
                        'Authorization': 'Bearer MY_SECRET_API_KEY_1'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    // Update status in response div
                    const statusUpdate = document.createElement('p');
                    statusUpdate.innerHTML = `<strong>Status Update:</strong> ${data.data.status} (checked at ${data.data.checked_at})`;
                    document.getElementById('response').appendChild(statusUpdate);
                    
                    // If status is completed or failed, stop checking
                    if (data.data.status === 'COMPLETED' || data.data.status === 'FAILED') {
                        clearInterval(statusCheck);
                    }
                })
                .catch(err => {
                    console.error('Error checking status:', err);
                });
            }, 5000);
        }
    </script>
</body>
</html>