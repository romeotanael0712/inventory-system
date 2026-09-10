<?php
ob_start();
header('Content-Type: application/json');
session_start();

require_once 'db.php';

// Session Protection
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

$action = $_GET['action'] ?? '';

// Helper function para kumuha ng JSON input
function getJsonInput() {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}

// 1. FETCH ALL ITEMS
if ($action === 'fetch_items') {
    $search = $_GET['search'] ?? '';
    try {
        if ($search !== '') {
            $stmt = $pdo->prepare("SELECT * FROM items WHERE name ILIKE ? ORDER BY id DESC");
            $stmt->execute(["%$search%"]);
        } else {
            $stmt = $pdo->query("SELECT * FROM items ORDER BY id DESC");
        }
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// 2. ADD ITEM (With Duplicate & Non-Negative Validation)
if ($action === 'add_item') {
    $input = getJsonInput();
    $name = trim($input['name'] ?? '');
    $quantity = (int)($input['quantity'] ?? 0);
    $price = (float)($input['price'] ?? 0);

    if (empty($name)) {
        echo json_encode(['status' => 'error', 'message' => 'Pakilagay ang pangalan ng item!']);
        exit;
    }

    // Validation: Bawal ang negatibong numero
    if ($quantity < 0 || $price < 0) {
        echo json_encode(['status' => 'error', 'message' => 'Bawal ang negatibong halaga sa Quantity o Price!']);
        exit;
    }

    try {
        // Validation: Duplicate Check (Case-Insensitive)
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM items WHERE LOWER(name) = LOWER(?)");
        $checkStmt->execute([$name]);
        if ($checkStmt->fetchColumn() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Ang item na ito ay umiiral na sa inventory!']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO items (name, quantity, price) VALUES (?, ?, ?)");
        $stmt->execute([$name, $quantity, $price]);
        echo json_encode(['status' => 'success', 'message' => 'Item successfully added!']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// 3. UPDATE ITEM (With Duplicate & Non-Negative Validation)
if ($action === 'update_item') {
    $input = getJsonInput();
    $id = (int)($input['id'] ?? 0);
    $name = trim($input['name'] ?? '');
    $quantity = (int)($input['quantity'] ?? 0);
    $price = (float)($input['price'] ?? 0);

    if ($id <= 0 || empty($name)) {
        echo json_encode(['status' => 'error', 'message' => 'Kailangan ng valid ID at name!']);
        exit;
    }

    // Validation: Bawal ang negatibong numero
    if ($quantity < 0 || $price < 0) {
        echo json_encode(['status' => 'error', 'message' => 'Bawal ang negatibong halaga sa Quantity o Price!']);
        exit;
    }

    try {
        // Validation: Duplicate Check sa ibang items
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM items WHERE LOWER(name) = LOWER(?) AND id != ?");
        $checkStmt->execute([$name, $id]);
        if ($checkStmt->fetchColumn() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'May ibang item na gumagamit ng pangalang ito!']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE items SET name = ?, quantity = ?, price = ? WHERE id = ?");
        $stmt->execute([$name, $quantity, $price, $id]);
        echo json_encode(['status' => 'success', 'message' => 'Item successfully updated!']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// 4. DELETE ITEM
if ($action === 'delete_item') {
    $input = getJsonInput();
    $id = (int)($input['id'] ?? 0);

    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid ID!']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['status' => 'success', 'message' => 'Item deleted successfully!']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action!']);
exit;