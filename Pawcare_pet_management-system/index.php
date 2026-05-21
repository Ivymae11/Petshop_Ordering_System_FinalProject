<?php
session_start();
$dbFile = __DIR__ . '/pawventory.sqlite';
function db() {
    global $dbFile;
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT,name TEXT NOT NULL,email TEXT NOT NULL UNIQUE,password TEXT NOT NULL,role TEXT NOT NULL,contact TEXT,address TEXT,status TEXT NOT NULL DEFAULT 'Active',created_at TEXT NOT NULL)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (id INTEGER PRIMARY KEY AUTOINCREMENT,category_name TEXT NOT NULL UNIQUE,description TEXT,status TEXT NOT NULL DEFAULT 'Active',created_at TEXT NOT NULL)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS suppliers (id INTEGER PRIMARY KEY AUTOINCREMENT,supplier_name TEXT NOT NULL,contact_person TEXT,contact TEXT,address TEXT,status TEXT NOT NULL DEFAULT 'Active',created_at TEXT NOT NULL)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS products (id INTEGER PRIMARY KEY AUTOINCREMENT,sku TEXT NOT NULL UNIQUE,product_name TEXT NOT NULL,category_id INTEGER,supplier_id INTEGER,pet_type TEXT NOT NULL,description TEXT,price REAL NOT NULL DEFAULT 0,stock_qty INTEGER NOT NULL DEFAULT 0,reorder_level INTEGER NOT NULL DEFAULT 5,status TEXT NOT NULL DEFAULT 'Active',created_at TEXT NOT NULL)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (id INTEGER PRIMARY KEY AUTOINCREMENT,order_no TEXT NOT NULL UNIQUE,customer_id INTEGER NOT NULL,order_date TEXT NOT NULL,subtotal REAL NOT NULL DEFAULT 0,delivery_fee REAL NOT NULL DEFAULT 0,total_amount REAL NOT NULL DEFAULT 0,payment_method TEXT NOT NULL DEFAULT 'Cash on Pickup',payment_status TEXT NOT NULL DEFAULT 'Unpaid',order_status TEXT NOT NULL DEFAULT 'Pending',delivery_address TEXT,notes TEXT,created_at TEXT NOT NULL)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (id INTEGER PRIMARY KEY AUTOINCREMENT,order_id INTEGER NOT NULL,product_id INTEGER NOT NULL,product_name TEXT NOT NULL,quantity INTEGER NOT NULL DEFAULT 1,unit_price REAL NOT NULL DEFAULT 0,line_total REAL NOT NULL DEFAULT 0)");
    if ((int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() === 0) { $stmt=$pdo->prepare("INSERT INTO users(name,email,password,role,contact,address,status,created_at) VALUES(?,?,?,?,?,?,?,?)"); $stmt->execute(['Pet Shop Administrator','admin@petshop.test','admin123','admin','09170000001','Paws & Care Main Branch','Active',date('Y-m-d H:i:s')]); $stmt->execute(['Mia Pet Owner','customer@petshop.test','customer123','customer','09170000002','Quezon City','Active',date('Y-m-d H:i:s')]); }
    if ((int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn() === 0) { $stmt=$pdo->prepare("INSERT INTO categories(category_name,description,status,created_at) VALUES(?,?,?,?)"); foreach([['Nutrition','Daily meals, treats, and supplements'],['Grooming Care','Coat and hygiene products'],['Play and Comfort','Toys, beds, collars, carriers, and bowls'],['Wellness','Pet health essentials']] as $c){$stmt->execute([$c[0],$c[1],'Active',date('Y-m-d H:i:s')]);} }
    if ((int)$pdo->query("SELECT COUNT(*) FROM suppliers")->fetchColumn() === 0) { $stmt=$pdo->prepare("INSERT INTO suppliers(supplier_name,contact_person,contact,address,status,created_at) VALUES(?,?,?,?,?,?)"); $stmt->execute(['Pawline Distribution','Lara Cruz','09181230001','Makati City','Active',date('Y-m-d H:i:s')]); $stmt->execute(['Furry Basket Trading','Mark Reyes','09181230002','Pasig City','Active',date('Y-m-d H:i:s')]); }
    if ((int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn() === 0) { $cat=(int)$pdo->query("SELECT id FROM categories LIMIT 1")->fetchColumn(); $sup=(int)$pdo->query("SELECT id FROM suppliers LIMIT 1")->fetchColumn(); $stmt=$pdo->prepare("INSERT INTO products(sku,product_name,category_id,supplier_id,pet_type,description,price,stock_qty,reorder_level,status,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?)"); $items=[['DOG-NUT-001','Grain-Free Dog Meal 5kg','Dog','Balanced dry meal',1390,36],['CAT-NUT-002','Tuna Crunch Cat Bites','Cat','Crunchy treats',295,52],['PET-GRM-003','Oatmeal Coat Shampoo','Dog/Cat','Gentle shampoo',365,30],['PET-PLY-004','Soft Rope Toy','Dog','Durable toy',240,44],['PET-WEL-005','Daily Pet Multivitamins','Dog/Cat','Daily supplement',520,25]]; foreach($items as $it){$stmt->execute([$it[0],$it[1],$cat,$sup,$it[2],$it[3],$it[4],$it[5],8,'Active',date('Y-m-d H:i:s')]);} }
    return $pdo;
}
$pdo = db(); $message='';
if(isset($_GET['logout'])){ $_SESSION=[]; session_unset(); session_destroy(); header('Location: index.php'); exit; }
if(!isset($_SESSION['admin_id']) && $_SERVER['REQUEST_METHOD']==='POST' && ($_POST['form_type']??'')==='login') { $stmt=$pdo->prepare("SELECT * FROM users WHERE email=? AND password=? AND role='admin' AND status='Active' LIMIT 1"); $stmt->execute([strtolower(trim($_POST['email']??'')), trim($_POST['password']??'')]); $admin=$stmt->fetch(PDO::FETCH_ASSOC); if($admin){$_SESSION['admin_id']=$admin['id']; $_SESSION['admin_name']=$admin['name']; header('Location: index.php'); exit;} else {$message='Invalid admin login.';} }
if(!isset($_SESSION['admin_id'])):
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Paws & Care Admin</title><style>

*{box-sizing:border-box}
body{
    margin:0;
    min-height:100vh;
    font-family:Segoe UI,Arial,sans-serif;
    background:
        radial-gradient(circle at 18% 12%,rgba(255,255,255,.90),transparent 18%),
        radial-gradient(circle at 84% 16%,rgba(199,180,255,.65),transparent 27%),
        linear-gradient(135deg,#8b6ee8,#a98df5 42%,#f7f3ff);
    display:grid;
    place-items:center;
    color:#211b32;
}
.login{
    width:880px;
    height:535px;
    background:rgba(255,255,255,.94);
    border-radius:32px;
    overflow:hidden;
    display:grid;
    grid-template-columns:1fr 390px;
    box-shadow:0 30px 90px rgba(90,65,150,.26);
    border:1px solid rgba(255,255,255,.78);
}
.art{
    background:
        linear-gradient(135deg,rgba(255,255,255,.78),rgba(245,240,255,.88)),
        url('assets/pet_theme_reference.webp');
    background-size:cover;
    background-position:center;
    padding:42px;
    color:#211b32;
    position:relative;
}
.art::after{
    content:"";
    position:absolute;
    inset:auto 36px 36px 36px;
    height:110px;
    border-radius:28px;
    background:rgba(255,255,255,.72);
    box-shadow:0 20px 50px rgba(122,91,211,.16);
}
.art .mark{
    width:70px;
    height:62px;
    border-radius:20px;
    display:grid;
    place-items:center;
    background:linear-gradient(135deg,#efe8ff,#d9cffb);
    color:#7c5bd5;
    font-size:0;
    box-shadow:0 16px 36px rgba(122,91,211,.18);
}
.art .mark::before{
    content:"🐾";
    font-size:28px;
}
.art h1{
    font-size:42px;
    line-height:1.05;
    margin:28px 0 10px;
    max-width:330px;
    letter-spacing:-1px;
}
.art p{
    font-size:16px;
    color:#5c5470;
    font-weight:700;
    max-width:320px;
    line-height:1.6;
}
.form{
    padding:48px 36px;
    background:white;
}
.form h2{
    margin:0;
    color:#211b32;
    font-size:30px;
    letter-spacing:-.5px;
}
.form p{
    color:#7a728d;
    font-weight:700;
}
label{
    display:block;
    margin:16px 0 7px;
    font-weight:900;
    color:#40384e;
}
input{
    width:100%;
    padding:14px 15px;
    border:1px solid #e2dcf5;
    border-radius:18px;
    background:#f7f4ff;
    color:#211b32;
    outline:none;
}
input:focus{
    background:white;
    border-color:#8b6ee8;
    box-shadow:0 0 0 4px rgba(139,110,232,.16);
}
button{
    width:100%;
    margin-top:18px;
    border:0;
    border-radius:18px;
    padding:14px;
    background:linear-gradient(135deg,#8b6ee8,#7c5bd5);
    color:white;
    font-weight:950;
    cursor:pointer;
    box-shadow:0 18px 34px rgba(124,91,213,.27);
}
.msg{
    background:#fff1f2;
    color:#be123c;
    padding:11px 13px;
    border-radius:15px;
    font-weight:850;
    margin:12px 0;
}

</style></head><body><div class="login"><section class="art"><div class="mark">Paws & Care</div><h1>Pet care shop workspace.</h1><p>A soft and simple workspace for pet products, customers, and orders.</p></section><section class="form"><h2>Admin Login</h2><p>Pet shop management access</p><?php if($message):?><div class="msg"><?=htmlspecialchars($message)?></div><?php endif;?><form method="post"><input type="hidden" name="form_type" value="login"><label>Email</label><input name="email" value="admin@petshop.test"><label>Password</label><input type="password" name="password" value="admin123"><button>Sign In</button></form></section></div></body></html><?php exit; endif;
try {
if($_SERVER['REQUEST_METHOD']==='POST'){
 $type=$_POST['form_type']??'';
 if($type==='add_category'){ $stmt=$pdo->prepare("INSERT INTO categories(category_name,description,status,created_at) VALUES(?,?,?,?)"); $stmt->execute([trim($_POST['category_name']??''),trim($_POST['category_description']??''),trim($_POST['category_status']??'Active'),date('Y-m-d H:i:s')]); $message='Category saved.'; }
 if($type==='update_category'){ $stmt=$pdo->prepare("UPDATE categories SET category_name=?,description=?,status=? WHERE id=?"); $stmt->execute([trim($_POST['category_name']??''),trim($_POST['category_description']??''),trim($_POST['category_status']??'Active'),(int)$_POST['id']]); $message='Category updated.'; }
 if($type==='delete_category'){ $stmt=$pdo->prepare("DELETE FROM categories WHERE id=?"); $stmt->execute([(int)$_POST['id']]); $message='Category deleted.'; }
 if($type==='add_supplier'){ $stmt=$pdo->prepare("INSERT INTO suppliers(supplier_name,contact_person,contact,address,status,created_at) VALUES(?,?,?,?,?,?)"); $stmt->execute([trim($_POST['supplier_name']??''),trim($_POST['contact_person']??''),trim($_POST['supplier_contact']??''),trim($_POST['supplier_address']??''),trim($_POST['supplier_status']??'Active'),date('Y-m-d H:i:s')]); $message='Supplier saved.'; }
 if($type==='update_supplier'){ $stmt=$pdo->prepare("UPDATE suppliers SET supplier_name=?,contact_person=?,contact=?,address=?,status=? WHERE id=?"); $stmt->execute([trim($_POST['supplier_name']??''),trim($_POST['contact_person']??''),trim($_POST['supplier_contact']??''),trim($_POST['supplier_address']??''),trim($_POST['supplier_status']??'Active'),(int)$_POST['id']]); $message='Supplier updated.'; }
 if($type==='delete_supplier'){ $stmt=$pdo->prepare("DELETE FROM suppliers WHERE id=?"); $stmt->execute([(int)$_POST['id']]); $message='Supplier deleted.'; }
 if($type==='add_product'){ $stmt=$pdo->prepare("INSERT INTO products(sku,product_name,category_id,supplier_id,pet_type,description,price,stock_qty,reorder_level,status,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?)"); $stmt->execute([trim($_POST['sku']??''),trim($_POST['product_name']??''),(int)($_POST['category_id']??0),(int)($_POST['supplier_id']??0),trim($_POST['pet_type']??''),trim($_POST['description']??''),(float)($_POST['price']??0),(int)($_POST['stock_qty']??0),(int)($_POST['reorder_level']??5),trim($_POST['product_status']??'Active'),date('Y-m-d H:i:s')]); $message='Product saved.'; }
 if($type==='update_product'){ $stmt=$pdo->prepare("UPDATE products SET sku=?,product_name=?,category_id=?,supplier_id=?,pet_type=?,description=?,price=?,stock_qty=?,reorder_level=?,status=? WHERE id=?"); $stmt->execute([trim($_POST['sku']??''),trim($_POST['product_name']??''),(int)($_POST['category_id']??0),(int)($_POST['supplier_id']??0),trim($_POST['pet_type']??''),trim($_POST['description']??''),(float)($_POST['price']??0),(int)($_POST['stock_qty']??0),(int)($_POST['reorder_level']??5),trim($_POST['product_status']??'Active'),(int)$_POST['id']]); $message='Product updated.'; }
 if($type==='delete_product'){ $stmt=$pdo->prepare("DELETE FROM products WHERE id=?"); $stmt->execute([(int)$_POST['id']]); $message='Product deleted.'; }
 if($type==='update_order_status'){ $stmt=$pdo->prepare("UPDATE orders SET order_status=?,payment_status=? WHERE id=?"); $stmt->execute([trim($_POST['order_status']??'Pending'),trim($_POST['payment_status']??'Unpaid'),(int)$_POST['id']]); $message='Order updated.'; }
}
} catch(Exception $e){ $message='Action failed: '.$e->getMessage(); }
$categories=$pdo->query("SELECT * FROM categories ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$suppliers=$pdo->query("SELECT * FROM suppliers ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$products=$pdo->query("SELECT products.*,categories.category_name,suppliers.supplier_name FROM products LEFT JOIN categories ON categories.id=products.category_id LEFT JOIN suppliers ON suppliers.id=products.supplier_id ORDER BY products.id DESC")->fetchAll(PDO::FETCH_ASSOC);
$customers=$pdo->query("SELECT id,name,email,contact,address,status,created_at FROM users WHERE role='customer' ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$orders=$pdo->query("SELECT orders.*,users.name AS customer_name,users.email AS customer_email,users.contact FROM orders JOIN users ON users.id=orders.customer_id ORDER BY orders.id DESC")->fetchAll(PDO::FETCH_ASSOC);
$lowStock=$pdo->query("SELECT * FROM products WHERE stock_qty <= reorder_level ORDER BY stock_qty ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Paws & Care Management</title><style>

*{box-sizing:border-box}
html{scroll-behavior:smooth}
body{
    margin:0;
    background:
        radial-gradient(circle at 85% 2%,rgba(224,214,255,.85),transparent 28%),
        linear-gradient(135deg,#8b6ee8 0,#a98df5 16%,#f7f4ff 16%,#fbfaff 100%);
    color:#211b32;
    font-family:Segoe UI,Arial,sans-serif;
}
.top{
    position:sticky;
    top:0;
    z-index:5;
    background:rgba(255,255,255,.88);
    backdrop-filter:blur(16px);
    border-bottom:1px solid rgba(218,210,242,.75);
    box-shadow:0 12px 28px rgba(107,88,170,.08);
}
.bar{
    max-width:1500px;
    margin:auto;
    padding:18px 28px;
    display:flex;
    align-items:center;
    gap:16px;
}
.logo{
    width:50px;
    height:50px;
    border-radius:16px;
    background:linear-gradient(135deg,#efe8ff,#dcd0ff);
    color:#7c5bd5;
    display:grid;
    place-items:center;
    font-weight:1000;
    font-size:0;
    box-shadow:0 14px 30px rgba(124,91,213,.16);
}
.logo::before{
    content:"🐾";
    font-size:23px;
}
.brand{
    font-size:23px;
    font-weight:1000;
    color:#211b32;
    margin-right:auto;
}
.nav a{
    color:#504663;
    text-decoration:none;
    font-weight:900;
    padding:10px 13px;
    border-radius:999px;
}
.nav a:hover{
    background:#f0eaff;
    color:#7c5bd5;
}
.logout{
    background:#7c5bd5;
    color:white!important;
    text-decoration:none;
    border-radius:999px;
    padding:11px 15px;
    font-weight:900;
    box-shadow:0 14px 26px rgba(124,91,213,.20);
}
.hero{
    max-width:1480px;
    margin:26px auto 16px;
    padding:32px;
    border-radius:34px;
    background:
        linear-gradient(110deg,rgba(255,255,255,.93),rgba(247,244,255,.86)),
        url('assets/pet_theme_reference.webp');
    background-size:cover;
    background-position:center 26%;
    color:#211b32;
    display:grid;
    grid-template-columns:1.2fr .8fr;
    gap:20px;
    box-shadow:0 24px 64px rgba(102,82,168,.16);
    border:1px solid rgba(255,255,255,.82);
}
.hero h1{
    font-size:38px;
    margin:0 0 8px;
    letter-spacing:-1px;
}
.hero p{
    color:#5c5470;
    font-weight:750;
    max-width:600px;
    line-height:1.55;
}
.shelf{
    background:rgba(255,255,255,.82);
    border:1px solid #e6dff7;
    border-radius:24px;
    padding:20px;
    box-shadow:0 14px 36px rgba(124,91,213,.12);
}
.shelf b{color:#7c5bd5}
.stats{
    max-width:1480px;
    margin:0 auto 20px;
    display:grid;
    grid-template-columns:repeat(6,1fr);
    gap:14px;
}
.stat{
    background:rgba(255,255,255,.92);
    border-radius:24px;
    padding:18px;
    border:1px solid #e7e0f8;
    box-shadow:0 14px 34px rgba(102,82,168,.09);
}
.stat span{
    display:block;
    color:#7a728d;
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:.6px;
    font-weight:900;
}
.stat strong{
    color:#211b32;
    font-size:31px;
    display:block;
    margin-top:6px;
}
.wrap{
    max-width:1480px;
    margin:auto;
    padding-bottom:32px;
}
.module{
    background:rgba(255,255,255,.94);
    border-radius:28px;
    padding:24px;
    margin-bottom:24px;
    border:1px solid #e7e0f8;
    box-shadow:0 18px 44px rgba(102,82,168,.10);
}
.module h2{
    margin:0 0 18px;
    color:#211b32;
    font-size:25px;
}
.split{
    display:grid;
    grid-template-columns:395px 1fr;
    gap:22px;
    align-items:start;
}
.form-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:12px;
}
.wide{grid-column:1/-1}
label{
    display:block;
    color:#51465f;
    font-size:12px;
    font-weight:900;
    text-transform:uppercase;
    letter-spacing:.4px;
}
input,select,textarea{
    width:100%;
    min-height:42px;
    padding:10px 12px;
    border:1px solid #e2dcf5;
    border-radius:16px;
    background:#f7f4ff;
    color:#211b32;
    outline:none;
}
textarea{
    min-height:88px;
    resize:vertical;
}
input:focus,select:focus,textarea:focus{
    background:white;
    border-color:#8b6ee8;
    box-shadow:0 0 0 4px rgba(139,110,232,.14);
}
button,.btn{
    border:0;
    border-radius:16px;
    padding:12px 15px;
    font-weight:950;
    cursor:pointer;
}
.btn-primary{
    background:linear-gradient(135deg,#8b6ee8,#7c5bd5);
    color:white;
}
.btn-danger{
    background:#e11d48;
    color:white;
}
.btn-muted{
    background:#4c445b;
    color:white;
}
.table-wrap{overflow:auto}
table{
    width:100%;
    border-collapse:separate;
    border-spacing:0 10px;
    min-width:900px;
}
th{
    color:#7a728d;
    text-align:left;
    font-size:12px;
    text-transform:uppercase;
    padding:0 10px;
}
td{
    background:#fbfaff;
    border-top:1px solid #e7e0f8;
    border-bottom:1px solid #e7e0f8;
    padding:12px 10px;
    vertical-align:middle;
}
td:first-child{
    border-left:1px solid #e7e0f8;
    border-radius:15px 0 0 15px;
    font-weight:950;
}
td:last-child{
    border-right:1px solid #e7e0f8;
    border-radius:0 15px 15px 0;
}
.inline{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:8px;
    align-items:end;
}
.badge{
    display:inline-block;
    border-radius:999px;
    background:#f0eaff;
    color:#7c5bd5;
    padding:6px 11px;
    font-weight:900;
    font-size:12px;
}
.msg{
    background:#ecfdf5;
    color:#047857;
    border-left:6px solid #34d399;
    padding:13px 16px;
    border-radius:16px;
    font-weight:900;
    margin-bottom:18px;
}
@media(max-width:1200px){
    .stats{grid-template-columns:repeat(2,1fr)}
    .split{grid-template-columns:1fr}
    .hero{grid-template-columns:1fr}
}

</style></head><body><header class="top"><div class="bar"><div class="logo">PAW</div><div class="brand">Paws & Care Studio</div><nav class="nav"><a href="#catalog">Catalog</a><a href="#orders">Orders</a><a href="#partners">Partners</a><a href="#customers">Customers</a><a href="#stock">Stock</a></nav><a class="logout" href="?logout=1">Logout</a></div></header><section class="hero"><div><h1>Paws & Care retail dashboard.</h1><p>Manage products, suppliers, customer orders, and shop essentials with a clean pet-care workspace.</p></div><div class="shelf"><b>Signed in</b><br><?=htmlspecialchars($_SESSION['admin_name'])?><br><br><b>Customer ordering API</b><br>http://localhost:8000/api.php</div></section><section class="stats"><div class="stat"><span>Categories</span><strong><?=count($categories)?></strong></div><div class="stat"><span>Suppliers</span><strong><?=count($suppliers)?></strong></div><div class="stat"><span>Products</span><strong><?=count($products)?></strong></div><div class="stat"><span>Orders</span><strong><?=count($orders)?></strong></div><div class="stat"><span>Customers</span><strong><?=count($customers)?></strong></div><div class="stat"><span>Low Stock</span><strong><?=count($lowStock)?></strong></div></section><main class="wrap"><?php if($message):?><div class="msg"><?=htmlspecialchars($message)?></div><?php endif;?>
<section class="module" id="catalog"><h2>Product Catalog</h2><div class="split"><form method="post"><input type="hidden" name="form_type" value="add_product"><div class="form-grid"><div><label>SKU</label><input name="sku" required></div><div><label>Status</label><select name="product_status"><option>Active</option><option>Inactive</option></select></div><div class="wide"><label>Product Name</label><input name="product_name" required></div><div><label>Category</label><select name="category_id"><?php foreach($categories as $c):?><option value="<?=$c['id']?>"><?=htmlspecialchars($c['category_name'])?></option><?php endforeach;?></select></div><div><label>Supplier</label><select name="supplier_id"><?php foreach($suppliers as $s):?><option value="<?=$s['id']?>"><?=htmlspecialchars($s['supplier_name'])?></option><?php endforeach;?></select></div><div><label>Pet Type</label><input name="pet_type" required></div><div><label>Price</label><input type="number" step="0.01" name="price" required></div><div><label>Stock</label><input type="number" name="stock_qty" required></div><div><label>Reorder</label><input type="number" name="reorder_level" value="5"></div><div class="wide"><label>Description</label><textarea name="description"></textarea></div><div class="wide"><button class="btn primary" style="width:100%">Save Product</button></div></div></form><div class="cards"><?php foreach($products as $p):?><article class="card"><h3><?=htmlspecialchars($p['product_name'])?></h3><div class="meta"><?=htmlspecialchars($p['sku'])?> · <?=htmlspecialchars($p['pet_type'])?></div><p><?=htmlspecialchars($p['category_name'])?> / <?=htmlspecialchars($p['supplier_name'])?></p><p><b>₱<?=number_format((float)$p['price'],2)?></b> <span class="badge <?=$p['stock_qty'] <= $p['reorder_level'] ? 'low':''?>">Stock <?=$p['stock_qty']?></span></p><form method="post" class="inline"><input type="hidden" name="form_type" value="update_product"><input type="hidden" name="id" value="<?=$p['id']?>"><input name="sku" value="<?=htmlspecialchars($p['sku'])?>"><input name="product_name" value="<?=htmlspecialchars($p['product_name'])?>"><select name="category_id"><?php foreach($categories as $c):?><option value="<?=$c['id']?>" <?=$p['category_id']==$c['id']?'selected':''?>><?=htmlspecialchars($c['category_name'])?></option><?php endforeach;?></select><select name="supplier_id"><?php foreach($suppliers as $s):?><option value="<?=$s['id']?>" <?=$p['supplier_id']==$s['id']?'selected':''?>><?=htmlspecialchars($s['supplier_name'])?></option><?php endforeach;?></select><input name="pet_type" value="<?=htmlspecialchars($p['pet_type'])?>"><input name="price" value="<?=htmlspecialchars($p['price'])?>"><input name="stock_qty" value="<?=htmlspecialchars($p['stock_qty'])?>"><input name="reorder_level" value="<?=htmlspecialchars($p['reorder_level'])?>"><input name="description" value="<?=htmlspecialchars($p['description'])?>"><select name="product_status"><option <?=$p['status']=='Active'?'selected':''?>>Active</option><option <?=$p['status']=='Inactive'?'selected':''?>>Inactive</option></select><button class="btn primary">Update</button></form><form method="post" class="actions"><input type="hidden" name="form_type" value="delete_product"><input type="hidden" name="id" value="<?=$p['id']?>"><button class="btn danger">Delete</button></form></article><?php endforeach;?></div></div></section>
<section class="module" id="orders"><h2>Order Counter</h2><div class="table-wrap"><table><tr><th>ID</th><th>Order</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th><th>Address</th><th>Action</th></tr><?php foreach($orders as $o):?><tr><td>#<?=$o['id']?></td><td><?=htmlspecialchars($o['order_no'])?><br><?=htmlspecialchars($o['order_date'])?></td><td><?=htmlspecialchars($o['customer_name'])?><br><?=htmlspecialchars($o['customer_email'])?></td><td>₱<?=number_format((float)$o['total_amount'],2)?></td><td><?=htmlspecialchars($o['payment_method'])?><br><span class="badge"><?=htmlspecialchars($o['payment_status'])?></span></td><td><span class="badge"><?=htmlspecialchars($o['order_status'])?></span></td><td><?=htmlspecialchars($o['delivery_address'])?></td><td><form method="post" class="inline"><input type="hidden" name="form_type" value="update_order_status"><input type="hidden" name="id" value="<?=$o['id']?>"><select name="order_status"><?php foreach(['Pending','Approved','Packed','Completed','Cancelled'] as $st):?><option <?=$o['order_status']==$st?'selected':''?>><?=$st?></option><?php endforeach;?></select><select name="payment_status"><option <?=$o['payment_status']=='Unpaid'?'selected':''?>>Unpaid</option><option <?=$o['payment_status']=='Paid'?'selected':''?>>Paid</option></select><button class="btn primary">Save</button></form></td></tr><?php endforeach;?><?php if(!$orders):?><tr><td colspan="8">No customer orders yet.</td></tr><?php endif;?></table></div></section>
<section class="module" id="partners"><h2>Categories and Suppliers</h2><div class="split"><div><h3>Categories</h3><form method="post" class="form-grid"><input type="hidden" name="form_type" value="add_category"><div><label>Name</label><input name="category_name" required></div><div><label>Status</label><select name="category_status"><option>Active</option><option>Inactive</option></select></div><div class="wide"><label>Description</label><input name="category_description"></div><button class="btn primary wide">Save Category</button></form><div class="table-wrap"><table><tr><th>ID</th><th>Name</th><th>Description</th><th>Status</th><th>Action</th></tr><?php foreach($categories as $c):?><tr><td>#<?=$c['id']?></td><td><?=htmlspecialchars($c['category_name'])?></td><td><?=htmlspecialchars($c['description'])?></td><td><span class="badge"><?=htmlspecialchars($c['status'])?></span></td><td><form method="post" class="inline"><input type="hidden" name="form_type" value="update_category"><input type="hidden" name="id" value="<?=$c['id']?>"><input name="category_name" value="<?=htmlspecialchars($c['category_name'])?>"><input name="category_description" value="<?=htmlspecialchars($c['description'])?>"><select name="category_status"><option <?=$c['status']=='Active'?'selected':''?>>Active</option><option <?=$c['status']=='Inactive'?'selected':''?>>Inactive</option></select><button class="btn primary">Update</button></form><form method="post" class="actions"><input type="hidden" name="form_type" value="delete_category"><input type="hidden" name="id" value="<?=$c['id']?>"><button class="btn danger">Delete</button></form></td></tr><?php endforeach;?></table></div></div><div><h3>Suppliers</h3><form method="post" class="form-grid"><input type="hidden" name="form_type" value="add_supplier"><div class="wide"><label>Name</label><input name="supplier_name" required></div><div><label>Person</label><input name="contact_person"></div><div><label>Contact</label><input name="supplier_contact"></div><div class="wide"><label>Address</label><input name="supplier_address"></div><button class="btn primary wide">Save Supplier</button></form><div class="table-wrap"><table><tr><th>ID</th><th>Supplier</th><th>Contact</th><th>Address</th><th>Status</th><th>Action</th></tr><?php foreach($suppliers as $s):?><tr><td>#<?=$s['id']?></td><td><?=htmlspecialchars($s['supplier_name'])?><br><?=htmlspecialchars($s['contact_person'])?></td><td><?=htmlspecialchars($s['contact'])?></td><td><?=htmlspecialchars($s['address'])?></td><td><span class="badge"><?=htmlspecialchars($s['status'])?></span></td><td><form method="post" class="inline"><input type="hidden" name="form_type" value="update_supplier"><input type="hidden" name="id" value="<?=$s['id']?>"><input name="supplier_name" value="<?=htmlspecialchars($s['supplier_name'])?>"><input name="contact_person" value="<?=htmlspecialchars($s['contact_person'])?>"><input name="supplier_contact" value="<?=htmlspecialchars($s['contact'])?>"><input name="supplier_address" value="<?=htmlspecialchars($s['address'])?>"><select name="supplier_status"><option <?=$s['status']=='Active'?'selected':''?>>Active</option><option <?=$s['status']=='Inactive'?'selected':''?>>Inactive</option></select><button class="btn primary">Update</button></form><form method="post" class="actions"><input type="hidden" name="form_type" value="delete_supplier"><input type="hidden" name="id" value="<?=$s['id']?>"><button class="btn danger">Delete</button></form></td></tr><?php endforeach;?></table></div></div></div></section>
<section class="module" id="customers"><h2>Customer Accounts</h2><div class="table-wrap"><table><tr><th>ID</th><th>Name</th><th>Email</th><th>Contact</th><th>Address</th><th>Status</th></tr><?php foreach($customers as $c):?><tr><td>#<?=$c['id']?></td><td><?=htmlspecialchars($c['name'])?></td><td><?=htmlspecialchars($c['email'])?></td><td><?=htmlspecialchars($c['contact'])?></td><td><?=htmlspecialchars($c['address'])?></td><td><span class="badge"><?=htmlspecialchars($c['status'])?></span></td></tr><?php endforeach;?></table></div></section>
<section class="module" id="stock"><h2>Restock Board</h2><div class="cards"><?php foreach($lowStock as $p):?><article class="card"><h3><?=htmlspecialchars($p['product_name'])?></h3><p><?=htmlspecialchars($p['sku'])?></p><span class="badge low">Stock <?=$p['stock_qty']?> / Reorder <?=$p['reorder_level']?></span></article><?php endforeach;?><?php if(!$lowStock):?><p>All products are above reorder level.</p><?php endif;?></div></section>
</main></body></html>