<?php
require_once 'config/db.php';

echo "<h2>Admin/Barber Accounts:</h2>";
$stmt = $pdo->query("SELECT id, name, email, role FROM users WHERE role IN ('admin', 'barber') ORDER BY id");
$admins = $stmt->fetchAll();

if (empty($admins)) {
    echo "<p style='color:red'>No admin/barber accounts found!</p>";
} else {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>";
    foreach ($admins as $admin) {
        echo "<tr>";
        echo "<td>{$admin['id']}</td>";
        echo "<td>{$admin['name']}</td>";
        echo "<td><strong>{$admin['email']}</strong></td>";
        echo "<td>{$admin['role']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h2>Customer Accounts:</h2>";
$stmt = $pdo->query("SELECT id, name, email, role FROM users WHERE role = 'customer' ORDER BY id");
$customers = $stmt->fetchAll();

if (empty($customers)) {
    echo "<p>No customer accounts found.</p>";
} else {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>";
    foreach ($customers as $customer) {
        echo "<tr>";
        echo "<td>{$customer['id']}</td>";
        echo "<td>{$customer['name']}</td>";
        echo "<td>{$customer['email']}</td>";
        echo "<td>{$customer['role']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}
