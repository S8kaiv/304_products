<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>

<?php
require 'db.php';

$errors = [];
$success = '';

/*ADD PRODUCT*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $price = trim($_POST['price']);
    $stock = trim($_POST['stock']);
    $supplier = trim($_POST['supplier']);
    $description = trim($_POST['description']);

    if ($name === '') $errors[] = "Product name is required.";
    if ($category === '') $errors[] = "Category is required.";
    if (!is_numeric($price) || $price <= 0) $errors[] = "Enter a valid price.";
    if (!ctype_digit($stock)) $errors[] = "Enter a valid stock number.";
    if ($supplier === '') $errors[] = "Supplier is required.";

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO products (name, category, price, stock, supplier, description) 
                               VALUES (:name, :category, :price, :stock, :supplier, :description)");
        $stmt->execute([
            ':name' => $name,
            ':category' => $category,
            ':price' => $price,
            ':stock' => $stock,
            ':supplier' => $supplier,
            ':description' => $description
        ]);
        header("Location: index.php?success=add");
        exit;
    }
}

/*UPDATE PRODUCT*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $id = $_POST['id'];
    $stmt = $pdo->prepare("UPDATE products SET name=?, category=?, price=?, stock=?, supplier=?, description=? WHERE id=?");
    $stmt->execute([
        $_POST['name'], $_POST['category'], $_POST['price'],
        $_POST['stock'], $_POST['supplier'], $_POST['description'], $id
    ]);
    header("Location: index.php?success=update");
    exit;
}

/*DELETE PRODUCT*/
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: index.php?success=delete");
    exit;
}

$products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product Inventory Management</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0; top: 0;
            width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.5);
            justify-content: center; align-items: center;
        }
        .modal-content {
            background: #fff;
            padding: 25px;
            border-radius: 15px;
            width: 400px;
            box-shadow: 0 5px 30px rgba(0,0,0,0.3);
            text-align: left;
            animation: popIn 0.3s ease;
        }
        @keyframes popIn {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        .modal-close {
            float: right;
            font-size: 20px;
            color: #555;
            cursor: pointer;
        }
        .modal-close:hover { color: #000; }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="nav-left">
            <h2>Product Management</h2>
        </div>

        <button class="nav-toggle" onclick="toggleMenu()">☰</button>

        <div class="nav-right" id="navMenu">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </nav>

    <script>
        function toggleMenu() {
            document.getElementById("navMenu").classList.toggle("show");
        }
    </script>

    <main>
        <?php if (isset($_GET['success'])): ?>
            <?php if ($_GET['success'] === 'add'): ?>
                <p class="alert success">Product added successfully!</p>
            <?php elseif ($_GET['success'] === 'update'): ?>
                <p class="alert success">Product updated successfully!</p>
            <?php elseif ($_GET['success'] === 'delete'): ?>
                <p class="alert danger">Product deleted successfully!</p>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert error">
                <ul>
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" class="form-card">
            <label>Product Name:</label>
            <input type="text" name="name" required>

            <label>Category:</label>
            <select name="category" required>
                <option value="">Select Category</option>
                <option value="Electronics">Electronics</option>
                <option value="Clothing">Clothing</option>
                <option value="Home & Kitchen">Home & Kitchen</option>
                <option value="Sports">Sports</option>
                <option value="Books">Books</option>
            </select>

            <label>Price:</label>
            <input type="number" step="0.01" name="price" required>

            <label>Stock:</label>
            <input type="number" name="stock" required>

            <label>Supplier:</label>
            <input type="text" name="supplier" required>

            <label>Description:</label>
            <textarea name="description" rows="3"></textarea>

            <button type="submit" name="add">Add Product</button>
        </form>

        <h2>Product List</h2>
        <?php if (empty($products)): ?>
            <p>No products available.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th><th>Name</th><th>Category</th><th>Price</th>
                        <th>Stock</th><th>Supplier</th><th>Description</th><th>Created At</th><th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td><?= $p['id'] ?></td>
                            <td><?= htmlspecialchars($p['name']) ?></td>
                            <td><?= htmlspecialchars($p['category']) ?></td>
                            <td>₱<?= number_format($p['price'], 2) ?></td>
                            <td><?= $p['stock'] ?></td>
                            <td><?= htmlspecialchars($p['supplier']) ?></td>
                            <td><?= htmlspecialchars($p['description']) ?></td>
                            <td><?= $p['created_at'] ?></td>
                            <td>
                                <a href="#" 
                                class="edit-btn" 
                                data-id="<?= $p['id'] ?>"
                                data-name="<?= htmlspecialchars($p['name']) ?>"
                                data-category="<?= htmlspecialchars($p['category']) ?>"
                                data-price="<?= $p['price'] ?>"
                                data-stock="<?= $p['stock'] ?>"
                                data-supplier="<?= htmlspecialchars($p['supplier']) ?>"
                                data-description="<?= htmlspecialchars($p['description']) ?>">✏️ Edit</a> |
                                <a href="index.php?delete=<?= $p['id'] ?>" class="delete" onclick="return confirm('Are you sure you want to delete this product?')">🗑 Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </main>

    <div class="modal" id="editModal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal()">&times;</span>
            <h3>Edit Product</h3>
            <form method="post" id="editForm">
                <input type="hidden" name="id" id="edit-id">

                <label>Product Name:</label>
                <input type="text" name="name" id="edit-name" required>

                <label>Category:</label>
                <select name="category" id="edit-category" required>
                    <option value="">Select Category</option>
                    <option value="Electronics">Electronics</option>
                    <option value="Clothing">Clothing</option>
                    <option value="Home & Kitchen">Home & Kitchen</option>
                    <option value="Sports">Sports</option>
                    <option value="Books">Books</option>
                </select>

                <label>Price:</label>
                <input type="number" step="0.01" name="price" id="edit-price" required>

                <label>Stock:</label>
                <input type="number" name="stock" id="edit-stock" required>

                <label>Supplier:</label>
                <input type="text" name="supplier" id="edit-supplier" required>

                <label>Description:</label>
                <textarea name="description" id="edit-description" rows="3"></textarea>

                <button type="submit" name="update">Update Product</button>
            </form>
        </div>
    </div>

    <script>

    const alerts = document.querySelectorAll('.alert.success, .alert.danger');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 3000);
    });

    if (window.location.search.includes('success')) {
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    const modal = document.getElementById('editModal');
    const editCategory = document.getElementById('edit-category');

    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', e => {
            e.preventDefault();

            document.getElementById('edit-id').value = btn.dataset.id;
            document.getElementById('edit-name').value = btn.dataset.name;
            document.getElementById('edit-price').value = btn.dataset.price;
            document.getElementById('edit-stock').value = btn.dataset.stock;
            document.getElementById('edit-supplier').value = btn.dataset.supplier;
            document.getElementById('edit-description').value = btn.dataset.description;

            Array.from(editCategory.options).forEach(opt => {
                opt.selected = (opt.value === btn.dataset.category);
            });

            modal.style.display = 'flex';
        });
    });

    function closeModal() {
        modal.style.display = 'none';
    }

    window.onclick = function(e) {
        if (e.target == modal) closeModal();
    };
    </script>

</body>
</html>
