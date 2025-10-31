<?php
session_start();
include "configuration/db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: SignupandLogin/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

// Handle cart actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    if ($action == 'add' && isset($_GET['id'])) {
        $product_id = intval($_GET['id']);

        // Check if product exists
        $stmt = $conn->prepare("SELECT product_id FROM products WHERE product_id = ? AND availability = 1");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Add to cart or update quantity
            $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE quantity = quantity + 1");
            $stmt->bind_param("ii", $user_id, $product_id);

            if ($stmt->execute()) {
                $message = "Product added to cart successfully!";
            } else {
                $message = "Error adding product to cart.";
            }
        }
    }

    if ($action == 'remove' && isset($_GET['id'])) {
        $product_id = intval($_GET['id']);
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $user_id, $product_id);

        if ($stmt->execute()) {
            $message = "Product removed from cart!";
        }
    }

    if ($action == 'update' && isset($_POST['product_id']) && isset($_POST['quantity'])) {
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']);

        if ($quantity > 0) {
            $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("iii", $quantity, $user_id, $product_id);
            if ($stmt->execute()) {
                // If this is an AJAX call, return a simple JSON success
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    echo json_encode(['success' => true]);
                    exit;
                }
            }
        } else {
            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $user_id, $product_id);
            $stmt->execute();
        }
        $message = "Cart updated successfully!";
    }
}

// Get cart items (one image per product using subquery)
$stmt = $conn->prepare("
    SELECT c.*, p.name, p.price, p.brand, p.category, pi.image_path, s.shop_name
    FROM cart c
    JOIN products p ON c.product_id = p.product_id
    LEFT JOIN (
        SELECT product_id, MIN(image_path) AS image_path
        FROM product_images
        GROUP BY product_id
    ) pi ON p.product_id = pi.product_id
    JOIN shops s ON p.shop_id = s.shop_id
    WHERE c.user_id = ?
    GROUP BY c.cart_id
    ORDER BY c.added_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_items = $stmt->get_result();

// Calculate total
$total = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - BYPC</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #e3f2fd 0%, #f8f9ff 100%);
            color: #2c3e50;
            line-height: 1.6;
            min-height: 100vh;
            padding-top: 80px;
        }
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(0, 200, 200, 0.1);
            z-index: 1000;
            transition: all 0.3s ease;
        }
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
        }
        .logo { font-size: 1.8rem; font-weight: 800; color: #00c8c8; text-decoration: none; }
        .nav-links { display: flex; list-style: none; gap: 2rem; align-items: center; }
        .nav-links a { color: #1a1a1a; text-decoration: none; font-weight: 500; transition: color 0.3s ease; }
        .nav-links a:hover { color: #00c8c8; }
        .user-dropdown { position: relative; }
        .user-btn {
            background: linear-gradient(135deg, #00c8c8, #00a8a8);
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .dropdown-content { display: none; position: absolute; right: 0; background: white; min-width: 180px; box-shadow: 0 8px 32px rgba(0, 200, 200, 0.2); border-radius: 15px; z-index: 1002; padding: 0.5rem 0; top: calc(100% + 1rem); }
        .user-dropdown:hover .dropdown-content { display: block; }
        .dropdown-content a { color: #1a1a1a; padding: 1rem 1.5rem; text-decoration: none; display: block; transition: background 0.3s ease; }
        .dropdown-content a:hover { background: rgba(0, 200, 200, 0.1); color: #00c8c8; }

        .container { max-width: 1200px; margin: 2rem auto; padding: 0 2rem; }
        .cart-header { text-align: center; margin-bottom: 3rem; }
        .cart-header h1 { font-size: 2.5rem; font-weight: 800; color: #2c3e50; margin-bottom: 0.5rem; }
        .cart-header p { font-size: 1.1rem; color: #666; }
        .message { background: #d4edda; color: #155724; padding: 1rem; border-radius: 10px; margin-bottom: 2rem; text-align: center; font-weight: 600; }
        .cart-content { display: grid; grid-template-columns: 1fr 350px; gap: 3rem; }
        .cart-items { background: white; border-radius: 20px; box-shadow: 0 10px 40px rgba(0, 200, 200, 0.1); overflow: hidden; }
        .cart-item {
            display: grid; grid-template-columns: 120px 1fr auto auto; gap: 1.5rem; padding: 2rem; border-bottom: 1px solid #eee; align-items: center; transition: background 0.3s ease;
        }
        .cart-item:hover { background: rgba(0, 200, 200, 0.02); }
        .product-image { width: 100px; height: 100px; border-radius: 15px; object-fit: cover; background: linear-gradient(135deg, #00c8c8, #00a8a8); display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem; }
        .product-details { flex-grow: 1; }
        .product-name { font-size: 1.3rem; font-weight: 700; color: #2c3e50; margin-bottom: 0.5rem; }
        .product-info { color: #666; margin-bottom: 0.5rem; }
        .product-price { font-size: 1.2rem; font-weight: 700; color: #00c8c8; }
        .quantity-controls { display: flex; align-items: center; gap: 1rem; background: #f8f9fa; padding: 0.5rem; border-radius: 10px; }
        .quantity-btn { background: #00c8c8; color: white; border: none; width: 35px; height: 35px; border-radius: 50%; cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease; }
        .quantity-btn:hover { background: #00a8a8; transform: scale(1.1); }
        .quantity-input { width: 60px; text-align: center; border: none; padding: 0.5rem; border-radius: 5px; font-weight: 600; }
        .remove-btn { background: #ff4757; color: white; border: none; padding: 0.75rem 1rem; border-radius: 10px; cursor: pointer; font-weight: 600; transition: all 0.3s ease; display: flex; align-items: center; gap: 0.5rem; }
        .remove-btn:hover { background: #ff3742; transform: translateY(-2px); }
        .cart-summary { background: white; border-radius: 20px; box-shadow: 0 10px 40px rgba(0, 200, 200, 0.1); padding: 2rem; height: fit-content; position: sticky; top: 100px; }
        .summary-title { font-size: 1.5rem; font-weight: 700; color: #2c3e50; margin-bottom: 1.5rem; text-align: center; }
        .summary-item { display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid #eee; }
        .summary-item:last-child { border-bottom: none; font-weight: 700; font-size: 1.2rem; color: #00c8c8; margin-top: 1rem; padding-top: 1rem; border-top: 2px solid #00c8c8; }
        .checkout-btn { width: 100%; background: linear-gradient(135deg, #00c8c8, #00a8a8); color: white; border: none; padding: 1rem 2rem; border-radius: 15px; font-size: 1.1rem; font-weight: 700; cursor: pointer; margin-top: 2rem; transition: all 0.3s ease; text-transform: uppercase; letter-spacing: 1px; }
        .checkout-btn:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0, 200, 200, 0.4); }
        .continue-shopping { display: inline-block; background: #6c757d; color: white; padding: 1rem 2rem; border-radius: 15px; text-decoration: none; font-weight: 600; margin-top: 1rem; transition: all 0.3s ease; }
        .continue-shopping:hover { background: #5a6268; transform: translateY(-2px); }
        .empty-cart { text-align: center; padding: 4rem 2rem; background: white; border-radius: 20px; box-shadow: 0 10px 40px rgba(0, 200, 200, 0.1); }
        .empty-cart-icon { font-size: 4rem; color: #ddd; margin-bottom: 1rem; }
        .empty-cart h3 { font-size: 1.8rem; color: #666; margin-bottom: 1rem; }
        .empty-cart p { color: #999; margin-bottom: 2rem; }

        @media (max-width: 768px) {
            .cart-content { grid-template-columns: 1fr; gap: 2rem; }
            .cart-item { grid-template-columns: 80px 1fr; gap: 1rem; }
            .quantity-controls { grid-column: 1 / -1; justify-self: start; margin-top: 1rem; }
            .remove-btn { grid-column: 1 / -1; justify-self: end; margin-top: 0.5rem; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="Mainpage.php" class="logo">Build Your PC</a>
            <ul class="nav-links">
                <li><a href="Mainpage.php">Home</a></li>
                <li><a href="product.php">Products</a></li>
                <li><a href="Roadmap.html">Cloud Build</a></li>
            </ul>
            <div class="auth-section">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="user-dropdown">
                        <div class="user-btn">
                            <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                            <span>▼</span>
                        </div>
                        <div class="dropdown-content">
                            <a href="profile.php">My Profile</a>
                            <a href="ordertracker.php">My Orders</a>
                            <a href="cart.php">My Cart</a>
                            <a href="Mainpage.php?logout=1">Logout</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="cart-header">
            <h1><i class="fas fa-shopping-cart"></i> Shopping Cart</h1>
            <p>Review your selected items before checkout</p>
        </div>

        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($cart_items->num_rows > 0): ?>
            <div class="cart-content">
                <div class="cart-items">
                    <?php while ($item = $cart_items->fetch_assoc()):
                        $subtotal = $item['price'] * $item['quantity'];
                        $total += $subtotal;
                    ?>
                        <div class="cart-item">
                            <div class="product-image">
                                <?php if (!empty($item['image_path'])): ?>
                                    <img src="uploads/<?php echo htmlspecialchars($item['image_path']); ?>" alt="Product" style="width:100%; height:100%; border-radius:12px; object-fit:cover;">
                                <?php else: ?>
                                    <i class="fas fa-desktop"></i>
                                <?php endif; ?>
                            </div>

                            <div class="product-details">
                                <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="product-info">
                                    <span class="brand"><?php echo htmlspecialchars($item['brand']); ?></span> •
                                    <span class="category"><?php echo htmlspecialchars($item['category']); ?></span><br>
                                    <span class="shop">Sold by: <?php echo htmlspecialchars($item['shop_name']); ?></span>
                                </div>
                                <div class="product-price">₹<?php echo number_format($item['price'], 2); ?></div>
                            </div>

                            <div class="quantity-controls">
                                <button type="button" class="quantity-btn" onclick="updateQuantity(this, <?php echo $item['product_id']; ?>, -1)">
                                    <i class="fas fa-minus"></i>
                                </button>

<input type="number" name="quantity" value="<?php echo (int)$item['quantity']; ?>" min="1" max="10" class="quantity-input" data-product-id="<?php echo $item['product_id']; ?>">

                                <button type="button" class="quantity-btn" onclick="updateQuantity(this, <?php echo $item['product_id']; ?>, 1)">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>

                            <a href="cart.php?action=remove&id=<?php echo $item['product_id']; ?>" class="remove-btn" onclick="return confirm('Remove this item from cart?')">
                                <i class="fas fa-trash"></i> Remove
                            </a>
                        </div>
                    <?php endwhile; ?>
                </div>

                <div class="cart-summary">
                    <h3 class="summary-title">Order Summary</h3>

                    <div class="summary-item">
                        <span>Items (<?php echo $cart_items->num_rows; ?>)</span>
                        <span>₹<?php echo number_format($total, 2); ?></span>
                    </div>

                    <div class="summary-item">
                        <span>Shipping</span>
                        <span>FREE</span>
                    </div>

                    <div class="summary-item">
                        <span>Total</span>
                        <span>₹<?php echo number_format($total, 2); ?></span>
                    </div>

                    <button class="checkout-btn" onclick="proceedToCheckout()">
                        <i class="fas fa-lock"></i> Proceed to Checkout
                    </button>

                    <a href="product.php" class="continue-shopping">
                        <i class="fas fa-arrow-left"></i> Continue Shopping
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-cart">
                <div class="empty-cart-icon"><i class="fas fa-shopping-cart"></i></div>
                <h3>Your cart is empty</h3>
                <p>Looks like you haven't added any products yet.</p>
                <a href="product.php" class="continue-shopping"><i class="fas fa-shopping-bag"></i> Start Shopping</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
    // Debounce helper — prevents rapid duplicate calls
    function debounce(fn, delay) {
        let t;
        return function(...args) {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, args), delay);
        };
    }

    // Send quantity update to server (debounced)
    const sendUpdate = debounce(function(productId, quantity) {
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('quantity', quantity);

        fetch('cart.php?action=update', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json().catch(()=>({})))
        .then(data => {
            // reload only when server returns success (safer)
            if (data && data.success) {
                location.reload();
            } else {
                // fallback: reload to sync UI with server
                location.reload();
            }
        })
        .catch(err => {
            console.error('Cart update failed', err);
            alert('Could not update cart. Please try again.');
        });
    }, 200); // 200ms debounce

    // Update quantity using +/- buttons
    function updateQuantity(btn, productId, change) {
        // find the nearest quantity input inside same controls container
        const container = btn.closest('.quantity-controls');
        if (!container) return;

        const input = container.querySelector('input.quantity-input');
        if (!input) return;

        let current = parseInt(input.value, 10);
        if (isNaN(current)) current = 1;

        let newQuantity = current + change;
        if (newQuantity < 1) newQuantity = 1;
        if (newQuantity > 10) newQuantity = 10;

        // update DOM immediately for instant feedback
        input.value = newQuantity;

        // call debounced server update (only once)
        sendUpdate(productId, newQuantity);
    }

    // Wire change event for manual input edits (reads data-product-id)
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('change', function(e) {
            let val = parseInt(this.value, 10);
            if (isNaN(val) || val < 1) val = 1;
            if (val > 10) val = 10;
            this.value = val;

            const productId = this.dataset.productId;
            if (!productId) return;

            sendUpdate(productId, val);
        });
    });

    function proceedToCheckout() {
        <?php if ($cart_items->num_rows > 0): ?>
            window.location.href = 'placeorder.php';
        <?php else: ?>
            alert('Your cart is empty!');
        <?php endif; ?>
    }

    // Navbar scroll effect
    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.navbar');
        if (window.scrollY > 50) {
            navbar.style.background = 'rgba(255, 255, 255, 0.98)';
            navbar.style.boxShadow = '0 4px 20px rgba(0, 200, 200, 0.1)';
        } else {
            navbar.style.background = 'rgba(255, 255, 255, 0.95)';
            navbar.style.boxShadow = 'none';
        }
    });
</script>

</body>
</html>
