<?php
// Isama ang database connection file
require_once 'db.php';

$edit_mode = false;
$edit_id = '';
$edit_name = '';
$edit_quantity = '';
$edit_price = '';

// 1. ADD ITEM (Create)
if (isset($_POST['add'])) {
    $name = trim($_POST['name']);
    $quantity = (int)$_POST['quantity'];
    $price = (float)$_POST['price'];

    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO items (name, quantity, price) VALUES (?, ?, ?)");
        $stmt->execute([$name, $quantity, $price]);
        header("Location: index.php");
        exit;
    }
}

// 2. UPDATE ITEM (Update)
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $quantity = (int)$_POST['quantity'];
    $price = (float)$_POST['price'];

    if (!empty($name)) {
        $stmt = $pdo->prepare("UPDATE items SET name = ?, quantity = ?, price = ? WHERE id = ?");
        $stmt->execute([$name, $quantity, $price, $id]);
        header("Location: index.php");
        exit;
    }
}

// 3. EDIT TRIGGER
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
    $stmt->execute([$edit_id]);
    $item_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($item_to_edit) {
        $edit_mode = true;
        $edit_name = $item_to_edit['name'];
        $edit_quantity = $item_to_edit['quantity'];
        $edit_price = $item_to_edit['price'];
    }
}

// 4. DELETE ITEM (Delete)
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: index.php");
    exit;
}

// 5. FETCH ITEMS WITH SEARCH (Read & Filter)
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if (!empty($search)) {
    $stmt = $pdo->prepare("SELECT * FROM items WHERE name ILIKE ? ORDER BY id DESC");
    $stmt->execute(['%' . $search . '%']);
} else {
    $stmt = $pdo->query("SELECT * FROM items ORDER BY id DESC");
}
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>