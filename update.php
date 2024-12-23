<?php
// Database connection configuration
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'invoice';
$table = 'data_table';

try {
    $dsn = "mysql:host=$host;dbname=$dbname";
    $conn = new PDO($dsn, $username, $password);
} 

catch (PDOException $e) {
    die("<h3>Error: " . $e->getMessage() . "</h3>");
}

// Check if the ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("<h3>Error: No ID provided.</h3>");
}

$id = $_GET['id'];

// Fetch the existing record
try {
    $sql = "SELECT * FROM $table WHERE Id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $id]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$invoice) {
        die("<h3>Error: Record not found.</h3>");
    }
} catch (PDOException $e) {
    die("<h3>Error: " . $e->getMessage() . "</h3>");
}

// Handle form submission for updating the record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_invoice'])) {
    $client_name = trim($_POST['client_name'] ?? '');
    $amount = trim($_POST['amount'] ?? '');
    $due_date = trim($_POST['due_date'] ?? '');
    $status = trim($_POST['status'] ?? '');

    if (empty($client_name) || empty($amount) || empty($due_date) || empty($status)) {
        echo "<script>alert('Please fill in all fields.');</script>";
    } else {
        try {
            $sql = "UPDATE $table SET client_name = :client_name, amount = :amount, due_date = :due_date, status = :status WHERE Id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':client_name' => $client_name,
                ':amount' => $amount,
                ':due_date' => $due_date,
                ':status' => $status,
                ':id' => $id
            ]);

            echo "<script>alert('Invoice updated successfully.'); window.location.href = 'firstpage.php';</script>";
        } catch (PDOException $e) {
            echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Epay Update</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: lightblue;
        }

        .container {
            width: 80%;
            max-width: 400px;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        input, select {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        button {
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }

        .go-back-button {
            background-color: #6c757d;
        }

        .go-back-button:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Update Data</h2>
        <form action="" method="POST">
            <label for="client_name">Client Name:</label>
            <input type="text" id="client_name" name="client_name" value="<?= htmlspecialchars($invoice['client_name'] ?? '') ?>" required>

            <label for="amount">Amount:</label>
            <input type="number" step="0.01" id="amount" name="amount" value="<?= htmlspecialchars($invoice['amount'] ?? '') ?>" required>

            <label for="due_date">Due Date:</label>
            <input type="date" id="due_date" name="due_date" value="<?= htmlspecialchars($invoice['due_date'] ?? '') ?>" required>

            <label for="status">Status:</label>
            <select id="status" name="status" required>
                <option value="Paid" <?= $invoice['status'] === 'Paid' ? 'selected' : '' ?>>Paid</option>
                <option value="Unpaid" <?= $invoice['status'] === 'Unpaid' ? 'selected' : '' ?>>Unpaid</option>
            </select>

            <button type="submit" name="update_invoice">Update Data</button>
        </form>

        <!-- Go Back Button -->
        <form action="firstpage.php" method="GET" style="margin-top: 15px;">
            <button class="go-back-button" type="submit">Go Back to First Page</button>
        </form>
    </div>
</body>
</html>
