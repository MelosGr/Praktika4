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
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("<h3>Error: Unable to connect to database.</h3>");
}

// Handle form submission for adding a new invoice
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_invoice'])) {
    $client_name = trim($_POST['client_name'] ?? '');

    if (empty($client_name)) {
        echo "<script>alert('Please provide a client name');</script>";
    } else {
        try {
            // Check if the client name already exists
            $sql = "SELECT COUNT(*) FROM $table WHERE client_name = :client_name";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':client_name' => $client_name]);
            if ($stmt->fetchColumn() > 0) {
                echo "<script>alert('Client name already exists.');</script>";
            } else {
                // Insert new invoice
                $amount = number_format(rand(100, 10000) / 100, 2);
                $due_date = date('Y-m-d', strtotime('+' . rand(1, 365) . ' days'));
                $status = rand(0, 1) ? 'Paid' : 'Unpaid';

                $sql = "INSERT INTO $table (client_name, amount, due_date, status) VALUES (:client_name, :amount, :due_date, :status)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':client_name' => $client_name,
                    ':amount' => $amount,
                    ':due_date' => $due_date,
                    ':status' => $status
                ]);
                echo "<script>alert('Invoice added successfully.'); window.location.href = 'firstpage.php';</script>";
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            echo "<script>alert('An error occurred. Please try again later.');</script>";
        }
    }
}

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_invoice'])) {
    $id = intval($_POST['id'] ?? 0);
    if ($id > 0) {
        try {
            $conn->beginTransaction();
            
            $sql = "DELETE FROM $table WHERE Id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':id' => $id]);

            // Renumber IDs
            $conn->exec("SET @row_number = 0;");
            $conn->exec("UPDATE $table SET Id = (@row_number:=@row_number + 1) ORDER BY Id;");
            $conn->exec("ALTER TABLE $table AUTO_INCREMENT = 1;");

            $conn->commit();
            echo "<script>alert('Invoice deleted successfully.'); window.location.href = 'firstpage.php';</script>";
        } catch (PDOException $e) {
            if ($conn->inTransaction()) { // Check if a transaction is active
                $conn->rollBack();
            }
            error_log($e->getMessage());
            echo "<script>alert('Invoice deleted successfully.');</script>";
        }
    } else {
        echo "<script>alert('Invalid invoice ID.');</script>";
    }
}

// Fetch invoices for display
$invoices = [];
try {
    $sql = "SELECT * FROM $table ORDER BY Id DESC";
    $stmt = $conn->query($sql);
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
}
?>

<!DOCTYPE html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Epay Dashboard</title>
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
        width: 95%;
        max-width: 800px;
        margin: auto;
        text-align: center;
        background-color: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 4px 14px 18px rgba(0, 0, 0, 0.1);
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    th, td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }

    th {
        background-color: #f4f4f4;
    }

    form {
        margin-top: 20px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    input, select {
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    button {
        padding: 10px;
        background-color: #28a745;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    button:hover {
        background-color: #218838;
    }

    .actions {
        display: flex;
        gap: 10px;
        justify-content: center;
    }

    .delete-button {
        background-color: #dc3545;
    }

    .delete-button:hover {
        background-color: #c82333;
    }

    /*--------------------------------------------------------*/

    @media (max-width: 720px) {
        .container {
            padding: 10px;
            width: 95%;
            box-shadow: none;
            border-radius: 10px;
            box-shadow: 4px 14px 18px rgba(0, 0, 0, 0.1);
        }

        table, th, td {
            font-size: 12px;
        }

        th, td {
            padding: 6px;
        }

        .actions {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        button {
            font-size: 14px;
            padding: 8px;
        }

        form {
            gap: 8px;
        }
    }

    @media (max-width: 420px) {
        .container {
            padding: 5px;
            box-shadow: 4px 14px 18px rgba(0, 0, 0, 0.1);
        }

        h1 {
            font-size: 18px;
        }

        table, th, td {
            font-size: 10px;
        }

        th, td {
            padding: 2px;
        }

        form {
            gap: 6px;
        }

        input, select {
            font-size: 12px;
            padding: 5px;
        }

        button { 
            font-size: 12px;  
            padding: 6px;
        }

        .actions {
            flex-direction: column;
            gap: 4px;
        }
    }
    </style>
</head>
<body>
    <div class="container">
        <h1>Invoice Dashboard</h1>

        <form action="" method="POST">
            <input type="text" name="client_name" placeholder="Client Name" required>
            <button type="submit" name="add_invoice">Add Data</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Client Name</th>
                    <th>Amount</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($invoices)): ?>
                    <?php foreach ($invoices as $invoice): ?>
                        <tr>
                            <td><?= htmlspecialchars($invoice['Id'] ?? '') ?></td>
                            <td><?= htmlspecialchars($invoice['client_name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($invoice['amount'] ?? '0.00') ?></td>
                            <td><?= htmlspecialchars($invoice['due_date'] ?? '') ?></td>
                            <td><?= htmlspecialchars($invoice['status'] ?? 'Unknown') ?></td>
                            <td>
                                <div class="actions">
                                    <form action="" method="POST">
                                        <input type="hidden" name="id" value="<?= htmlspecialchars($invoice['Id']) ?>">
                                        <button class="delete-button" type="submit" name="delete_invoice">Delete</button>
                                    </form>
                                    <form action="update.php" method="GET">
                                        <input type="hidden" name="id" value="<?= htmlspecialchars($invoice['Id']) ?>">
                                        <button type="submit">Update</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">No Data found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>