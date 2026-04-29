<?php
/**
 * Database Migration Script
 * 
 * Run this ONCE to add the reviews table and update appointments table.
 * Delete this file after running!
 */

require_once 'config/db.php';

// Simple password protection (change this!)
$MIGRATION_PASSWORD = 'von2025';

// Check password
if (!isset($_GET['key']) || $_GET['key'] !== $MIGRATION_PASSWORD) {
    die('Access denied. Add ?key=von2025 to the URL to run migration.');
}

$migrationResults = [];
$errors = [];

try {
    // Step 1: Add haircut_description column if not exists
    try {
        $pdo->exec("ALTER TABLE appointments ADD COLUMN haircut_description TEXT");
        $migrationResults[] = "✅ Added haircut_description column to appointments";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            $migrationResults[] = "ℹ️ haircut_description column already exists";
        } else {
            throw $e;
        }
    }

    // Step 2: Add location column if not exists
    try {
        $pdo->exec("ALTER TABLE appointments ADD COLUMN location VARCHAR(255)");
        $migrationResults[] = "✅ Added location column to appointments";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            $migrationResults[] = "ℹ️ location column already exists";
        } else {
            throw $e;
        }
    }

    // Step 3: Update status enum to include 'accepted'
    try {
        $pdo->exec("ALTER TABLE appointments MODIFY COLUMN status ENUM('pending','accepted','completed','cancelled') DEFAULT 'pending'");
        $migrationResults[] = "✅ Updated status enum to include 'accepted'";
    } catch (PDOException $e) {
        $migrationResults[] = "ℹ️ Status enum may already be updated";
    }

    // Step 4: Create reviews table
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS reviews (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                appointment_id INT NOT NULL,
                rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
                comment TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE
            ) ENGINE=InnoDB
        ");
        $migrationResults[] = "✅ Created reviews table";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            $migrationResults[] = "ℹ️ Reviews table already exists";
        } else {
            throw $e;
        }
    }

    // Step 5: Verify tables exist
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $migrationResults[] = "✅ Database tables: " . implode(', ', $tables);

    // Check if reviews table exists
    if (in_array('reviews', $tables)) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM reviews");
        $count = $stmt->fetchColumn();
        $migrationResults[] = "✅ Reviews table has $count reviews";
    }

} catch (PDOException $e) {
    $errors[] = "❌ Database error: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration - V.O.N Barbershop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #1a1a2e;
            color: #F5F0E8;
            font-family: 'Inter', sans-serif;
            padding: 50px 20px;
        }
        .container {
            max-width: 800px;
        }
        .card {
            background: #2d2d44;
            border: 2px solid #C5A059;
        }
        h1 {
            color: #C5A059;
            font-family: 'Playfair Display', serif;
        }
        .success {
            color: #28a745;
        }
        .info {
            color: #17a2b8;
        }
        .error {
            color: #dc3545;
        }
        .result-item {
            padding: 10px;
            margin: 5px 0;
            background: rgba(255,255,255,0.05);
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card p-4">
            <h1 class="mb-4">Database Migration</h1>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <h5>Migration Failed!</h5>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li class="error"><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php else: ?>
                <div class="alert alert-success">
                    <h5>✅ Migration Completed Successfully!</h5>
                </div>
                
                <h5 class="mt-4 mb-3" style="color: #C5A059;">Migration Results:</h5>
                <div>
                    <?php foreach ($migrationResults as $result): ?>
                        <div class="result-item">
                            <?php echo htmlspecialchars($result); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="alert alert-warning mt-4">
                    <strong>⚠️ Important:</strong> Delete this file (run_migration.php) after migration is complete for security!
                </div>
            <?php endif; ?>
            
            <hr style="border-color: #C5A059;">
            <p class="mb-0" style="color: #8A8A9A;">
                V.O.N Barbershop Database Migration Tool
            </p>
        </div>
    </div>
</body>
</html>
