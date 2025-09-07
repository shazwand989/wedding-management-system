<?php
require_once 'includes/config.php';

echo "Starting database update...\n";

try {
    // Create wedding_tasks table
    $sql = "CREATE TABLE IF NOT EXISTS wedding_tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        task_title VARCHAR(255) NOT NULL,
        description TEXT,
        due_date DATE NOT NULL,
        priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
        status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    $pdo->exec($sql);
    echo "✓ Created wedding_tasks table\n";

    // Create wedding_budgets table
    $sql = "CREATE TABLE IF NOT EXISTS wedding_budgets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        total_budget DECIMAL(12,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    $pdo->exec($sql);
    echo "✓ Created wedding_budgets table\n";

    // Create budget_expenses table
    $sql = "CREATE TABLE IF NOT EXISTS budget_expenses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        category ENUM('venue', 'photography', 'attire', 'flowers', 'music', 'transportation', 'stationery', 'rings', 'miscellaneous') NOT NULL,
        description VARCHAR(255) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        vendor_name VARCHAR(255),
        expense_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    $pdo->exec($sql);
    echo "✓ Created budget_expenses table\n";

    // Add missing columns to users table
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN date_of_birth DATE NULL");
        echo "✓ Added date_of_birth column to users table\n";
    } catch (Exception $e) {
        echo "- date_of_birth column already exists\n";
    }

    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN gender ENUM('male', 'female', 'other') NULL");
        echo "✓ Added gender column to users table\n";
    } catch (Exception $e) {
        echo "- gender column already exists\n";
    }

    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN address TEXT NULL");
        echo "✓ Added address column to users table\n";
    } catch (Exception $e) {
        echo "- address column already exists\n";
    }

    // Add missing columns to vendors table
    try {
        $pdo->exec("ALTER TABLE vendors ADD COLUMN location VARCHAR(255) NULL");
        echo "✓ Added location column to vendors table\n";
    } catch (Exception $e) {
        echo "- location column already exists\n";
    }

    try {
        $pdo->exec("ALTER TABLE vendors ADD COLUMN specialties TEXT NULL");
        echo "✓ Added specialties column to vendors table\n";
    } catch (Exception $e) {
        echo "- specialties column already exists\n";
    }

    // Check if sample data exists before inserting
    $stmt = $pdo->query("SELECT COUNT(*) FROM wedding_tasks WHERE customer_id = 1");
    $taskCount = $stmt->fetchColumn();

    if ($taskCount == 0) {
        // Insert sample timeline tasks (only if no data exists)
        $stmt = $pdo->prepare("INSERT INTO wedding_tasks (customer_id, task_title, description, due_date, priority, status) VALUES (?, ?, ?, ?, ?, ?)");
        
        $sampleTasks = [
            [1, 'Book venue', 'Research and book the perfect wedding venue', '2024-06-01', 'high', 'pending'],
            [1, 'Choose wedding dress', 'Visit bridal shops and select wedding dress', '2024-07-15', 'high', 'pending'],
            [1, 'Send invitations', 'Design and send wedding invitations to guests', '2024-08-01', 'medium', 'pending'],
            [1, 'Finalize menu', 'Meet with caterer to finalize wedding menu', '2024-08-15', 'medium', 'pending'],
            [1, 'Wedding rehearsal', 'Conduct wedding ceremony rehearsal', '2024-09-20', 'high', 'pending']
        ];

        foreach ($sampleTasks as $task) {
            $stmt->execute($task);
        }
        echo "✓ Inserted sample timeline tasks\n";
    } else {
        echo "- Sample timeline tasks already exist\n";
    }

    // Check if budget data exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM wedding_budgets WHERE customer_id = 1");
    $budgetCount = $stmt->fetchColumn();

    if ($budgetCount == 0) {
        // Insert sample budget
        $stmt = $pdo->prepare("INSERT INTO wedding_budgets (customer_id, total_budget) VALUES (?, ?)");
        $stmt->execute([1, 25000.00]);
        echo "✓ Inserted sample budget\n";

        // Insert sample expenses
        $stmt = $pdo->prepare("INSERT INTO budget_expenses (customer_id, category, description, amount, vendor_name, expense_date) VALUES (?, ?, ?, ?, ?, ?)");
        
        $sampleExpenses = [
            [1, 'venue', 'Wedding venue booking deposit', 5000.00, 'Grand Ballroom', '2024-02-15'],
            [1, 'photography', 'Wedding photography package', 3000.00, 'John Photography Studio', '2024-03-01'],
            [1, 'attire', 'Wedding dress and accessories', 2500.00, 'Bridal Boutique', '2024-03-15'],
            [1, 'flowers', 'Bridal bouquet and decorations', 1500.00, 'Beautiful Decorations', '2024-04-01']
        ];

        foreach ($sampleExpenses as $expense) {
            $stmt->execute($expense);
        }
        echo "✓ Inserted sample budget expenses\n";
    } else {
        echo "- Sample budget data already exists\n";
    }

    echo "\n✅ Database update completed successfully!\n";
    echo "All customer portal features are now fully functional.\n";

} catch (Exception $e) {
    echo "❌ Error updating database: " . $e->getMessage() . "\n";
}
?>
