<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

$dbFile = __DIR__ . '/pawventory.sqlite';

function db() {
    global $dbFile;
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
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

    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        category_name TEXT NOT NULL UNIQUE,
        description TEXT,
        status TEXT NOT NULL DEFAULT 'Active',
        created_at TEXT NOT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS suppliers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        supplier_name TEXT NOT NULL,
        contact_person TEXT,
        contact TEXT,
        address TEXT,
        status TEXT NOT NULL DEFAULT 'Active',
        created_at TEXT NOT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sku TEXT NOT NULL UNIQUE,
    product_name TEXT NOT NULL,
    category_id INTEGER,
    supplier_id INTEGER,
    pet_type TEXT NOT NULL,
    description TEXT,
    price REAL NOT NULL DEFAULT 0,
    stock_qty INTEGER NOT NULL DEFAULT 0,
    reorder_level INTEGER NOT NULL DEFAULT 0,
    status TEXT NOT NULL DEFAULT 'Active',
    created_at TEXT NOT NULL,
    FOREIGN KEY(category_id) REFERENCES categories(id),
    FOREIGN KEY(supplier_id) REFERENCES suppliers(id)
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
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

    $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER NOT NULL,
        product_id INTEGER NOT NULL,
        product_name TEXT NOT NULL,
        quantity INTEGER NOT NULL DEFAULT 1,
        unit_price REAL NOT NULL DEFAULT 0,
        line_total REAL NOT NULL DEFAULT 0
    )");

    if ((int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() === 0) {
        $stmt = $pdo->prepare("INSERT INTO users(name,email,password,role,contact,address,status,created_at) VALUES(?,?,?,?,?,?,?,?)");
        $stmt->execute(['Pet Shop Administrator','admin@petshop.test','admin123','admin','09170000001','Pawventory Main Branch','Active',date('Y-m-d H:i:s')]);
        $stmt->execute(['Mia Pet Owner','customer@petshop.test','customer123','customer','09170000002','Quezon City','Active',date('Y-m-d H:i:s')]);
    }

    if ((int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn() === 0) {
        $stmt = $pdo->prepare("INSERT INTO categories(category_name,description,status,created_at) VALUES(?,?,?,?)");
        $stmt->execute(['Nutrition','Daily meals, treats, and supplements','Active',date('Y-m-d H:i:s')]);
        $stmt->execute(['Grooming Care','Shampoo, brushes, coat and hygiene products','Active',date('Y-m-d H:i:s')]);
        $stmt->execute(['Play and Comfort','Toys, beds, collars, carriers, and bowls','Active',date('Y-m-d H:i:s')]);
        $stmt->execute(['Wellness','Vitamins, hygiene, and pet health essentials','Active',date('Y-m-d H:i:s')]);
    }

    if ((int)$pdo->query("SELECT COUNT(*) FROM suppliers")->fetchColumn() === 0) {
        $stmt = $pdo->prepare("INSERT INTO suppliers(supplier_name,contact_person,contact,address,status,created_at) VALUES(?,?,?,?,?,?)");
        $stmt->execute(['Pawline Distribution','Lara Cruz','09181230001','Makati City','Active',date('Y-m-d H:i:s')]);
        $stmt->execute(['Furry Basket Trading','Mark Reyes','09181230002','Pasig City','Active',date('Y-m-d H:i:s')]);
        $stmt->execute(['Tail & Whisker Supply','Nina Santos','09181230003','Manila City','Active',date('Y-m-d H:i:s')]);
    }

    if ((int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn() === 0) {
        $catNutrition = (int)$pdo->query("SELECT id FROM categories WHERE category_name='Nutrition' LIMIT 1")->fetchColumn();
        $catGroom = (int)$pdo->query("SELECT id FROM categories WHERE category_name='Grooming Care' LIMIT 1")->fetchColumn();
        $catPlay = (int)$pdo->query("SELECT id FROM categories WHERE category_name='Play and Comfort' LIMIT 1")->fetchColumn();
        $catWell = (int)$pdo->query("SELECT id FROM categories WHERE category_name='Wellness' LIMIT 1")->fetchColumn();
        $sup = (int)$pdo->query("SELECT id FROM suppliers LIMIT 1")->fetchColumn();
        $stmt = $pdo->prepare("INSERT INTO products(sku,product_name,category_id,supplier_id,pet_type,description,price,stock_qty,reorder_level,status,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute(['DOG-NUT-001','Grain-Free Dog Meal 5kg',$catNutrition,$sup,'Dog','Balanced dry meal for adult dogs',1390,36,8,'Active',date('Y-m-d H:i:s')]);
        $stmt->execute(['CAT-NUT-002','Tuna Crunch Cat Bites',$catNutrition,$sup,'Cat','Crunchy tuna-flavored cat treats',295,52,10,'Active',date('Y-m-d H:i:s')]);
        $stmt->execute(['PET-GRM-003','Oatmeal Coat Shampoo',$catGroom,$sup,'Dog/Cat','Gentle shampoo for sensitive coats',365,30,7,'Active',date('Y-m-d H:i:s')]);
        $stmt->execute(['PET-PLY-004','Soft Rope Toy',$catPlay,$sup,'Dog','Durable rope toy for active pets',240,44,10,'Active',date('Y-m-d H:i:s')]);
        $stmt->execute(['PET-WEL-005','Daily Pet Multivitamins',$catWell,$sup,'Dog/Cat','Daily supplement for overall wellness',520,25,8,'Active',date('Y-m-d H:i:s')]);
        $stmt->execute(['CAT-PLY-006','Cat Tunnel Bed',$catPlay,$sup,'Cat','Foldable play tunnel and nap space',890,18,5,'Active',date('Y-m-d H:i:s')]);
    }
    return $pdo;
}

function ok($data = []) { echo json_encode(['success' => true] + $data); exit; }
function fail($message, $code = 400) { http_response_code($code); echo json_encode(['success' => false, 'message' => $message]); exit; }
function next_order_no($pdo) { $count = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(); return 'PW-' . date('Ymd') . '-' . str_pad((string)($count + 1), 4, '0', STR_PAD_LEFT); }

$pdo = db();
$action = $_GET['action'] ?? $_POST['action'] ?? 'ping';

try {
    if ($action === 'ping') ok(['message' => 'Pawventory API is running']);

    if ($action === 'login') {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = trim($_POST['password'] ?? '');
        $role = trim($_POST['role'] ?? '');
        if ($email === '' || $password === '' || $role === '') fail('Email, password, and role are required.');
        $stmt = $pdo->prepare("
        SELECT * FROM users
        WHERE email=? AND role=? AND status='Active'
        LIMIT 1
        ");

        $stmt->execute([$email, $role]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            fail('Invalid login details.');
        }
        $stmt->execute([$email,$password,$role]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) fail('Invalid login details.');
        ok(['message' => 'Login successful.', 'user' => $user]);
    }

    if ($action === 'register_customer') {
        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = trim($_POST['password'] ?? '');
        $contact = trim($_POST['contact'] ?? '');
        $address = trim($_POST['address'] ?? '');
        if ($name === '' || $email === '' || $password === '') fail('Name, email, and password are required.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail('Valid email is required.');
        if (strlen($password) < 6) fail('Password must be at least 6 characters.');
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) fail('Email is already registered.');
        $stmt = $pdo->prepare("INSERT INTO users(name,email,password,role,contact,address,status,created_at) VALUES(?,?,?,?,?,?,?,?)");
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
        INSERT INTO users(name,email,password,role,contact,address,status,created_at)
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
        ok(['message' => 'Customer account created.']);
    }

    if ($action === 'list_categories') ok(['categories' => $pdo->query("SELECT * FROM categories ORDER BY category_name")->fetchAll(PDO::FETCH_ASSOC)]);
    $pdo->exec("CREATE TABLE IF NOT EXISTS suppliers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    supplier_name TEXT NOT NULL,
    contact_person TEXT,
    phone TEXT,
    email TEXT,
    address TEXT,
    created_at TEXT NOT NULL
    )");
    if ($action === 'list_customers') {
        $stmt = $pdo->prepare("SELECT id,name,email,contact,address,status,created_at FROM users WHERE role='customer' ORDER BY id DESC");
        $stmt->execute(); ok(['customers' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($action === 'list_products') {
        $rows = $pdo->query("SELECT products.*, categories.category_name, suppliers.supplier_name FROM products LEFT JOIN categories ON categories.id=products.category_id LEFT JOIN suppliers ON suppliers.id=products.supplier_id ORDER BY products.id DESC")->fetchAll(PDO::FETCH_ASSOC);
        ok(['products' => $rows]);
    }

    if ($action === 'list_active_products') {
        $stmt = $pdo->prepare("SELECT products.*, categories.category_name FROM products LEFT JOIN categories ON categories.id=products.category_id WHERE products.status='Active' AND products.stock_qty > 0 ORDER BY products.product_name");
        $stmt->execute(); ok(['products' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($action === 'create_order') {
        $customerId = (int)($_POST['customer_id'] ?? 0);
        $deliveryFee = (float)($_POST['delivery_fee'] ?? 0);
        $paymentMethod = trim($_POST['payment_method'] ?? 'Cash on Pickup');
        $deliveryAddress = trim($_POST['delivery_address'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $items = json_decode($_POST['items'] ?? '[]', true);
        if ($customerId <= 0) fail('Invalid customer account.');
        if (!in_array($paymentMethod, ['Cash on Pickup','Cash on Delivery','GCash','Bank Transfer'], true)) fail('Invalid payment method.');
        if (!is_array($items) || count($items) === 0) fail('Please add at least one item to the cart.');
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id=? AND role='customer' LIMIT 1");
        $stmt->execute([$customerId]);
        if (!$stmt->fetch()) fail('Customer not found.');
        $pdo->beginTransaction();
        $subtotal = 0; $clean = [];
        foreach ($items as $item) {
            $pid = (int)($item['product_id'] ?? 0);
            $qty = max(1, (int)($item['quantity'] ?? 1));
            $stmt = $pdo->prepare("SELECT id, product_name, price, stock_qty, status FROM products WHERE id=? LIMIT 1");
            $stmt->execute([$pid]);
            $p = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$p || $p['status'] !== 'Active') { $pdo->rollBack(); fail('A selected product is no longer available.'); }
            if ((int)$p['stock_qty'] < $qty) { $pdo->rollBack(); fail($p['product_name'] . ' does not have enough stock.'); }
            $line = (float)$p['price'] * $qty; $subtotal += $line;
            $clean[] = ['product_id'=>(int)$p['id'],'product_name'=>$p['product_name'],'quantity'=>$qty,'unit_price'=>(float)$p['price'],'line_total'=>$line];
        }
        if ($deliveryFee < 0) $deliveryFee = 0;
        $total = $subtotal + $deliveryFee;
        $orderNo = next_order_no($pdo);
        $stmt = $pdo->prepare("INSERT INTO orders(order_no,customer_id,order_date,subtotal,delivery_fee,total_amount,payment_method,payment_status,order_status,delivery_address,notes,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$orderNo,$customerId,date('Y-m-d'),$subtotal,$deliveryFee,$total,$paymentMethod,'Unpaid','Pending',$deliveryAddress,$notes,date('Y-m-d H:i:s')]);
        $orderId = (int)$pdo->lastInsertId();
        $itemStmt = $pdo->prepare("INSERT INTO order_items(order_id,product_id,product_name,quantity,unit_price,line_total) VALUES(?,?,?,?,?,?)");
        $stockStmt = $pdo->prepare("UPDATE products SET stock_qty = stock_qty - ? WHERE id=?");
        foreach ($clean as $row) { $itemStmt->execute([$orderId,$row['product_id'],$row['product_name'],$row['quantity'],$row['unit_price'],$row['line_total']]); $stockStmt->execute([$row['quantity'],$row['product_id']]); }
        $pdo->commit(); ok(['message'=>'Order submitted successfully.','order_id'=>$orderId,'order_no'=>$orderNo]);
    }

    if ($action === 'list_orders') {
        $customerId = (int)($_GET['customer_id'] ?? 0);
        $sql = "SELECT orders.*, users.name AS customer_name, users.email AS customer_email, users.contact FROM orders JOIN users ON users.id=orders.customer_id";
        if ($customerId > 0) { $stmt = $pdo->prepare($sql . " WHERE orders.customer_id=? ORDER BY orders.id DESC"); $stmt->execute([$customerId]); ok(['orders'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]); }
        ok(['orders'=>$pdo->query($sql . " ORDER BY orders.id DESC")->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($action === 'get_order') {
        $id = (int)($_GET['id'] ?? 0); if ($id <= 0) fail('Invalid order ID.');
        $stmt = $pdo->prepare("SELECT orders.*, users.name AS customer_name, users.email AS customer_email, users.contact, users.address FROM orders JOIN users ON users.id=orders.customer_id WHERE orders.id=? LIMIT 1");
        $stmt->execute([$id]); $order = $stmt->fetch(PDO::FETCH_ASSOC); if (!$order) fail('Order not found.');
        $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id=? ORDER BY id ASC"); $stmt->execute([$id]);
        ok(['order'=>$order,'items'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($action === 'cancel_order') {
        $orderId = (int)($_POST['id'] ?? 0); $customerId = (int)($_POST['customer_id'] ?? 0);
        if ($orderId <= 0 || $customerId <= 0) fail('Invalid order details.');
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id=? AND customer_id=? LIMIT 1"); $stmt->execute([$orderId,$customerId]); $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) fail('Order not found.');
        if (!in_array($order['order_status'], ['Pending','Approved'], true)) fail('Only pending or approved orders can be cancelled.');
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id=?"); $stmt->execute([$orderId]);
        $stockStmt = $pdo->prepare("UPDATE products SET stock_qty = stock_qty + ? WHERE id=?");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $item) $stockStmt->execute([(int)$item['quantity'],(int)$item['product_id']]);
        $stmt = $pdo->prepare("UPDATE orders SET order_status='Cancelled' WHERE id=?"); $stmt->execute([$orderId]);
        $pdo->commit(); ok(['message'=>'Order cancelled.']);
    }

    fail('Invalid action.');
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    fail($e->getMessage(), 500);
}
?>