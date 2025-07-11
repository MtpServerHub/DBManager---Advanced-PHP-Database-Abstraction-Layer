<?php
require_once '../autoload.php';
use DBManager\src\DBManager;

class ECommerceSystem {
    private $dbId = 'ecommerce_db';

    public function __construct() {
        // Initialize DBManager
        DBManager::prepare();

        // Configure database connection
        DBManager::define(
            'localhost',
            $this->dbId,
            'ecommerce',
            'ecom_user',
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
                address TEXT,
                phone VARCHAR(20),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",

            'products' => "CREATE TABLE IF NOT EXISTS products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                description TEXT,
                price DECIMAL(10,2) NOT NULL,
                stock INT NOT NULL DEFAULT 0,
                category VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",

            'orders' => "CREATE TABLE IF NOT EXISTS orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                customer_id INT NOT NULL,
                total DECIMAL(10,2) NOT NULL,
                status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
                payment_method VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (customer_id) REFERENCES customers(id)
            )",

            'order_items' => "CREATE TABLE IF NOT EXISTS order_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                product_id INT NOT NULL,
                quantity INT NOT NULL,
                unit_price DECIMAL(10,2) NOT NULL,
                FOREIGN KEY (order_id) REFERENCES orders(id),
                FOREIGN KEY (product_id) REFERENCES products(id)
            )",

            'payments' => "CREATE TABLE IF NOT EXISTS payments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                method VARCHAR(50) NOT NULL,
                transaction_id VARCHAR(100),
                status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (order_id) REFERENCES orders(id)
            )"
        ];

        foreach ($tables as $table => $sql) {
            if (!DBManager::queryDirect($this->dbId, $sql)) {
                throw new Exception("Failed to create table: $table");
            }
        }
    }

    // Customer Management
    public function createCustomer(string $name, string $email, string $address = null, string $phone = null): int {
        DBManager::beingTransaction($this->dbId);

        try {
            $op = 101;
            DBManager::newOperation($this->dbId, $op, false, 'insert_customer');
            DBManager::query('insert_customer',
                "INSERT INTO customers (name, email, address, phone) 
                 VALUES (:name, :email, :address, :phone)");

            DBManager::bindValue($op, ':name', $name);
            DBManager::bindValue($op, ':email', $email);
            DBManager::bindValue($op, ':address', $address);
            DBManager::bindValue($op, ':phone', $phone);

            if (!DBManager::execute($op)) {
                throw new Exception("Failed to create customer");
            }

            $customerId = DBManager::fetch(DBManager::newOperation($this->dbId, 102, false, 'last_insert_id'))['id'];

            DBManager::commit($this->dbId);
            return $customerId;
        } catch (Exception $e) {
            DBManager::rollBack($this->dbId);
            throw $e;
        }
    }

    public function getCustomer(int $customerId): array {
        $op = 201;
        DBManager::newOperation($this->dbId, $op, false, 'get_customer');
        DBManager::query('get_customer',
            "SELECT * FROM customers WHERE id = :id");

        DBManager::bindValue($op, ':id', $customerId);

        if (DBManager::execute($op)) {
            $customer = DBManager::fetch($op);
            if ($customer) {
                return $customer;
            }
        }

        throw new Exception("Customer not found");
    }

    // Product Management
    public function addProduct(string $name, float $price, int $stock, string $description = null, string $category = null): int {
        DBManager::beingTransaction($this->dbId);

        try {
            $op = 301;
            DBManager::newOperation($this->dbId, $op, false, 'add_product');
            DBManager::query('add_product',
                "INSERT INTO products (name, description, price, stock, category) 
                 VALUES (:name, :desc, :price, :stock, :category)");

            DBManager::bindValue($op, ':name', $name);
            DBManager::bindValue($op, ':desc', $description);
            DBManager::bindValue($op, ':price', $price);
            DBManager::bindValue($op, ':stock', $stock);
            DBManager::bindValue($op, ':category', $category);

            if (!DBManager::execute($op)) {
                throw new Exception("Failed to add product");
            }

            $productId = DBManager::fetch(DBManager::newOperation($this->dbId, 302, false, 'last_insert_id'))['id'];

            DBManager::commit($this->dbId);
            return $productId;
        } catch (Exception $e) {
            DBManager::rollBack($this->dbId);
            throw $e;
        }
    }

    public function updateProductStock(int $productId, int $quantityChange): bool {
        DBManager::beingTransaction($this->dbId);

        try {
            $op = 401;
            DBManager::newOperation($this->dbId, $op, false, 'update_stock');
            DBManager::query('update_stock',
                "UPDATE products SET stock = stock + :change WHERE id = :id");

            DBManager::bindValue($op, ':change', $quantityChange);
            DBManager::bindValue($op, ':id', $productId);

            if (!DBManager::execute($op)) {
                throw new Exception("Failed to update product stock");
            }

            DBManager::commit($this->dbId);
            return true;
        } catch (Exception $e) {
            DBManager::rollBack($this->dbId);
            throw $e;
        }
    }

    public function getProduct(int $productId): array {
        $op = 501;
        DBManager::newOperation($this->dbId, $op, false, 'get_product');
        DBManager::query('get_product',
            "SELECT * FROM products WHERE id = :id");

        DBManager::bindValue($op, ':id', $productId);

        if (DBManager::execute($op)) {
            $product = DBManager::fetch($op);
            if ($product) {
                return $product;
            }
        }

        throw new Exception("Product not found");
    }

    public function listProducts(string $category = null, int $limit = 10): array {
        $op = 601;
        DBManager::newOperation($this->dbId, $op, false, 'list_products');

        $sql = "SELECT * FROM products WHERE stock > 0";
        if ($category) {
            $sql .= " AND category = :category";
        }
        $sql .= " LIMIT :limit";

        DBManager::query('list_products', $sql);

        if ($category) {
            DBManager::bindValue($op, ':category', $category);
        }
        DBManager::bindValue($op, ':limit', $limit);

        if (DBManager::execute($op)) {
            return DBManager::fetchAll($op);
        }

        throw new Exception("Failed to retrieve products");
    }

    // Order Management
    public function createOrder(int $customerId, array $items, string $paymentMethod = 'credit_card'): int {
        DBManager::beingTransaction($this->dbId);

        try {
            // Calculate order total and validate items
            $total = 0;
            $productUpdates = [];

            foreach ($items as $item) {
                $product = $this->getProduct($item['product_id']);
                if ($product['stock'] < $item['quantity']) {
                    throw new Exception("Insufficient stock for product: " . $product['name']);
                }

                $total += $product['price'] * $item['quantity'];
                $productUpdates[] = [
                    'id' => $item['product_id'],
                    'quantity' => -$item['quantity'] // Negative because we're reducing stock
                ];
            }

            // Create order
            $op1 = 701;
            DBManager::newOperation($this->dbId, $op1, false, 'create_order');
            DBManager::query('create_order',
                "INSERT INTO orders (customer_id, total, payment_method) 
                 VALUES (:cid, :total, :method)");

            DBManager::bindValue($op1, ':cid', $customerId);
            DBManager::bindValue($op1, ':total', $total);
            DBManager::bindValue($op1, ':method', $paymentMethod);

            if (!DBManager::execute($op1)) {
                throw new Exception("Failed to create order");
            }

            $orderId = DBManager::fetch(DBManager::newOperation($this->dbId, 702, false, 'last_insert_id'))['id'];

            // Add order items
            foreach ($items as $item) {
                $product = $this->getProduct($item['product_id']);

                $op2 = 703;
                DBManager::newOperation($this->dbId, $op2, false, 'add_order_item');
                DBManager::query('add_order_item',
                    "INSERT INTO order_items (order_id, product_id, quantity, unit_price) 
                     VALUES (:oid, :pid, :qty, :price)");

                DBManager::bindValue($op2, ':oid', $orderId);
                DBManager::bindValue($op2, ':pid', $item['product_id']);
                DBManager::bindValue($op2, ':qty', $item['quantity']);
                DBManager::bindValue($op2, ':price', $product['price']);

                if (!DBManager::execute($op2)) {
                    throw new Exception("Failed to add order item");
                }
            }

            // Update product stocks
            foreach ($productUpdates as $update) {
                $this->updateProductStock($update['id'], $update['quantity']);
            }

            // Create payment record
            $op3 = 704;
            DBManager::newOperation($this->dbId, $op3, false, 'create_payment');
            DBManager::query('create_payment',
                "INSERT INTO payments (order_id, amount, method) 
                 VALUES (:oid, :amount, :method)");

            DBManager::bindValue($op3, ':oid', $orderId);
            DBManager::bindValue($op3, ':amount', $total);
            DBManager::bindValue($op3, ':method', $paymentMethod);

            if (!DBManager::execute($op3)) {
                throw new Exception("Failed to create payment record");
            }

            DBManager::commit($this->dbId);
            return $orderId;
        } catch (Exception $e) {
            DBManager::rollBack($this->dbId);
            throw $e;
        }
    }

    public function getOrder(int $orderId): array {
        $op1 = 801;
        DBManager::newOperation($this->dbId, $op1, false, 'get_order');
        DBManager::query('get_order',
            "SELECT o.*, c.name as customer_name, c.email as customer_email 
             FROM orders o 
             JOIN customers c ON o.customer_id = c.id 
             WHERE o.id = :id");

        DBManager::bindValue($op1, ':id', $orderId);

        if (DBManager::execute($op1)) {
            $order = DBManager::fetch($op1);
            if ($order) {
                // Get order items
                $op2 = 802;
                DBManager::newOperation($this->dbId, $op2, false, 'get_order_items');
                DBManager::query('get_order_items',
                    "SELECT oi.*, p.name as product_name, p.description as product_description 
                     FROM order_items oi 
                     JOIN products p ON oi.product_id = p.id 
                     WHERE oi.order_id = :oid");

                DBManager::bindValue($op2, ':oid', $orderId);

                if (DBManager::execute($op2)) {
                    $order['items'] = DBManager::fetchAll($op2);
                }

                // Get payment info
                $op3 = 803;
                DBManager::newOperation($this->dbId, $op3, false, 'get_payment');
                DBManager::query('get_payment',
                    "SELECT * FROM payments WHERE order_id = :oid LIMIT 1");

                DBManager::bindValue($op3, ':oid', $orderId);

                if (DBManager::execute($op3)) {
                    $order['payment'] = DBManager::fetch($op3);
                }

                return $order;
            }
        }

        throw new Exception("Order not found");
    }

    public function updateOrderStatus(int $orderId, string $status): bool {
        $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            throw new InvalidArgumentException("Invalid order status");
        }

        $op = 901;
        DBManager::newOperation($this->dbId, $op, false, 'update_order_status');
        DBManager::query('update_order_status',
            "UPDATE orders SET status = :status WHERE id = :id");

        DBManager::bindValue($op, ':status', $status);
        DBManager::bindValue($op, ':id', $orderId);

        return DBManager::execute($op);
    }

    public function getCustomerOrders(int $customerId, int $limit = 5): array {
        $op = 1001;
        DBManager::newOperation($this->dbId, $op, false, 'get_customer_orders');
        DBManager::query('get_customer_orders',
            "SELECT * FROM orders 
             WHERE customer_id = :cid 
             ORDER BY created_at DESC 
             LIMIT :limit");

        DBManager::bindValue($op, ':cid', $customerId);
        DBManager::bindValue($op, ':limit', $limit);

        if (DBManager::execute($op)) {
            $orders = DBManager::fetchAll($op);

            // Get basic item count for each order
            foreach ($orders as &$order) {
                $op2 = 1002;
                DBManager::newOperation($this->dbId, $op2, false, 'count_order_items');
                DBManager::query('count_order_items',
                    "SELECT COUNT(*) as item_count, SUM(quantity) as total_quantity 
                     FROM order_items 
                     WHERE order_id = :oid");

                DBManager::bindValue($op2, ':oid', $order['id']);

                if (DBManager::execute($op2)) {
                    $count = DBManager::fetch($op2);
                    $order['item_count'] = $count['item_count'];
                    $order['total_quantity'] = $count['total_quantity'];
                }
            }

            return $orders;
        }

        throw new Exception("Failed to retrieve customer orders");
    }
}

// Example Usage
try {
    $shop = new ECommerceSystem();

    // Create customers
    $aliceId = $shop->createCustomer("Alice Johnson", "alice@example.com", "123 Main St", "555-1234");
    $bobId = $shop->createCustomer("Bob Smith", "bob@example.com", "456 Oak Ave", "555-5678");

    // Add products
    $laptopId = $shop->addProduct("Laptop", 999.99, 10, "High-performance laptop", "electronics");
    $phoneId = $shop->addProduct("Smartphone", 699.99, 15, "Latest model smartphone", "electronics");
    $headphonesId = $shop->addProduct("Headphones", 149.99, 20, "Noise-cancelling headphones", "accessories");

    // Alice places an order
    $order1Id = $shop->createOrder($aliceId, [
        ['product_id' => $laptopId, 'quantity' => 1],
        ['product_id' => $headphonesId, 'quantity' => 2]
    ], 'credit_card');

    // Bob places an order
    $order2Id = $shop->createOrder($bobId, [
        ['product_id' => $phoneId, 'quantity' => 1]
    ], 'paypal');

    // Update order status
    $shop->updateOrderStatus($order1Id, 'processing');
    $shop->updateOrderStatus($order2Id, 'shipped');

    // Get order details
    $aliceOrder = $shop->getOrder($order1Id);
    $bobOrder = $shop->getOrder($order2Id);

    echo "Alice's Order #{$aliceOrder['id']} Total: $" . $aliceOrder['total'] . "\n";
    echo "Bob's Order #{$bobOrder['id']} Status: " . $bobOrder['status'] . "\n";

    // List customer orders
    $aliceOrders = $shop->getCustomerOrders($aliceId);
    echo "Alice has " . count($aliceOrders) . " orders\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>