<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management System</title>
    <!-- Local CSS & SweetAlert2 JS (Offline Ready) -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <script src="js/sweetalert2.all.min.js"></script>
</head>
<body class="bg-light p-4">

<div class="container bg-white p-4 rounded shadow-sm" style="max-width: 900px;">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="m-0 text-primary fw-bold">Inventory Management System</h2>
            <p class="m-0 text-muted">Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>!</p>
        </div>
        <a href="logout.php" class="btn btn-danger btn-sm fw-bold">Logout</a>
    </div>

    <!-- Add Item Form -->
    <div class="card mb-4 border-0 bg-light">
        <div class="card-body">
            <h5 class="card-title mb-3 text-secondary">Add New Item</h5>
            <form id="add-form" class="row g-2">
                <div class="col-md-5">
                    <input type="text" id="item-name" class="form-control" placeholder="e.g. Suka" required>
                </div>
                <div class="col-md-3">
                    <input type="number" id="item-qty" class="form-control" placeholder="Quantity" value="0" min="0" required>
                </div>
                <div class="col-md-4">
                    <input type="number" step="0.01" id="item-price" class="form-control" placeholder="Price (₱)" value="0.00" min="0" required>
                </div>
                <div class="col-12 text-end mt-3">
                    <button type="submit" class="btn btn-primary px-4 fw-bold">Add Item</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Search Box -->
    <div class="row mb-3">
        <div class="col-md-6 ms-auto">
            <input type="text" id="search-input" class="form-control" placeholder="Search item by name...">
        </div>
    </div>

    <!-- Inventory Table -->
    <table class="table table-bordered table-hover align-middle">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Item Name</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Date Added</th>
                <th style="width: 150px;">Action</th>
            </tr>
        </thead>
        <tbody id="inventory-table-body">
            <!-- Data loaded via AJAX -->
        </tbody>
    </table>
</div>

<!-- Edit Item Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Edit Item</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="edit-form">
          <div class="modal-body">
            <input type="hidden" id="edit-id">
            <div class="mb-3">
                <label class="form-label font-weight-bold">Item Name</label>
                <input type="text" id="edit-name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Quantity</label>
                <input type="number" id="edit-qty" class="form-control" min="0" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Price (₱)</label>
                <input type="number" step="0.01" id="edit-price" class="form-control" min="0" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-warning fw-bold">Save Changes</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- Local Bootstrap JS -->
<script src="js/bootstrap.bundle.min.js"></script>

<script>
let editModal;

// SweetAlert2 Toast Setup
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 2500,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer);
        toast.addEventListener('mouseleave', Swal.resumeTimer);
    }
});

document.addEventListener("DOMContentLoaded", () => {
    editModal = new bootstrap.Modal(document.getElementById('editModal'));
    loadItems();
});

// Centralized Error Dialog Helper
function showFailedDialog(title, message) {
    Swal.fire({
        icon: 'error',
        title: title,
        text: message,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'OK'
    });
}

// Universal Safe Fetch Function (Handles Network Error, Offline, Bad Server Response)
async function safeFetch(url, options = {}) {
    if (!navigator.onLine) {
        showFailedDialog('Walang Internet Connection!', 'Hindi maiproseso ang request dahil offline ang iyong device.');
        return null;
    }

    try {
        const res = await fetch(url, options);

        if (!res.ok) {
            showFailedDialog('Server Error!', `Nagkaroon ng problema sa server (Status Code: ${res.status}).`);
            return null;
        }

        const result = await res.json().catch(() => null);
        if (!result) {
            showFailedDialog('Invalid Response!', 'May maling format o sirang data na ibinalik ang server.');
            return null;
        }

        return result;

    } catch (err) {
        showFailedDialog('Connection Failed!', 'Hindi maabot ang server. Posibleng nakapatay ang Docker/Backend Server.');
        return null;
    }
}

// 1. FETCH ITEMS
async function loadItems(query = '') {
    const result = await safeFetch(`api.php?action=fetch_items&search=${encodeURIComponent(query)}`);
    if (!result) return;

    if (result.status === 'success') {
        const tbody = document.getElementById('inventory-table-body');
        tbody.innerHTML = '';

        if (result.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No items found</td></tr>';
            return;
        }

        result.data.forEach(item => {
            tbody.innerHTML += `
                <tr>
                    <td>${item.id}</td>
                    <td><strong>${item.name}</strong></td>
                    <td><span class="badge bg-info text-dark">${item.quantity} pcs</span></td>
                    <td>₱${parseFloat(item.price).toFixed(2)}</td>
                    <td><small class="text-muted">${item.created_at || 'N/A'}</small></td>
                    <td>
                        <button onclick="openEditModal(${item.id}, '${item.name.replace(/'/g, "\\'")}', ${item.quantity}, ${item.price})" class="btn btn-outline-warning btn-sm me-1">Edit</button>
                        <button onclick="deleteItem(${item.id})" class="btn btn-outline-danger btn-sm">Delete</button>
                    </td>
                </tr>
            `;
        });
    } else {
        showFailedDialog('Error Loading Data', result.message || 'May nangyaring mali sa pag-load ng items.');
    }
}

// 2. ADD ITEM
document.getElementById('add-form').addEventListener('submit', async (e) => {
    e.preventDefault();

    const name = document.getElementById('item-name').value;
    const quantity = document.getElementById('item-qty').value;
    const price = document.getElementById('item-price').value;

    const result = await safeFetch('api.php?action=add_item', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, quantity, price })
    });

    if (!result) return;

    if (result.status === 'success') {
        document.getElementById('add-form').reset();
        loadItems();
        Toast.fire({ icon: 'success', title: result.message || 'Item added successfully!' });
    } else {
        showFailedDialog('Failed to Add Item', result.message || 'Hindi na-save ang bagong item.');
    }
});

// Open Edit Modal
function openEditModal(id, name, quantity, price) {
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-name').value = name;
    document.getElementById('edit-qty').value = quantity;
    document.getElementById('edit-price').value = price;
    editModal.show();
}

// 3. UPDATE ITEM
document.getElementById('edit-form').addEventListener('submit', async (e) => {
    e.preventDefault();

    const id = document.getElementById('edit-id').value;
    const name = document.getElementById('edit-name').value;
    const quantity = document.getElementById('edit-qty').value;
    const price = document.getElementById('edit-price').value;

    const result = await safeFetch('api.php?action=update_item', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, name, quantity, price })
    });

    if (!result) return;

    if (result.status === 'success') {
        editModal.hide();
        loadItems();
        Toast.fire({ icon: 'success', title: result.message || 'Item updated successfully!' });
    } else {
        showFailedDialog('Failed to Update Item', result.message || 'Hindi na-update ang item.');
    }
});

// 4. DELETE ITEM
async function deleteItem(id) {
    const confirm = await Swal.fire({
        title: 'Sigurado ka ba?',
        text: "Hindi mo na ito maibabalik kapag nabura!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Oo, burahin!'
    });

    if (confirm.isConfirmed) {
        const result = await safeFetch('api.php?action=delete_item', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });

        if (!result) return;

        if (result.status === 'success') {
            loadItems();
            Toast.fire({ icon: 'success', title: result.message || 'Item deleted successfully!' });
        } else {
            showFailedDialog('Failed to Delete Item', result.message || 'Hindi nabura ang item.');
        }
    }
}

// Real-time Search
document.getElementById('search-input').addEventListener('input', (e) => {
    loadItems(e.target.value);
});
</script>

</body>
</html>