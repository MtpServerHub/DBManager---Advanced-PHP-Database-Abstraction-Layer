<?php
// TEST DBManager - IT wont work. The database is example
require_once '../autoload.php';
use DBManager\src\DBManager;

class BankSystem {
    private string $dbId = 'bank_db';

    public function __construct() {
        // Initialize DBManager
        DBManager::prepare();

        // Configure database connection
        DBManager::define(
            'localhost',
            $this->dbId,
            'bank_system',
            'bank_user',
            'secure_password123',
            'utf8mb4'
        );

        // Connect to database
        if (!DBManager::connect($this->dbId)) {
            throw new Exception("Failed to connect to database");
        }

        // Create tables if they don't exist
        $this->initializeDatabase();
    }

    private function initializeDatabase() {
        $tables = [
            'customers' => "CREATE TABLE IF NOT EXISTS customers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                balance DECIMAL(10,2) DEFAULT 0.00,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",

            'transactions' => "CREATE TABLE IF NOT EXISTS transactions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                customer_id INT NOT NULL,
                type ENUM('deposit', 'withdrawal', 'transfer') NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                description VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (customer_id) REFERENCES customers(id)
            )",

            'transfers' => "CREATE TABLE IF NOT EXISTS transfers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                from_customer INT NOT NULL,
                to_customer INT NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                description VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (from_customer) REFERENCES customers(id),
                FOREIGN KEY (to_customer) REFERENCES customers(id)
            )"
        ];

        foreach ($tables as $table => $sql) {
            if (!DBManager::queryDirect($this->dbId, $sql)) {
                throw new Exception("Failed to create table: $table");
            }
        }
    }

    public function createCustomer(string $name, string $email, float $initialDeposit = 0): int {
        DBManager::beingTransaction($this->dbId);

        try {
            // Insert customer
            $op1 = 101;
            DBManager::newOperation($this->dbId, $op1, false, 'insert_customer');
            DBManager::query('insert_customer',
                "INSERT INTO customers (name, email, balance) VALUES (:name, :email, :balance)");

            DBManager::bindValue($op1, ':name', $name);
            DBManager::bindValue($op1, ':email', $email);
            DBManager::bindValue($op1, ':balance', $initialDeposit);

            if (!DBManager::execute($op1)) {
                throw new Exception("Failed to create customer");
            }

            // Get the new customer ID
            $customerId = DBManager::fetch(DBManager::newOperation($this->dbId, 102, false, 'last_insert_id'))['id'];

            // Record initial deposit if any
            if ($initialDeposit > 0) {
                $this->recordTransaction($customerId, 'deposit', $initialDeposit, 'Initial deposit');
            }

            DBManager::commit($this->dbId);
            return $customerId;
        } catch (Exception $e) {
            DBManager::rollBack($this->dbId);
            throw $e;
        }
    }

    public function deposit(int $customerId, float $amount, string $description = ''): bool {
        if ($amount <= 0) {
            throw new InvalidArgumentException("Deposit amount must be positive");
        }

        DBManager::beingTransaction($this->dbId);

        try {
            // Update balance
            $op1 = 201;
            DBManager::newOperation($this->dbId, $op1, false, 'update_balance');
            DBManager::query('update_balance',
                "UPDATE customers SET balance = balance + :amount WHERE id = :id");

            DBManager::bindValue($op1, ':amount', $amount);
            DBManager::bindValue($op1, ':id', $customerId);

            if (!DBManager::execute($op1)) {
                throw new Exception("Failed to update balance");
            }

            // Record transaction
            $this->recordTransaction($customerId, 'deposit', $amount, $description);

            DBManager::commit($this->dbId);
            return true;
        } catch (Exception $e) {
            DBManager::rollBack($this->dbId);
            throw $e;
        }
    }

    public function withdraw(int $customerId, float $amount, string $description = ''): bool {
        if ($amount <= 0) {
            throw new InvalidArgumentException("Withdrawal amount must be positive");
        }

        DBManager::beingTransaction($this->dbId);

        try {
            // Check sufficient balance
            $balance = $this->getBalance($customerId);
            if ($balance < $amount) {
                throw new Exception("Insufficient funds");
            }

            // Update balance
            $op1 = 301;
            DBManager::newOperation($this->dbId, $op1, false, 'update_balance');
            DBManager::query('update_balance',
                "UPDATE customers SET balance = balance - :amount WHERE id = :id");

            DBManager::bindValue($op1, ':amount', $amount);
            DBManager::bindValue($op1, ':id', $customerId);

            if (!DBManager::execute($op1)) {
                throw new Exception("Failed to update balance");
            }

            // Record transaction
            $this->recordTransaction($customerId, 'withdrawal', $amount, $description);

            DBManager::commit($this->dbId);
            return true;
        } catch (Exception $e) {
            DBManager::rollBack($this->dbId);
            throw $e;
        }
    }

    public function transfer(int $fromCustomerId, int $toCustomerId, float $amount, string $description = ''): bool {
        if ($amount <= 0) {
            throw new InvalidArgumentException("Transfer amount must be positive");
        }

        DBManager::beingTransaction($this->dbId);

        try {
            // Check sufficient balance
            $balance = $this->getBalance($fromCustomerId);
            if ($balance < $amount) {
                throw new Exception("Insufficient funds for transfer");
            }

            // Withdraw from sender
            $op1 = 401;
            DBManager::newOperation($this->dbId, $op1, false, 'update_balance_from');
            DBManager::query('update_balance_from',
                "UPDATE customers SET balance = balance - :amount WHERE id = :id");

            DBManager::bindValue($op1, ':amount', $amount);
            DBManager::bindValue($op1, ':id', $fromCustomerId);

            if (!DBManager::execute($op1)) {
                throw new Exception("Failed to withdraw from sender");
            }

            // Deposit to recipient
            $op2 = 402;
            DBManager::newOperation($this->dbId, $op2, false, 'update_balance_to');
            DBManager::query('update_balance_to',
                "UPDATE customers SET balance = balance + :amount WHERE id = :id");

            DBManager::bindValue($op2, ':amount', $amount);
            DBManager::bindValue($op2, ':id', $toCustomerId);

            if (!DBManager::execute($op2)) {
                throw new Exception("Failed to deposit to recipient");
            }

            // Record transactions
            $this->recordTransaction($fromCustomerId, 'transfer', $amount, "Transfer to customer #$toCustomerId: $description");
            $this->recordTransaction($toCustomerId, 'transfer', $amount, "Transfer from customer #$fromCustomerId: $description");

            // Record transfer
            $op3 = 403;
            DBManager::newOperation($this->dbId, $op3, false, 'record_transfer');
            DBManager::query('record_transfer',
                "INSERT INTO transfers (from_customer, to_customer, amount, description) 
                 VALUES (:from, :to, :amount, :desc)");

            DBManager::bindValue($op3, ':from', $fromCustomerId);
            DBManager::bindValue($op3, ':to', $toCustomerId);
            DBManager::bindValue($op3, ':amount', $amount);
            DBManager::bindValue($op3, ':desc', $description);

            if (!DBManager::execute($op3)) {
                throw new Exception("Failed to record transfer");
            }

            DBManager::commit($this->dbId);
            return true;
        } catch (Exception $e) {
            DBManager::rollBack($this->dbId);
            throw $e;
        }
    }

    public function getBalance(int $customerId): float {
        $op = 501;
        DBManager::newOperation($this->dbId, $op, false, 'get_balance');
        DBManager::query('get_balance', "SELECT balance FROM customers WHERE id = :id");

        DBManager::bindValue($op, ':id', $customerId);

        if (DBManager::execute($op)) {
            $result = DBManager::fetch($op);
            return (float)$result['balance'];
        }

        throw new Exception("Failed to retrieve balance");
    }

    public function getTransactions(int $customerId, int $limit = 10): array {
        $op = 601;
        DBManager::newOperation($this->dbId, $op, false, 'get_transactions');
        DBManager::query('get_transactions',
            "SELECT type, amount, description, created_at 
             FROM transactions 
             WHERE customer_id = :id 
             ORDER BY created_at DESC 
             LIMIT :limit");

        DBManager::bindValue($op, ':id', $customerId);
        DBManager::bindValue($op, ':limit', $limit);

        if (DBManager::execute($op)) {
            return DBManager::fetchAll($op);
        }

        throw new Exception("Failed to retrieve transactions");
    }

    private function recordTransaction(int $customerId, string $type, float $amount, string $description = ''): bool {
        $op = 701;
        DBManager::newOperation($this->dbId, $op, false, 'record_transaction');
        DBManager::query('record_transaction',
            "INSERT INTO transactions (customer_id, type, amount, description) 
             VALUES (:cid, :type, :amount, :desc)");

        DBManager::bindValue($op, ':cid', $customerId);
        DBManager::bindValue($op, ':type', $type);
        DBManager::bindValue($op, ':amount', $amount);
        DBManager::bindValue($op, ':desc', $description);

        return DBManager::execute($op);
    }

    /**
     * @throws \Exception
     */
    public function getCustomerDetails(int $customerId): array {
        $op = 801;
        DBManager::newOperation($this->dbId, $op, false, 'get_customer');
        DBManager::query('get_customer',
            "SELECT id, name, email, balance, created_at 
             FROM customers 
             WHERE id = :id");

        DBManager::bindValue($op, ':id', $customerId);

        if (DBManager::execute($op)) {
            $customer = DBManager::fetch($op);
            if ($customer) {
                $customer['transactions'] = $this->getTransactions($customerId);
                return $customer;
            }
        }

        throw new Exception("Customer not found");
    }
}

// Example Usage
try {
    $bank = new BankSystem();

    // Create customers
    $johnId = $bank->createCustomer("John Doe", "john@example.com", 1000);
    $janeId = $bank->createCustomer("Jane Smith", "jane@example.com", 500);

    // Perform transactions
    $bank->deposit($johnId, 200, "Paycheck");
    $bank->withdraw($johnId, 50, "ATM withdrawal");
    $bank->transfer($johnId, $janeId, 300, "Rent payment");

    // Get account info
    $john = $bank->getCustomerDetails($johnId);
    $jane = $bank->getCustomerDetails($janeId);

    echo "John's balance: $" . $john['balance'] . "\n";
    echo "Jane's balance: $" . $jane['balance'] . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>