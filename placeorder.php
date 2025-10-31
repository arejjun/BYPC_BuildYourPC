    <?php
    session_start();
    include "configuration/db.php";

    if (!isset($_SESSION['user_id'])) {
        header("Location: SignupandLogin/login.php");
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $message = '';
    $success = false;

    // Handle order placement (for both COD and Online Payment)
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
        $delivery_address = trim($_POST['delivery_address']);
        $pincode = trim($_POST['pincode']);
        $phone = trim($_POST['phone']);
        $payment_method = $_POST['payment_method'] ?? '';

        if (!empty($delivery_address) && !empty($pincode) && !empty($phone) && !empty($payment_method)) {
            $full_address = $delivery_address . ", Pincode: " . $pincode . ", Phone: " . $phone;

            // Get cart items - FRESH QUERY for order placement
            $cartQuery = "
                SELECT c.product_id, c.quantity, p.price, p.shop_id, p.name
                FROM cart c
                JOIN products p ON c.product_id = p.product_id
                WHERE c.user_id = ? AND p.availability = 1
            ";
            $cartStmt = $conn->prepare($cartQuery);
            $cartStmt->bind_param("i", $user_id);
            $cartStmt->execute();
            $cartResult = $cartStmt->get_result();

            if ($cartResult->num_rows > 0) {
                // Group items by shop_id
                $shopOrders = [];
                while ($item = $cartResult->fetch_assoc()) {
                    $shop_id = $item['shop_id'];
                    if (!isset($shopOrders[$shop_id])) {
                        $shopOrders[$shop_id] = [
                            'total' => 0,
                            'items' => []
                        ];
                    }
                    $itemTotal = $item['price'] * $item['quantity'];
                    $shopOrders[$shop_id]['total'] += $itemTotal;
                    $shopOrders[$shop_id]['items'][] = $item;
                }
                
                // Close this statement
                $cartStmt->close();

                $conn->begin_transaction();
                try {
                    $allOrdersSuccess = true;

                    // Create separate orders for each shop
                    foreach ($shopOrders as $shop_id => $orderData) {
                        // Insert order with payment method
                        $insertOrderStmt = $conn->prepare("
                            INSERT INTO orders (customer_id, shop_id, delivery_address, status, total_amount, created_at)
                            VALUES (?, ?, ?, 'pending', ?, NOW())
                        ");
                        $insertOrderStmt->bind_param("iisd", $user_id, $shop_id, $full_address, $orderData['total']);

                        if ($insertOrderStmt->execute()) {
                            $order_id = $conn->insert_id;

                            // Insert order items
                            foreach ($orderData['items'] as $item) {
                                $insertItemStmt = $conn->prepare("
                                    INSERT INTO order_items (order_id, product_id, quantity, price)
                                    VALUES (?, ?, ?, ?)
                                ");
                                $insertItemStmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);

                                if (!$insertItemStmt->execute()) {
                                    $allOrdersSuccess = false;
                                    $insertItemStmt->close();
                                    break 2;
                                }
                                $insertItemStmt->close();
                            }
                            $insertOrderStmt->close();
                        } else {
                            $allOrdersSuccess = false;
                            $insertOrderStmt->close();
                            break;
                        }
                    }

                    if ($allOrdersSuccess) {
                        // Clear the cart
                        $clearCartStmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
                        $clearCartStmt->bind_param("i", $user_id);
                        $clearCartStmt->execute();
                        $clearCartStmt->close();

                        $conn->commit();
                        $success = true;
                        
                        if ($payment_method === 'cod') {
                            $message = "Order placed successfully with Cash on Delivery! You will be redirected to order tracker.";
                        } else {
                            $message = "Payment successful! Order placed. You will be redirected to order tracker.";
                        }
                    } else {
                        $conn->rollback();
                        $message = "Error placing order. Please try again.";
                    }
                } catch (Exception $e) {
                    $conn->rollback();
                    $message = "Error placing order: " . $e->getMessage();
                }
            } else {
                $message = "Your cart is empty or contains unavailable items.";
                $cartStmt->close();
            }
        } else {
            $message = "Please fill in all delivery details and select a payment method.";
        }
    }

    // Get current cart items for display - SEPARATE FRESH QUERY
    $cartItems = null;
    $grandTotal = 0;
    $itemCount = 0;

    // Only fetch cart if order wasn't just placed successfully
    if (!$success) {
       $displayCartQuery = "
    SELECT
        c.product_id,
        c.quantity,
        p.name,
        p.price,
        p.category,
        pi.image_path,
        s.shop_name
    FROM cart c
    JOIN products p ON c.product_id = p.product_id
    JOIN shops s ON p.shop_id = s.shop_id
    LEFT JOIN (
        SELECT product_id, image_path
        FROM product_images
        GROUP BY product_id
    ) pi ON p.product_id = pi.product_id
    WHERE c.user_id = ? AND p.availability = 1
    ORDER BY s.shop_name, p.name
";

        $displayCartStmt = $conn->prepare($displayCartQuery);
        $displayCartStmt->bind_param("i", $user_id);
        $displayCartStmt->execute();
        $cartItems = $displayCartStmt->get_result();
    }
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Place Order - Build Your PC</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                padding-top: 100px;
            }

            .container {
                max-width: 1000px;
                margin: 0 auto;
                padding: 2rem;
            }

            .page-header {
                text-align: center;
                margin-bottom: 3rem;
            }

            .page-title {
                color: white;
                font-size: 2.5rem;
                font-weight: 700;
                margin-bottom: 0.5rem;
            }

            .page-subtitle {
                color: rgba(255, 255, 255, 0.8);
                font-size: 1.1rem;
            }

            .message {
                padding: 1rem;
                border-radius: 15px;
                margin-bottom: 2rem;
                text-align: center;
                backdrop-filter: blur(10px);
            }

            .message.success {
                background: rgba(0, 200, 200, 0.1);
                border: 1px solid rgba(0, 200, 200, 0.3);
                color: #00c8c8;
            }

            .message.error {
                background: rgba(244, 67, 54, 0.1);
                border: 1px solid rgba(244, 67, 54, 0.3);
                color: #f44336;
            }

            .checkout-container {
                display: grid;
                grid-template-columns: 1fr 400px;
                gap: 2rem;
            }

            .order-summary {
                background: rgba(255, 255, 255, 0.1);
                backdrop-filter: blur(20px);
                border: 1px solid rgba(255, 255, 255, 0.2);
                border-radius: 20px;
                padding: 2rem;
            }

            .checkout-form {
                background: rgba(255, 255, 255, 0.1);
                backdrop-filter: blur(20px);
                border: 1px solid rgba(255, 255, 255, 0.2);
                border-radius: 20px;
                padding: 2rem;
            }

            .section-title {
                color: white;
                font-size: 1.5rem;
                font-weight: 600;
                margin-bottom: 1.5rem;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }

            .cart-items {
                margin-bottom: 2rem;
            }

            .cart-item {
                display: flex;
                align-items: center;
                gap: 1rem;
                padding: 1rem;
                background: rgba(255, 255, 255, 0.05);
                border-radius: 15px;
                margin-bottom: 1rem;
                border: 1px solid rgba(255, 255, 255, 0.1);
            }

            .item-image {
                width: 60px;
                height: 60px;
                border-radius: 10px;
                object-fit: cover;
                background: rgba(255, 255, 255, 0.1);
                display: flex;
                align-items: center;
                justify-content: center;
                color: rgba(255, 255, 255, 0.5);
            }

            .item-details {
                flex: 1;
                color: white;
            }

            .item-name {
                font-weight: 600;
                margin-bottom: 0.25rem;
            }

            .item-meta {
                font-size: 0.9rem;
                opacity: 0.7;
                margin-bottom: 0.25rem;
            }

            .item-price {
                font-weight: 600;
                color: #00c8c8;
            }

            .total-section {
                border-top: 1px solid rgba(255, 255, 255, 0.2);
                padding-top: 1rem;
                color: white;
            }

            .total-row {
                display: flex;
                justify-content: space-between;
                margin-bottom: 0.5rem;
            }

            .grand-total {
                font-size: 1.2rem;
                font-weight: 700;
                color: #00c8c8;
                border-top: 1px solid rgba(255, 255, 255, 0.2);
                padding-top: 0.5rem;
            }

            .form-group {
                margin-bottom: 1.5rem;
            }

            .form-group label {
                display: block;
                color: white;
                font-weight: 500;
                margin-bottom: 0.5rem;
            }

            .form-group input,
            .form-group textarea {
                width: 100%;
                padding: 1rem;
                border: 1px solid rgba(255, 255, 255, 0.3);
                border-radius: 10px;
                background: rgba(255, 255, 255, 0.1);
                color: white;
                font-family: inherit;
                backdrop-filter: blur(10px);
            }

            .form-group input::placeholder,
            .form-group textarea::placeholder {
                color: rgba(255, 255, 255, 0.6);
            }

            .form-group textarea {
                resize: vertical;
                min-height: 100px;
            }

            .payment-methods {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 1rem;
                margin-bottom: 1.5rem;
            }

            .payment-option {
                position: relative;
            }

            .payment-option input[type="radio"] {
                position: absolute;
                opacity: 0;
            }

            .payment-option label {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 1.5rem 1rem;
                background: rgba(255, 255, 255, 0.05);
                border: 2px solid rgba(255, 255, 255, 0.2);
                border-radius: 15px;
                cursor: pointer;
                transition: all 0.3s ease;
                text-align: center;
            }

            .payment-option input[type="radio"]:checked + label {
                background: rgba(0, 200, 200, 0.1);
                border-color: #00c8c8;
                transform: translateY(-2px);
                box-shadow: 0 5px 20px rgba(0, 200, 200, 0.2);
            }

            .payment-option label i {
                font-size: 2rem;
                margin-bottom: 0.5rem;
                color: #00c8c8;
            }

            .payment-option label .payment-name {
                color: white;
                font-weight: 600;
                font-size: 1rem;
                margin-bottom: 0.25rem;
            }

            .payment-option label .payment-desc {
                color: rgba(255, 255, 255, 0.7);
                font-size: 0.85rem;
            }

            .place-order-btn {
                width: 100%;
                background: linear-gradient(135deg, #00c8c8, #00a8a8);
                color: white;
                padding: 1rem;
                border: none;
                border-radius: 25px;
                font-size: 1.1rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
            }

            .place-order-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(0, 200, 200, 0.3);
            }

            .place-order-btn:disabled {
                background: #6c757d;
                cursor: not-allowed;
                transform: none;
                box-shadow: none;
            }

            .empty-cart {
                text-align: center;
                color: white;
                padding: 3rem;
            }

            .empty-icon {
                font-size: 4rem;
                opacity: 0.3;
                margin-bottom: 1rem;
            }

            .back-link {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                color: white;
                text-decoration: none;
                margin-bottom: 2rem;
                padding: 0.5rem 1rem;
                border-radius: 25px;
                background: rgba(255, 255, 255, 0.1);
                transition: all 0.3s ease;
            }

            .back-link:hover {
                background: rgba(255, 255, 255, 0.2);
                transform: translateX(-5px);
            }

            .payment-modal {
                display: none;
                position: fixed;
                z-index: 9999;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.8);
                backdrop-filter: blur(10px);
                align-items: center;
                justify-content: center;
            }

            .payment-modal.active {
                display: flex;
            }

            .payment-content {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                padding: 2.5rem;
                border-radius: 20px;
                max-width: 500px;
                width: 90%;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
                animation: slideDown 0.3s ease;
            }

            @keyframes slideDown {
                from {
                    opacity: 0;
                    transform: translateY(-50px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .payment-header {
                text-align: center;
                margin-bottom: 2rem;
            }

            .payment-title {
                color: white;
                font-size: 1.8rem;
                font-weight: 700;
                margin-bottom: 0.5rem;
            }

            .payment-subtitle {
                color: rgba(255, 255, 255, 0.8);
                font-size: 0.95rem;
            }

            .payment-amount {
                background: rgba(255, 255, 255, 0.1);
                padding: 1rem;
                border-radius: 15px;
                text-align: center;
                margin-bottom: 2rem;
                border: 1px solid rgba(255, 255, 255, 0.2);
            }

            .payment-amount-label {
                color: rgba(255, 255, 255, 0.8);
                font-size: 0.9rem;
                margin-bottom: 0.5rem;
            }

            .payment-amount-value {
                color: #00c8c8;
                font-size: 2rem;
                font-weight: 700;
            }

            .payment-form-group {
                margin-bottom: 1.5rem;
            }

            .payment-form-group label {
                display: block;
                color: white;
                font-weight: 500;
                margin-bottom: 0.5rem;
                font-size: 0.9rem;
            }

            .payment-form-group input {
                width: 100%;
                padding: 1rem;
                border: 1px solid rgba(255, 255, 255, 0.3);
                border-radius: 10px;
                background: rgba(255, 255, 255, 0.1);
                color: white;
                font-family: inherit;
                backdrop-filter: blur(10px);
                font-size: 1rem;
            }

            .payment-form-group input::placeholder {
                color: rgba(255, 255, 255, 0.5);
            }

            .payment-row {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 1rem;
            }

            .pay-now-btn {
                width: 100%;
                background: linear-gradient(135deg, #00c8c8, #00a8a8);
                color: white;
                padding: 1rem;
                border: none;
                border-radius: 25px;
                font-size: 1.1rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                margin-top: 1rem;
            }

            .pay-now-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(0, 200, 200, 0.3);
            }

            .pay-now-btn:disabled {
                background: #6c757d;
                cursor: not-allowed;
                transform: none;
            }

            .cancel-payment-btn {
                width: 100%;
                background: rgba(255, 255, 255, 0.1);
                color: white;
                padding: 1rem;
                border: 1px solid rgba(255, 255, 255, 0.3);
                border-radius: 25px;
                font-size: 1rem;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.3s ease;
                margin-top: 0.5rem;
            }

            .cancel-payment-btn:hover {
                background: rgba(255, 255, 255, 0.2);
            }

            .payment-icons {
                display: flex;
                justify-content: center;
                gap: 1rem;
                margin-bottom: 1.5rem;
            }

            .payment-icon {
                font-size: 2rem;
                color: rgba(255, 255, 255, 0.6);
            }

            .processing {
                display: none;
                text-align: center;
                color: white;
            }

            .processing.active {
                display: block;
            }

            .spinner {
                border: 4px solid rgba(255, 255, 255, 0.2);
                border-top: 4px solid #00c8c8;
                border-radius: 50%;
                width: 50px;
                height: 50px;
                animation: spin 1s linear infinite;
                margin: 0 auto 1rem;
            }

            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }

            @media (max-width: 768px) {
                .container {
                    padding: 1rem;
                }

                .checkout-container {
                    grid-template-columns: 1fr;
                    gap: 1rem;
                }

                .page-title {
                    font-size: 2rem;
                }

                .cart-item {
                    flex-direction: column;
                    text-align: center;
                }

                .payment-content {
                    padding: 1.5rem;
                }

                .payment-row {
                    grid-template-columns: 1fr;
                }

                .payment-methods {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    </head>
    <body>
        <?php include 'components/navigation.php'; ?>

        <div class="container">
            <a href="cart.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Cart
            </a>

            <div class="page-header">
                <h1 class="page-title"><i class="fas fa-shopping-cart"></i> Place Order</h1>
                <p class="page-subtitle">Review your order and provide delivery details</p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="message <?php echo $success ? 'success' : 'error'; ?>">
                    <i class="fas fa-<?php echo $success ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <script>
                    setTimeout(function() {
                        window.location.href = 'ordertracker.php';
                    }, 3000);
                </script>
            <?php elseif ($cartItems && $cartItems->num_rows > 0): ?>
                <div class="checkout-container">
                    <div class="checkout-form">
                        <h3 class="section-title">
                            <i class="fas fa-map-marker-alt"></i> Delivery Information
                        </h3>

                        <form method="POST" action="" id="checkoutForm">
                            <div class="form-group">
                                <label for="delivery_address">Delivery Address *</label>
                                <textarea name="delivery_address" id="delivery_address" required
                                    placeholder="Enter your complete address (House/Flat No., Street, Area, City, State)"></textarea>
                            </div>

                            <div class="form-group">
                                <label for="pincode">Pincode *</label>
                                <input type="text" name="pincode" id="pincode" required
                                    placeholder="Enter your area pincode" pattern="[0-9]{6}">
                            </div>

                            <div class="form-group">
                                <label for="phone">Phone Number *</label>
                                <input type="tel" name="phone" id="phone" required
                                    placeholder="Enter your contact number" pattern="[0-9]{10}">
                            </div>

                            <div class="form-group">
                                <label>Select Payment Method *</label>
                                <div class="payment-methods">
                                    <div class="payment-option">
                                        <input type="radio" name="payment_method" id="online_payment" value="online" required>
                                        <label for="online_payment">
                                            <i class="fas fa-credit-card"></i>
                                            <span class="payment-name">Card Payment</span>
                                            <span class="payment-desc">Pay online securely</span>
                                        </label>
                                    </div>
                                    <div class="payment-option">
                                        <input type="radio" name="payment_method" id="cod_payment" value="cod" required>
                                        <label for="cod_payment">
                                            <i class="fas fa-money-bill-wave"></i>
                                            <span class="payment-name">Cash on Delivery</span>
                                            <span class="payment-desc">Pay when you receive</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" name="place_order" id="place_order_input" value="">

                            <button type="button" id="proceedToPayment" class="place-order-btn">
                                <i class="fas fa-arrow-right"></i> Continue
                            </button>
                        </form>
                    </div>

                    <div class="order-summary">
                        <h3 class="section-title">
                            <i class="fas fa-receipt"></i> Order Summary
                        </h3>

                        <div class="cart-items">
                            <?php
                            while ($item = $cartItems->fetch_assoc()):
                                $itemTotal = $item['price'] * $item['quantity'];
                                $grandTotal += $itemTotal;
                                $itemCount += $item['quantity'];
                            ?>
                                <div class="cart-item">
                                    <div class="item-image">
                                        <?php if (!empty($item['image_path']) && file_exists($item['image_path'])): ?>
                                            <img src="<?php echo htmlspecialchars($item['image_path']); ?>"
                                                alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                style="width: 60px; height: 60px; border-radius: 10px; object-fit: cover;">
                                        <?php else: ?>
                                            <i class="fas fa-cube"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="item-details">
                                        <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                        <div class="item-meta">
                                            <?php echo htmlspecialchars($item['category']); ?> •
                                            Shop: <?php echo htmlspecialchars($item['shop_name']); ?>
                                        </div>
                                        <div class="item-price">
                                            Qty: <?php echo $item['quantity']; ?> × &#8377;<?php echo number_format($item['price'], 2); ?>
                                            = &#8377;<?php echo number_format($itemTotal, 2); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>

                        <div class="total-section">
                            <div class="total-row">
                                <span>Items (<?php echo $itemCount; ?>):</span>
                                <span>&#8377; <?php echo number_format($grandTotal, 2); ?></span>
                            </div>
                            <div class="total-row">
                                <span>Shipping:</span>
                                <span>Free</span>
                            </div>
                            <div class="total-row grand-total">
                                <span>Total:</span>
                                <span>&#8377; <?php echo number_format($grandTotal, 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Modal -->
                <div id="paymentModal" class="payment-modal">
                    <div class="payment-content">
                        <div id="paymentFormSection">
                            <div class="payment-header">
                                <h2 class="payment-title"><i class="fas fa-credit-card"></i> Payment Details</h2>
                                <p class="payment-subtitle">Enter your card information (Demo Mode)</p>
                            </div>

                            <div class="payment-amount">
                                <div class="payment-amount-label">Total Amount to Pay</div>
                                <div class="payment-amount-value">&#8377; <?php echo number_format($grandTotal, 2); ?></div>
                            </div>

                            <div class="payment-icons">
                                <i class="fab fa-cc-visa payment-icon"></i>
                                <i class="fab fa-cc-mastercard payment-icon"></i>
                                <i class="fab fa-cc-amex payment-icon"></i>
                            </div>

                            <div class="payment-form-group">
                                <label for="card_number">Card Number</label>
                                <input type="text" id="card_number" placeholder="1234 5678 9012 3456" 
                                    maxlength="19" required>
                            </div>

                            <div class="payment-form-group">
                                <label for="card_holder">Card Holder Name</label>
                                <input type="text" id="card_holder" placeholder="JOHN DOE" required>
                            </div>

                            <div class="payment-row">
                                <div class="payment-form-group">
                                    <label for="expiry_date">Expiry Date</label>
                                    <input type="text" id="expiry_date" placeholder="MM/YY" 
                                        maxlength="5" required>
                                </div>

                                <div class="payment-form-group">
                                    <label for="cvv">CVV</label>
                                    <input type="text" id="cvv" placeholder="123" 
                                        maxlength="4" required>
                                </div>
                            </div>

                            <button type="button" id="payNowBtn" class="pay-now-btn">
                                <i class="fas fa-lock"></i> Pay Now
                            </button>

                            <button type="button" id="cancelPaymentBtn" class="cancel-payment-btn">
                                Cancel
                            </button>
                        </div>

                        <div id="processingSection" class="processing">
                            <div class="spinner"></div>
                            <h3>Processing Payment...</h3>
                            <p>Please wait while we process your payment securely</p>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <div class="empty-cart">
                    <div class="empty-icon"><i class="fas fa-shopping-cart"></i></div>
                    <h3>Your cart is empty</h3>
                    <p>Add some products to your cart before placing an order</p>
                    <a href="product.php" class="place-order-btn" style="width: auto; margin-top: 1rem;">
                        <i class="fas fa-shopping-bag"></i> Continue Shopping
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <script>
            const proceedToPaymentBtn = document.getElementById('proceedToPayment');
            const paymentModal = document.getElementById('paymentModal');
            const cancelPaymentBtn = document.getElementById('cancelPaymentBtn');
            const payNowBtn = document.getElementById('payNowBtn');
            const checkoutForm = document.getElementById('checkoutForm');
            const paymentFormSection = document.getElementById('paymentFormSection');
            const processingSection = document.getElementById('processingSection');

            // Card number formatting
            document.getElementById('card_number').addEventListener('input', function(e) {
                let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
                let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
                e.target.value = formattedValue;
            });

            // Expiry date formatting
            document.getElementById('expiry_date').addEventListener('input', function(e) {
                let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
                if (value.length >= 2) {
                    value = value.substring(0, 2) + '/' + value.substring(2, 4);
                }
                e.target.value = value;
            });

            // CVV validation
            document.getElementById('cvv').addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/[^0-9]/gi, '');
            });

            // Handle Continue button
            proceedToPaymentBtn.addEventListener('click', function() {
                if (checkoutForm.checkValidity()) {
                    const selectedPayment = document.querySelector('input[name="payment_method"]:checked');
                    
                    if (!selectedPayment) {
                        alert('Please select a payment method');
                        return;
                    }

                    if (selectedPayment.value === 'online') {
                        paymentModal.classList.add('active');
                        document.body.style.overflow = 'hidden';
                    } else if (selectedPayment.value === 'cod') {
                        if (confirm('Confirm Cash on Delivery order? You will pay when you receive the products.')) {
                            document.getElementById('place_order_input').value = '1';
                            checkoutForm.submit();
                        }
                    }
                } else {
                    checkoutForm.reportValidity();
                }
            });

            // Close payment modal
            cancelPaymentBtn.addEventListener('click', function() {
                paymentModal.classList.remove('active');
                document.body.style.overflow = 'auto';
                resetPaymentForm();
            });

            // Close modal on outside click
            paymentModal.addEventListener('click', function(e) {
                if (e.target === paymentModal) {
                    paymentModal.classList.remove('active');
                    document.body.style.overflow = 'auto';
                    resetPaymentForm();
                }
            });

            // Process payment
            payNowBtn.addEventListener('click', function() {
                const cardNumber = document.getElementById('card_number').value.replace(/\s+/g, '');
                const cardHolder = document.getElementById('card_holder').value.trim();
                const expiryDate = document.getElementById('expiry_date').value;
                const cvv = document.getElementById('cvv').value;

                // Basic validation
                if (!cardNumber || cardNumber.length < 13) {
                    alert('Please enter a valid card number');
                    return;
                }

                if (!cardHolder) {
                    alert('Please enter card holder name');
                    return;
                }

                if (!expiryDate || expiryDate.length !== 5) {
                    alert('Please enter valid expiry date (MM/YY)');
                    return;
                }

                if (!cvv || cvv.length < 3) {
                    alert('Please enter valid CVV');
                    return;
                }

                // Show processing
                paymentFormSection.style.display = 'none';
                processingSection.classList.add('active');
                payNowBtn.disabled = true;

                // Simulate payment processing
                setTimeout(function() {
                    document.getElementById('place_order_input').value = '1';
                    checkoutForm.submit();
                }, 2000);
            });

            function resetPaymentForm() {
                document.getElementById('card_number').value = '';
                document.getElementById('card_holder').value = '';
                document.getElementById('expiry_date').value = '';
                document.getElementById('cvv').value = '';
                paymentFormSection.style.display = 'block';
                processingSection.classList.remove('active');
                payNowBtn.disabled = false;
            }
        </script>
    </body>
    </html>
    <?php
    // Close statement if exists
    if (isset($displayCartStmt)) {
        $displayCartStmt->close();
    }
    $conn->close();
    ?>