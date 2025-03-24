<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Transaction Status</title>
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
        input, select, button {
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
    </style>
</head>
<body>
    <h1>Update Transaction Status</h1>
    
    <form id="searchForm">
        <div class="form-group">
            <label for="reference">Transaction Reference</label>
            <input type="text" id="reference" required placeholder="PAY-1234567890">
        </div>
        
        <button type="submit">Find Transaction</button>
    </form>
    
    <div id="updateForm" style="display: none; margin-top: 20px;">
        <h2>Update Status</h2>
        <div class="form-group">
            <label for="status">New Status</label>
            <select id="status">
                <option value="PENDING">PENDING</option>
                <option value="COMPLETED">COMPLETED</option>
                <option value="FAILED">FAILED</option>
                <option value="CANCELLED">CANCELLED</option>
            </select>
        </div>
        
        <button type="button" id="updateButton">Update Status</button>
    </div>
    
    <div id="response"></div>
    
    <script>
        document.getElementById('searchForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const reference = document.getElementById('reference').value;
            const responseDiv = document.getElementById('response');
            responseDiv.style.display = 'block';
            responseDiv.textContent = 'Searching...';
            
            // Find transaction
            fetch(`/api/payments/status?reference=${reference}`, {
                headers: {
                    'Authorization': 'Bearer MY_SECRET_API_KEY_1'
                }
            })
            .then(response => response.json())
            .then(data => {
                responseDiv.textContent = JSON.stringify(data, null, 2);
                
                if (data.success) {
                    // Show update form
                    document.getElementById('updateForm').style.display = 'block';
                }
            })
            .catch(error => {
                responseDiv.textContent = 'Error: ' + error.message;
            });
        });
        
        document.getElementById('updateButton').addEventListener('click', function() {
            const reference = document.getElementById('reference').value;
            const status = document.getElementById('status').value;
            const responseDiv = document.getElementById('response');
            
            responseDiv.textContent += '\n\nUpdating status...';
            
            // Create a simple PHP script to update transaction
            const updateScript = `<?php
                // Load configuration
                require_once __DIR__ . '/../../api/config.php';
                
                try {
                    $reference = '${reference}';
                    $status = '${status}';
                    
                    // Connect to database
                    $db = new PDO(
                        "mysql:host={$CONFIG['db_host']};dbname={$CONFIG['db_name']}",
                        $CONFIG['db_user'],
                        $CONFIG['db_pass'],
                        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                    );
                    
                    // Update transaction
                    $stmt = $db->prepare("
                        UPDATE transactions 
                        SET status = :status, updated_at = NOW()
                        WHERE reference = :reference
                    ");
                    
                    $stmt->execute([
                        ':status' => $status,
                        ':reference' => $reference
                    ]);
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Transaction updated',
                        'rows_affected' => $stmt->rowCount()
                    ]);
                } catch (Exception $e) {
                    echo json_encode([
                        'success' => false,
                        'error' => $e->getMessage()
                    ]);
                }
            ?>`;
            
            // Save the script to a temp file
            fetch('/api/docs/temp-update.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `script=${encodeURIComponent(updateScript)}`
            })
            .then(response => response.text())
            .then(data => {
                // Execute the update script
                fetch('/api/docs/temp-update.php')
                .then(response => response.json())
                .then(data => {
                    responseDiv.textContent += '\n\nUpdate response: ' + JSON.stringify(data, null, 2);
                    
                    if (data.success) {
                        // Refresh transaction status
                        setTimeout(() => {
                            fetch(`/api/payments/status?reference=${reference}`, {
                                headers: {
                                    'Authorization': 'Bearer MY_SECRET_API_KEY_1'
                                }
                            })
                            .then(response => response.json())
                            .then(data => {
                                responseDiv.textContent += '\n\nNew status: ' + JSON.stringify(data, null, 2);
                            });
                        }, 1000);
                    }
                })
                .catch(error => {
                    responseDiv.textContent += '\n\nUpdate error: ' + error.message;
                });
            })
            .catch(error => {
                responseDiv.textContent += '\n\nError creating update script: ' + error.message;
            });
        });
    </script>
</body>
</html>
