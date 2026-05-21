<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$dbFile = __DIR__ . '/pawventory.sqlite';

function db() {
    global $dbFile;

    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // USERS
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        role TEXT NOT NULL,
        contact TEXT,
        address TEXT,
        status TEXT NOT NULL DEFAULT 'Active',
        created_at TEXT NOT NULL
    )");

    // CATEGORIES
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        category_name TEXT NOT NULL UNIQUE,
        description TEXT,
        status TEXT NOT NULL DEFAULT 'Active',
        created_at TEXT NOT NULL
    )");

    // SUPPLIERS
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS suppliers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        supplier_name TEXT NOT NULL,
        contact_person TEXT,
        contact TEXT,
        address TEXT,
        status TEXT NOT NULL DEFAULT 'Active',
        created_at TEXT NOT NULL
    )");

    // PRODUCTS
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        sku TEXT NOT NULL UNIQUE,
        product_name TEXT NOT NULL,
        category_id INTEGER,
        supplier_id INTEGER,
        pet_type TEXT NOT NULL,
        description TEXT,
        price REAL NOT NULL DEFAULT 0,
        stock_qty INTEGER NOT NULL DEFAULT 0,
        reorder_level INTEGER NOT NULL DEFAULT 5,
        status TEXT NOT NULL DEFAULT 'Active',
        created_at TEXT NOT NULL,
        FOREIGN KEY(category_id) REFERENCES categories(id),
        FOREIGN KEY(supplier_id) REFERENCES suppliers(id)
    )");

    // ORDERS
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_no TEXT NOT NULL UNIQUE,
        customer_id INTEGER NOT NULL,
        order_date TEXT NOT NULL,
        subtotal REAL NOT NULL DEFAULT 0,
        delivery_fee REAL NOT NULL DEFAULT 0,
        total_amount REAL NOT NULL DEFAULT 0,
        payment_method TEXT NOT NULL DEFAULT 'Cash on Pickup',
        payment_status TEXT NOT NULL DEFAULT 'Unpaid',
        order_status TEXT NOT NULL DEFAULT 'Pending',
        delivery_address TEXT,
        notes TEXT,
        created_at TEXT NOT NULL
    )");

    // ORDER ITEMS
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS order_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER NOT NULL,
        product_id INTEGER NOT NULL,
        product_name TEXT NOT NULL,
        quantity INTEGER NOT NULL DEFAULT 1,
        unit_price REAL NOT NULL DEFAULT 0,
        line_total REAL NOT NULL DEFAULT 0
    )");

    // DEFAULT USERS
    if ((int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() === 0) {

        $stmt = $pdo->prepare("
        INSERT INTO users
        (name,email,password,role,contact,address,status,created_at)
        VALUES(?,?,?,?,?,?,?,?)
        ");

        $stmt->execute([
            'Pet Shop Administrator',
            'admin@petshop.test',
            password_hash('admin123', PASSWORD_DEFAULT),
            'admin',
            '09170000001',
            'Pawventory Main Branch',
            'Active',
            date('Y-m-d H:i:s')
        ]);

        $stmt->execute([
            'Mia Pet Owner',
            'customer@petshop.test',
            password_hash('customer123', PASSWORD_DEFAULT),
            'customer',
            '09170000002',
            'Quezon City',
            'Active',
            date('Y-m-d H:i:s')
        ]);
    }

    return $pdo;
}

function ok($data = []) {
    echo json_encode(['success' => true] + $data);
    exit;
}

function fail($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}

$pdo = db();

$action = $_GET['action'] ?? $_POST['action'] ?? 'ping';

try {

    // PING
    if ($action === 'ping') {
        ok(['message' => 'Pawventory API is running']);
    }

    // LOGIN
    if ($action === 'login') {

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            fail('POST request required.', 405);
        }

        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = trim($_POST['password'] ?? '');
        $role = trim($_POST['role'] ?? '');

        if ($email === '' || $password === '' || $role === '') {
            fail('Email, password, and role are required.');
        }

        $stmt = $pdo->prepare("
        SELECT * FROM users
        WHERE email = ?
        AND role = ?
        AND status = 'Active'
        LIMIT 1
        ");

        $stmt->execute([$email, $role]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            fail('User not found.');
        }

        if (!password_verify($password, $user['password'])) {
            fail('Invalid password.');
        }

        unset($user['password']);

        ok([
            'message' => 'Login successful.',
            'user' => $user
        ]);
    }

    // REGISTER CUSTOMER
    if ($action === 'register_customer') {

        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = trim($_POST['password'] ?? '');
        $contact = trim($_POST['contact'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if ($name === '' || $email === '' || $password === '') {
            fail('Name, email, and password are required.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            fail('Valid email is required.');
        }

        if (strlen($password) < 6) {
            fail('Password must be at least 6 characters.');
        }

        $stmt = $pdo->prepare("
        SELECT id FROM users
        WHERE email = ?
        LIMIT 1
        ");

        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            fail('Email is already registered.');
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
        INSERT INTO users
        (name,email,password,role,contact,address,status,created_at)
        VALUES(?,?,?,?,?,?,?,?)
        ");

        $stmt->execute([
            $name,
            $email,
            $hashedPassword,
            'customer',
            $contact,
            $address,
            'Active',
            date('Y-m-d H:i:s')
        ]);

        ok([
            'message' => 'Customer account created.'
        ]);
    }

    fail('Invalid action.');

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fail($e->getMessage(), 500);
}
?>