<?php
session_start();
include "configuration/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: SignupandLogin/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

// Check if viewing specific order or order list
$view_order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : null;
$view_mode = $view_order_id ? 'single' : 'list';

// Handle order action requests
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $order_id = intval($_POST['order_id']);
    $action = $_POST['action'];
    $request_message = isset($_POST['message']) ? trim($_POST['message']) : '';

    if (in_array($action, ['cancel', 'return']) && !empty($request_message)) {
        // Check if request already exists
        $checkStmt = $conn->prepare("SELECT * FROM Order_Requests WHERE order_id = ? AND request_type = ? ORDER BY created_at DESC LIMIT 1");
        $checkStmt->bind_param("is", $order_id, $action);
        $checkStmt->execute();
        $existing = $checkStmt->get_result()->fetch_assoc();

        if (!$existing || $existing['status'] == 'rejected') {
            // Insert new request
            $insertStmt = $conn->prepare("INSERT INTO Order_Requests (order_id, customer_id, request_type, message, status) VALUES (?, ?, ?, ?, 'pending')");
            $insertStmt->bind_param("iiss", $order_id, $user_id, $action, $request_message);
            if ($insertStmt->execute()) {
                $message = ucfirst($action) . " request submitted successfully!";
            }
        }
    }
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review'])) {
    $order_id = intval($_POST['order_id']);
    $product_id = intval($_POST['product_id']);
    $rating = intval($_POST['rating']);
    $review_text = trim($_POST['review_text']);


    if ($rating >= 1 && $rating <= 5) {
        // Check if review already exists
        $checkReview = $conn->prepare("SELECT review_id FROM reviews WHERE product_id = ? AND user_id = ? AND order_id = ?");
        $checkReview->bind_param("iii", $product_id, $user_id, $order_id);
        $checkReview->execute();
        $existingReview = $checkReview->get_result()->fetch_assoc();

        if (!$existingReview) {
            // Insert new review
            $insertReview = $conn->prepare("INSERT INTO reviews (product_id, user_id, order_id, rating, review_text) VALUES (?, ?, ?, ?, ?)");
            $insertReview->bind_param("iiiis", $product_id, $user_id, $order_id, $rating, $review_text);
            if ($insertReview->execute()) {
                $message = "Review submitted successfully!";
            } else {
                $message = "Error submitting review. Please try again.";
            }
        } else {
            $message = "You have already reviewed this product for this order.";
        }
    } else {
        $message = "Invalid rating. Please select a rating between 1 and 5 stars.";
    }
}

// Fetch orders based on view mode
if ($view_mode === 'single') {
    // Fetch specific order details
    $orderQuery = "
        SELECT
            o.order_id,
            o.status,
            o.total_amount,
            o.delivery_address,
            o.created_at,
            s.shop_name,
            s.phone_number,
            GROUP_CONCAT(
                CONCAT(p.product_id, '|', p.name, '|', oi.quantity, '|', oi.price, '|', COALESCE(pi.image_path, ''))
                SEPARATOR ';;'
            ) as order_items
        FROM orders o
        JOIN shops s ON o.shop_id = s.shop_id
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.product_id
        LEFT JOIN (
            SELECT product_id, image_path
            FROM product_images
            GROUP BY product_id
        ) pi ON p.product_id = pi.product_id
        WHERE o.customer_id = ? AND o.order_id = ?
        GROUP BY o.order_id
    ";

    $stmt = $conn->prepare($orderQuery);
    $stmt->bind_param("ii", $user_id, $view_order_id);
    $stmt->execute();
    $single_order = $stmt->get_result()->fetch_assoc();

    if (!$single_order) {
        $message = "Order not found or you don't have permission to view it.";
        $view_mode = 'list'; // Switch back to list view
        // Fetch all orders for list view
        $ordersQuery = "
            SELECT
                o.order_id,
                o.status,
                o.total_amount,
                o.delivery_address,
                o.created_at,
                s.shop_name,
                GROUP_CONCAT(
                    CONCAT(p.product_id, '|', p.name, '|', oi.quantity, '|', oi.price, '|', COALESCE(pi.image_path, ''))
                    SEPARATOR ';;'
                ) as order_items
            FROM orders o
            JOIN shops s ON o.shop_id = s.shop_id
            LEFT JOIN order_items oi ON o.order_id = oi.order_id
            LEFT JOIN products p ON oi.product_id = p.product_id
            LEFT JOIN (
                SELECT product_id, image_path
                FROM product_images
                GROUP BY product_id
            ) pi ON p.product_id = pi.product_id
            WHERE o.customer_id = ?
            GROUP BY o.order_id
            ORDER BY o.created_at DESC
        ";

        $stmt = $conn->prepare($ordersQuery);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $orders = $stmt->get_result();
    }
} else {
    // Fetch all orders for the user with order items and product details
    $ordersQuery = "
        SELECT
            o.order_id,
            o.status,
            o.total_amount,
            o.delivery_address,
            o.created_at,
            s.shop_name,
            GROUP_CONCAT(
                CONCAT(p.product_id, '|', p.name, '|', oi.quantity, '|', oi.price, '|', COALESCE(pi.image_path, ''))
                SEPARATOR ';;'
            ) as order_items
        FROM orders o
        JOIN shops s ON o.shop_id = s.shop_id
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.product_id
        LEFT JOIN (
            SELECT product_id, image_path
            FROM product_images
            GROUP BY product_id
        ) pi ON p.product_id = pi.product_id
        WHERE o.customer_id = ?
        GROUP BY o.order_id
        ORDER BY o.created_at DESC
    ";

    $stmt = $conn->prepare($ordersQuery);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $orders = $stmt->get_result();
}

// Get all requests (pending, approved, rejected) with shop owner responses
$requestsQuery = "SELECT order_id, request_type, status, admin_response, created_at FROM Order_Requests WHERE customer_id = ? ORDER BY created_at DESC";
$requestStmt = $conn->prepare($requestsQuery);
$requestStmt->bind_param("i", $user_id);
$requestStmt->execute();
$allRequests = $requestStmt->get_result();
$requests = [];
while ($req = $allRequests->fetch_assoc()) {
    $requests[$req['order_id']][$req['request_type']] = [
        'status' => $req['status'],
        'admin_response' => $req['admin_response'],
        'created_at' => $req['created_at']
    ];
}

// Get all reviews for orders
$reviewsQuery = "SELECT order_id, product_id, rating, review_text, created_at FROM reviews WHERE user_id = ?";
$reviewStmt = $conn->prepare($reviewsQuery);
$reviewStmt->bind_param("i", $user_id);
$reviewStmt->execute();
$allReviews = $reviewStmt->get_result();
$reviews = [];
while ($review = $allReviews->fetch_assoc()) {
    $reviews[$review['order_id']][$review['product_id']] = [
        'rating' => $review['rating'],
        'review_text' => $review['review_text'],
        'created_at' => $review['created_at']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Tracker - Build Your PC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0d6758ff 0%, #2a6769ff 100%);
            min-height: 100vh;
            padding-top: 100px;
        }

        .container {
            max-width: 1200px;
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

        .orders-grid {
            display: grid;
            gap: 2rem;
        }

        .order-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .order-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .order-info {
            color: white;
        }

        .order-id {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .order-date {
            opacity: 0.7;
            font-size: 0.9rem;
        }

        .order-status {
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 1px;
        }

        .status-pending { background: #ff9800; color: white; }
        .status-confirmed { background: #2196f3; color: white; }
        .status-shipped { background: #ff5722; color: white; }
        .status-delivered { background: #4caf50; color: white; }
        .status-cancelled { background: #f44336; color: white; }

        .order-body {
            padding: 1.5rem;
        }

        .shop-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
            margin-bottom: 1rem;
            opacity: 0.9;
        }

        .products-list {
            margin-bottom: 1.5rem;
        }

        .product-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            margin-bottom: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .product-image {
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

        .product-details {
            flex: 1;
            color: white;
        }

        .product-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .product-meta {
            font-size: 0.9rem;
            opacity: 0.7;
        }

        .order-total {
            text-align: right;
            color: white;
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .order-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-cancel {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
        }

        .btn-return {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            color: white;
        }

        .btn-track {
            background: linear-gradient(135deg, #00c8c8, #00a8a8);
            color: white;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .pending-request {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.3);
            color: #ffc107;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }

        .request-response {
            padding: 1rem;
            border-radius: 15px;
            margin: 0.5rem 0;
            backdrop-filter: blur(10px);
        }

        .request-response.approved {
            background: rgba(76, 175, 80, 0.1);
            border: 1px solid rgba(76, 175, 80, 0.3);
        }

        .request-response.rejected {
            background: rgba(244, 67, 54, 0.1);
            border: 1px solid rgba(244, 67, 54, 0.3);
        }

        .response-header {
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .request-response.approved .response-header {
            color: #4caf50;
        }

        .request-response.rejected .response-header {
            color: #f44336;
        }

        .response-message {
            font-size: 0.9rem;
            line-height: 1.4;
            color: white;
            opacity: 0.9;
            padding: 0.5rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            border-left: 3px solid;
        }

        .request-response.approved .response-message {
            border-left-color: #4caf50;
        }

        .request-response.rejected .response-message {
            border-left-color: #f44336;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: white;
        }

        .empty-icon {
            font-size: 4rem;
            opacity: 0.3;
            margin-bottom: 1rem;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: rgba(255, 255, 255, 0.95);
            margin: 10% auto;
            padding: 2rem;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            backdrop-filter: blur(20px);
        }

        .modal-header {
            margin-bottom: 1.5rem;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }

        .form-group textarea {
            width: 100%;
            padding: 1rem;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-family: inherit;
            resize: vertical;
            min-height: 100px;
        }

        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .btn-submit {
            background: linear-gradient(135deg, #00c8c8, #00a8a8);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-cancel-modal {
            background: #6c757d;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-review {
            background: linear-gradient(135deg, #ffd700, #ffb347);
            color: #333;
        }

        .review-modal {
            display: none;
            position: fixed;
            z-index: 1001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
        }

        .review-modal-content {
            background: rgba(255, 255, 255, 0.95);
            margin: 5% auto;
            padding: 2rem;
            border-radius: 20px;
            width: 90%;
            max-width: 600px;
            backdrop-filter: blur(20px);
        }

        .star-rating {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin: 1rem 0;
        }

        .star {
            font-size: 2rem;
            color: #ddd;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .star:hover,
        .star.active {
            color: #ffd700;
            transform: scale(1.1);
        }

        .review-text {
            width: 100%;
            min-height: 100px;
            padding: 1rem;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-family: inherit;
            resize: vertical;
        }

        .existing-review {
            background: rgba(255, 215, 0, 0.1);
            border: 1px solid rgba(255, 215, 0, 0.3);
            border-radius: 15px;
            padding: 1rem;
            margin: 0.5rem 0;
        }

        .review-stars {
            color: #ffd700;
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }

        .review-date {
            color: #666;
            font-size: 0.8rem;
            margin-top: 0.5rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .page-title {
                font-size: 2rem;
            }

            .order-header {
                flex-direction: column;
                text-align: center;
            }

            .product-item {
                flex-direction: column;
                text-align: center;
            }

            .order-actions {
                justify-content: center;
            }
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin: 2rem 0;
            position: relative;
        }

        @media (max-width: 768px) {
            .progress-steps {
                flex-wrap: wrap;
                gap: 1rem;
            }

            .progress-steps > div {
                flex: none;
                min-width: calc(50% - 0.5rem);
            }
        }
    </style>
</head>
<body>
    <?php include 'components/navigation.php'; ?>

    <div class="container">
        <?php if ($view_mode === 'single'): ?>
            <!-- Back to orders link -->
            <a href="ordertracker.php" class="back-link" style="display: inline-flex; align-items: center; gap: 0.5rem; color: white; text-decoration: none; margin-bottom: 2rem; padding: 0.5rem 1rem; border-radius: 25px; background: rgba(255, 255, 255, 0.1); transition: all 0.3s ease;">
                <i class="fas fa-arrow-left"></i> Back to All Orders
            </a>

            <div class="page-header">
                <h1 class="page-title"><i class="fas fa-search"></i> Order #<?php echo $single_order['order_id']; ?></h1>
                <p class="page-subtitle">Track your order delivery status</p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="message <?php echo (strpos($message, 'not found') !== false || strpos($message, 'Error') !== false) ? 'error' : 'success'; ?>">
                    <i class="fas fa-<?php echo (strpos($message, 'not found') !== false || strpos($message, 'Error') !== false) ? 'exclamation-circle' : 'check-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Single Order Tracking View -->
            <?php
            $steps = ['pending', 'confirmed', 'shipped', 'delivered'];
            $cancelled_steps = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
            $current_status = strtolower($single_order['status']);
            $is_cancelled = $current_status === 'cancelled';
            $active_steps = $is_cancelled ? $cancelled_steps : $steps;
            $active_index = array_search($current_status, $active_steps);
            ?>

            <div style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 20px; padding: 2rem; margin-bottom: 2rem;">
                <!-- Order Status Progress -->
                <div style="margin-bottom: 3rem;">
                    <h3 style="color: white; margin-bottom: 2rem; text-align: center;">
                        <i class="fas fa-shipping-fast"></i> Delivery Progress
                    </h3>

                    <div class="progress-steps">
                        <!-- Progress Line -->
                        <div style="position: absolute; top: 20px; left: 0; right: 0; height: 4px; background: #333; border-radius: 2px; z-index: 1;">
                            <div style="height: 100%; background: linear-gradient(90deg, #00c8c8, #00a8a8); border-radius: 2px; width: <?php echo $is_cancelled ? '100%' : (($active_index + 1) / count($steps)) * 100; ?>%; transition: width 0.5s ease;"></div>
                        </div>

                        <?php foreach ($active_steps as $index => $step): ?>
                            <?php
                            $is_active = $index <= $active_index;
                            $is_current = $index === $active_index;
                            $step_color = $is_cancelled && $step === 'cancelled' ? '#f44336' : ($is_active ? '#00c8c8' : '#666');
                            $bg_color = $is_cancelled && $step === 'cancelled' ? '#f44336' : ($is_active ? '#00c8c8' : '#333');
                            ?>
                            <div style="text-align: center; flex: 1; position: relative; z-index: 2;">
                                <div style="width: 40px; height: 40px; border-radius: 50%; background: <?php echo $bg_color; ?>; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; border: 3px solid <?php echo $is_current ? 'white' : $bg_color; ?>; box-shadow: <?php echo $is_current ? '0 0 20px rgba(0, 200, 200, 0.5)' : 'none'; ?>;">
                                    <?php if ($step === 'pending'): ?>
                                        <i class="fas fa-clock"></i>
                                    <?php elseif ($step === 'confirmed'): ?>
                                        <i class="fas fa-check"></i>
                                    <?php elseif ($step === 'shipped'): ?>
                                        <i class="fas fa-truck"></i>
                                    <?php elseif ($step === 'delivered'): ?>
                                        <i class="fas fa-box"></i>
                                    <?php elseif ($step === 'cancelled'): ?>
                                        <i class="fas fa-times"></i>
                                    <?php endif; ?>
                                </div>
                                <div style="color: <?php echo $step_color; ?>; font-weight: <?php echo $is_current ? 'bold' : 'normal'; ?>; text-transform: capitalize;">
                                    <?php echo $step; ?>
                                </div>
                                <?php if ($is_current): ?>
                                    <div style="color: rgba(255, 255, 255, 0.7); font-size: 0.8rem; margin-top: 0.5rem;">Current Status</div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Order Details -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                    <div>
                        <h4 style="color: white; margin-bottom: 1rem;"><i class="fas fa-info-circle"></i> Order Information</h4>
                        <div style="color: rgba(255, 255, 255, 0.8); line-height: 1.6;">
                            <div><strong>Order Date:</strong> <?php echo date('M d, Y - h:i A', strtotime($single_order['created_at'])); ?></div>
                            <div><strong>Shop:</strong> <?php echo htmlspecialchars($single_order['shop_name']); ?></div>
                            <div><strong>Total Amount:</strong> <span style="color: #00c8c8; font-weight: bold;">$<?php echo number_format($single_order['total_amount'], 2); ?></span></div>
                            <div><strong>Status:</strong> <span style="color: <?php echo $is_cancelled ? '#f44336' : '#00c8c8'; ?>; text-transform: capitalize; font-weight: bold;"><?php echo $single_order['status']; ?></span></div>
                        </div>
                    </div>
                    <div>
                        <h4 style="color: white; margin-bottom: 1rem;"><i class="fas fa-map-marker-alt"></i> Delivery Address</h4>
                        <div style="color: rgba(255, 255, 255, 0.8); line-height: 1.6;">
                            <?php echo htmlspecialchars($single_order['delivery_address']); ?>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div>
                    <h4 style="color: white; margin-bottom: 1rem;"><i class="fas fa-box-open"></i> Order Items</h4>
                    <div>
                        <?php
                        if (!empty($single_order['order_items'])) {
                            $items = explode(';;', $single_order['order_items']);
                            foreach ($items as $item) {
                                $parts = explode('|', $item);
                                if (count($parts) >= 4) {
                                    $product_id = $parts[0];
                                    $name = $parts[1];
                                    $quantity = $parts[2];
                                    $price = $parts[3];
                                    $image = isset($parts[4]) ? $parts[4] : '';
                        ?>
                            <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: rgba(255, 255, 255, 0.05); border-radius: 15px; margin-bottom: 1rem; border: 1px solid rgba(255, 255, 255, 0.1);">
                                <div style="width: 60px; height: 60px; border-radius: 10px; background: rgba(255, 255, 255, 0.1); display: flex; align-items: center; justify-content: center; color: rgba(255, 255, 255, 0.5);">
                                    <?php if (!empty($image) && file_exists($image)): ?>
                                        <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($name); ?>" style="width: 60px; height: 60px; border-radius: 10px; object-fit: cover;">
                                    <?php else: ?>
                                        <i class="fas fa-cube"></i>
                                    <?php endif; ?>
                                </div>
                                <div style="flex: 1; color: white;">
                                    <div style="font-weight: 600; margin-bottom: 0.25rem;"><?php echo htmlspecialchars($name); ?></div>
                                    <div style="font-size: 0.9rem; opacity: 0.7;">
                                        Qty: <?php echo $quantity; ?> × $<?php echo number_format($price, 2); ?> = $<?php echo number_format($price * $quantity, 2); ?>
                                    </div>
                                </div>
                            </div>
                        <?php
                                }
                            }
                        }
                        ?>
                    </div>
                </div>

                <!-- Action buttons for single order -->
                <div style="margin-top: 2rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                    <?php
                    $canCancel = in_array($single_order['status'], ['pending', 'confirmed']);
                    $canReturn = $single_order['status'] == 'delivered';
                    $canReview = $single_order['status'] == 'delivered';

                    // Check for all requests for this specific order
                    $requestStmt = $conn->prepare("SELECT request_type, status, admin_response FROM Order_Requests WHERE order_id = ? AND customer_id = ? ORDER BY created_at DESC");
                    $requestStmt->bind_param("ii", $view_order_id, $user_id);
                    $requestStmt->execute();
                    $singleOrderRequests = $requestStmt->get_result();
                    $singleOrderRequestData = [];
                    while ($req = $singleOrderRequests->fetch_assoc()) {
                        $singleOrderRequestData[$req['request_type']] = [
                            'status' => $req['status'],
                            'admin_response' => $req['admin_response']
                        ];
                    }

                    $hasCancelRequest = isset($singleOrderRequestData['cancel']);
                    $hasReturnRequest = isset($singleOrderRequestData['return']);
                    $singleCancelStatus = $hasCancelRequest ? $singleOrderRequestData['cancel']['status'] : null;
                    $singleReturnStatus = $hasReturnRequest ? $singleOrderRequestData['return']['status'] : null;
                    $singleCancelResponse = $hasCancelRequest ? $singleOrderRequestData['cancel']['admin_response'] : null;
                    $singleReturnResponse = $hasReturnRequest ? $singleOrderRequestData['return']['admin_response'] : null;
                    ?>

                    <?php if ($canCancel && !$hasCancelRequest): ?>
                        <button onclick="showModal('cancel', <?php echo $view_order_id; ?>)" style="padding: 0.75rem 1.5rem; border: none; border-radius: 25px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 0.5rem; background: linear-gradient(135deg, #ff6b6b, #ee5a24); color: white;">
                            <i class="fas fa-times"></i> Request Cancel
                        </button>
                    <?php elseif ($hasCancelRequest): ?>
                        <?php if ($singleCancelStatus === 'pending'): ?>
                            <span style="background: rgba(255, 193, 7, 0.1); border: 1px solid rgba(255, 193, 7, 0.3); color: #ffc107; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.9rem;">
                                <i class="fas fa-clock"></i> Cancel request pending
                            </span>
                        <?php elseif ($singleCancelStatus === 'approved'): ?>
                            <div class="request-response approved" style="background: rgba(76, 175, 80, 0.1); border: 1px solid rgba(76, 175, 80, 0.3); padding: 1rem; border-radius: 15px; color: white;">
                                <div class="response-header" style="font-weight: 600; margin-bottom: 0.5rem; color: #4caf50;">
                                    <i class="fas fa-check-circle"></i> Cancellation Approved
                                </div>
                                <?php if (!empty($singleCancelResponse)): ?>
                                    <div class="response-message" style="font-size: 0.9rem; opacity: 0.9; line-height: 1.4;"><?php echo htmlspecialchars($singleCancelResponse); ?></div>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($singleCancelStatus === 'rejected'): ?>
                            <div class="request-response rejected" style="background: rgba(244, 67, 54, 0.1); border: 1px solid rgba(244, 67, 54, 0.3); padding: 1rem; border-radius: 15px; color: white;">
                                <div class="response-header" style="font-weight: 600; margin-bottom: 0.5rem; color: #f44336;">
                                    <i class="fas fa-times-circle"></i> Cancellation Rejected
                                </div>
                                <?php if (!empty($singleCancelResponse)): ?>
                                    <div class="response-message" style="font-size: 0.9rem; opacity: 0.9; line-height: 1.4;"><?php echo htmlspecialchars($singleCancelResponse); ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($canReturn && !$hasReturnRequest): ?>
                        <button onclick="showModal('return', <?php echo $view_order_id; ?>)" style="padding: 0.75rem 1.5rem; border: none; border-radius: 25px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 0.5rem; background: linear-gradient(135deg, #f39c12, #e67e22); color: white;">
                            <i class="fas fa-undo"></i> Request Return
                        </button>
                    <?php elseif ($hasReturnRequest): ?>
                        <?php if ($singleReturnStatus === 'pending'): ?>
                            <span style="background: rgba(255, 193, 7, 0.1); border: 1px solid rgba(255, 193, 7, 0.3); color: #ffc107; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.9rem;">
                                <i class="fas fa-clock"></i> Return request pending
                            </span>
                        <?php elseif ($singleReturnStatus === 'approved'): ?>
                            <div class="request-response approved" style="background: rgba(76, 175, 80, 0.1); border: 1px solid rgba(76, 175, 80, 0.3); padding: 1rem; border-radius: 15px; color: white;">
                                <div class="response-header" style="font-weight: 600; margin-bottom: 0.5rem; color: #4caf50;">
                                    <i class="fas fa-check-circle"></i> Return Approved
                                </div>
                                <?php if (!empty($singleReturnResponse)): ?>
                                    <div class="response-message" style="font-size: 0.9rem; opacity: 0.9; line-height: 1.4;"><?php echo htmlspecialchars($singleReturnResponse); ?></div>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($singleReturnStatus === 'rejected'): ?>
                            <div class="request-response rejected" style="background: rgba(244, 67, 54, 0.1); border: 1px solid rgba(244, 67, 54, 0.3); padding: 1rem; border-radius: 15px; color: white;">
                                <div class="response-header" style="font-weight: 600; margin-bottom: 0.5rem; color: #f44336;">
                                    <i class="fas fa-times-circle"></i> Return Rejected
                                </div>
                                <?php if (!empty($singleReturnResponse)): ?>
                                    <div class="response-message" style="font-size: 0.9rem; opacity: 0.9; line-height: 1.4;"><?php echo htmlspecialchars($singleReturnResponse); ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Review buttons for delivered orders -->
                    <?php if ($canReview): ?>
                        <div style="margin-top: 1rem; width: 100%;">
                            <h4 style="color: white; margin-bottom: 1rem;"><i class="fas fa-star"></i> Product Reviews</h4>
                            <?php
                            if (!empty($single_order['order_items'])) {
                                $items = explode(';;', $single_order['order_items']);
                                foreach ($items as $item) {
                                    $parts = explode('|', $item);
                                    if (count($parts) >= 4) {
                                        $product_id = $parts[0];
                                        $name = $parts[1];
                                        $quantity = $parts[2];
                                        $price = $parts[3];
                                        $image = isset($parts[4]) ? $parts[4] : '';

                                        $hasReview = isset($reviews[$view_order_id][$product_id]);
                            ?>
                                <div style="background: rgba(255, 255, 255, 0.05); border-radius: 15px; padding: 1rem; margin-bottom: 1rem; border: 1px solid rgba(255, 255, 255, 0.1);">
                                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                                        <div style="width: 60px; height: 60px; border-radius: 10px; background: rgba(255, 255, 255, 0.1); display: flex; align-items: center; justify-content: center; color: rgba(255, 255, 255, 0.5);">
                                            <?php if (!empty($image) && file_exists($image)): ?>
                                                <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($name); ?>" style="width: 60px; height: 60px; border-radius: 10px; object-fit: cover;">
                                            <?php else: ?>
                                                <i class="fas fa-cube"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div style="flex: 1; color: white;">
                                            <div style="font-weight: 600; margin-bottom: 0.25rem;"><?php echo htmlspecialchars($name); ?></div>
                                            <div style="font-size: 0.9rem; opacity: 0.7;">Qty: <?php echo $quantity; ?></div>
                                        </div>
                                    </div>

                                    <?php if ($hasReview): ?>
                                        <div class="existing-review">
                                            <div class="review-stars">
                                                <?php
                                                $rating = $reviews[$view_order_id][$product_id]['rating'];
                                                for ($i = 1; $i <= 5; $i++) {
                                                    echo $i <= $rating ? '★' : '☆';
                                                }
                                                ?>
                                            </div>
                                            <div style="color: white; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($reviews[$view_order_id][$product_id]['review_text']); ?></div>
                                            <div class="review-date">Reviewed on <?php echo date('M d, Y', strtotime($reviews[$view_order_id][$product_id]['created_at'])); ?></div>
                                        </div>
                                    <?php else: ?>
                                        <button onclick="showReviewModal(<?php echo $view_order_id; ?>, <?php echo $product_id; ?>, '<?php echo htmlspecialchars($name); ?>')" style="padding: 0.5rem 1rem; border: none; border-radius: 20px; background: linear-gradient(135deg, #ffd700, #ffb347); color: #333; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
                                            <i class="fas fa-star"></i> Write Review
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php
                                    }
                                }
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>
            <!-- Order List View -->
            <div class="page-header">
                <h1 class="page-title"><i class="fas fa-truck"></i> Order Tracker</h1>
                <p class="page-subtitle">Track all your orders and their delivery status</p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="message <?php echo (strpos($message, 'not found') !== false || strpos($message, 'Error') !== false) ? 'error' : 'success'; ?>">
                    <i class="fas fa-<?php echo (strpos($message, 'not found') !== false || strpos($message, 'Error') !== false) ? 'exclamation-circle' : 'check-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="orders-grid">
                <?php if ($orders->num_rows > 0): ?>
                <?php while ($order = $orders->fetch_assoc()): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-info">
                                <div class="order-id">Order #<?php echo $order['order_id']; ?></div>
                                <div class="order-date"><?php echo date('M d, Y - h:i A', strtotime($order['created_at'])); ?></div>
                            </div>
                            <div class="order-status status-<?php echo $order['status']; ?>">
                                <?php echo strtoupper($order['status']); ?>
                            </div>
                        </div>

                        <div class="order-body">
                            <div class="shop-info">
                                <i class="fas fa-store"></i>
                                <span>Shop: <?php echo htmlspecialchars($order['shop_name']); ?></span>
                            </div>

                            <div class="products-list">
                                <?php
                                if (!empty($order['order_items'])) {
                                    $items = explode(';;', $order['order_items']);
                                    foreach ($items as $item) {
                                        $parts = explode('|', $item);
                                        if (count($parts) >= 4) {
                                            $product_id = $parts[0];
                                            $name = $parts[1];
                                            $quantity = $parts[2];
                                            $price = $parts[3];
                                            $image = isset($parts[4]) ? $parts[4] : '';
                                ?>
                                    <div class="product-item">
                                        <div class="product-image">
                                            <?php if (!empty($image) && file_exists($image)): ?>
                                                <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($name); ?>" style="width: 60px; height: 60px; border-radius: 10px; object-fit: cover;">
                                            <?php else: ?>
                                                <i class="fas fa-cube"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="product-details">
                                            <div class="product-name"><?php echo htmlspecialchars($name); ?></div>
                                            <div class="product-meta">
                                                Qty: <?php echo $quantity; ?> × &#8377; <?php echo number_format($price, 2); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php
                                        }
                                    }
                                }
                                ?>
                            </div>

                            <div class="order-total">
                                Total: &#8377; <?php echo number_format($order['total_amount'], 2); ?>
                            </div>

                            <div class="order-actions">
                                <?php
                                $canCancel = in_array($order['status'], ['pending', 'confirmed']);
                                $canReturn = $order['status'] == 'delivered';
                                $hasCancelRequest = isset($requests[$order['order_id']]['cancel']);
                                $hasReturnRequest = isset($requests[$order['order_id']]['return']);

                                $cancelRequestStatus = $hasCancelRequest ? $requests[$order['order_id']]['cancel']['status'] : null;
                                $returnRequestStatus = $hasReturnRequest ? $requests[$order['order_id']]['return']['status'] : null;
                                $cancelResponse = $hasCancelRequest ? $requests[$order['order_id']]['cancel']['admin_response'] : null;
                                $returnResponse = $hasReturnRequest ? $requests[$order['order_id']]['return']['admin_response'] : null;
                                ?>

                                <?php if ($canCancel && !$hasCancelRequest): ?>
                                    <button class="action-btn btn-cancel" onclick="showModal('cancel', <?php echo $order['order_id']; ?>)">
                                        <i class="fas fa-times"></i> Request Cancel
                                    </button>
                                <?php elseif ($hasCancelRequest): ?>
                                    <?php if ($cancelRequestStatus === 'pending'): ?>
                                        <span class="pending-request">
                                            <i class="fas fa-clock"></i> Cancel request pending
                                        </span>
                                    <?php elseif ($cancelRequestStatus === 'approved'): ?>
                                        <div class="request-response approved">
                                            <div class="response-header">
                                                <i class="fas fa-check-circle"></i> Cancellation Approved
                                            </div>
                                            <?php if (!empty($cancelResponse)): ?>
                                                <div class="response-message"><?php echo htmlspecialchars($cancelResponse); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php elseif ($cancelRequestStatus === 'rejected'): ?>
                                        <div class="request-response rejected">
                                            <div class="response-header">
                                                <i class="fas fa-times-circle"></i> Cancellation Rejected
                                            </div>
                                            <?php if (!empty($cancelResponse)): ?>
                                                <div class="response-message"><?php echo htmlspecialchars($cancelResponse); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if ($canReturn && !$hasReturnRequest): ?>
                                    <button class="action-btn btn-return" onclick="showModal('return', <?php echo $order['order_id']; ?>)">
                                        <i class="fas fa-undo"></i> Request Return
                                    </button>
                                <?php elseif ($hasReturnRequest): ?>
                                    <?php if ($returnRequestStatus === 'pending'): ?>
                                        <span class="pending-request">
                                            <i class="fas fa-clock"></i> Return request pending
                                        </span>
                                    <?php elseif ($returnRequestStatus === 'approved'): ?>
                                        <div class="request-response approved">
                                            <div class="response-header">
                                                <i class="fas fa-check-circle"></i> Return Approved
                                            </div>
                                            <?php if (!empty($returnResponse)): ?>
                                                <div class="response-message"><?php echo htmlspecialchars($returnResponse); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php elseif ($returnRequestStatus === 'rejected'): ?>
                                        <div class="request-response rejected">
                                            <div class="response-header">
                                                <i class="fas fa-times-circle"></i> Return Rejected
                                            </div>
                                            <?php if (!empty($returnResponse)): ?>
                                                <div class="response-message"><?php echo htmlspecialchars($returnResponse); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <a href="ordertracker.php?order_id=<?php echo $order['order_id']; ?>" class="action-btn btn-track">
                                    <i class="fas fa-search"></i> Track Order
                                </a>

                                <!-- Review button for delivered orders in list view -->
                                <?php if ($order['status'] == 'delivered'): ?>
                                    <div style="margin-top: 1rem; width: 100%;">
                                        <h4 style="color: white; margin-bottom: 1rem; font-size: 0.9rem;"><i class="fas fa-star"></i> Leave Reviews</h4>
                                        <?php
                                        if (!empty($order['order_items'])) {
                                            $items = explode(';;', $order['order_items']);
                                            foreach ($items as $item) {
                                                $parts = explode('|', $item);
                                                if (count($parts) >= 4) {
                                                    $product_id = $parts[0];
                                                    $name = $parts[1];
                                                    $hasReview = isset($reviews[$order['order_id']][$product_id]);

                                                    if (!$hasReview):
                                        ?>
                                            <button onclick="showReviewModal(<?php echo $order['order_id']; ?>, <?php echo $product_id; ?>, '<?php echo htmlspecialchars($name); ?>')"
                                                    style="padding: 0.4rem 0.8rem; margin: 0.2rem; border: none; border-radius: 15px; background: linear-gradient(135deg, #ffd700, #ffb347); color: #333; font-weight: 500; cursor: pointer; transition: all 0.3s ease; font-size: 0.8rem;">
                                                <i class="fas fa-star"></i> Review <?php echo htmlspecialchars(substr($name, 0, 15)) . (strlen($name) > 15 ? '...' : ''); ?>
                                            </button>
                                        <?php
                                                    endif;
                                                }
                                            }
                                        }
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-shopping-bag"></i></div>
                    <h3>No Orders Found</h3>
                    <p>You haven't placed any orders yet. Start shopping to see your orders here!</p>
                    <a href="product.php" class="action-btn btn-track" style="margin-top: 1rem;">
                        <i class="fas fa-shopping-cart"></i> Start Shopping
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Request Modal -->
    <div id="requestModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Request</h3>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="order_id" id="modalOrderId">
                <input type="hidden" name="action" id="modalAction">

                <div class="form-group">
                    <label for="message">Reason:</label>
                    <textarea name="message" id="message" required placeholder="Please provide a detailed reason for your request"></textarea>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel-modal" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-submit">Submit Request</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Review Modal -->
    <div id="reviewModal" class="review-modal">
        <div class="review-modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="reviewModalTitle">Write Review</h3>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="order_id" id="reviewOrderId">
                <input type="hidden" name="product_id" id="reviewProductId">
                <input type="hidden" name="rating" id="selectedRating" value="5">
                <input type="hidden" name="submit_review" value="1">

                <div class="form-group">
                    <label>Product:</label>
                    <div id="reviewProductName" style="font-weight: bold; color: #333; padding: 0.5rem; background: #f8f9fa; border-radius: 8px; margin-bottom: 1rem;"></div>
                </div>

                <div class="form-group">
                    <label>Rating:</label>
                    <div class="star-rating">
                        <span class="star" data-rating="1">★</span>
                        <span class="star" data-rating="2">★</span>
                        <span class="star" data-rating="3">★</span>
                        <span class="star" data-rating="4">★</span>
                        <span class="star" data-rating="5">★</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="review_text">Your Review:</label>
                    <textarea name="review_text" class="review-text" placeholder="Share your experience with this product..."></textarea>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel-modal" onclick="closeReviewModal()">Cancel</button>
                    <button type="submit" class="btn-submit">Submit Review</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showModal(action, orderId) {
            const modal = document.getElementById('requestModal');
            const title = document.getElementById('modalTitle');
            const orderIdInput = document.getElementById('modalOrderId');
            const actionInput = document.getElementById('modalAction');

            title.textContent = action === 'cancel' ? 'Request Cancellation' : 'Request Return';
            orderIdInput.value = orderId;
            actionInput.value = action;

            modal.style.display = 'block';
        }

        function closeModal() {
            document.getElementById('requestModal').style.display = 'none';
        }

        function showReviewModal(orderId, productId, productName) {
            const modal = document.getElementById('reviewModal');
            const orderIdInput = document.getElementById('reviewOrderId');
            const productIdInput = document.getElementById('reviewProductId');
            const productNameDiv = document.getElementById('reviewProductName');
            const ratingInput = document.getElementById('selectedRating');

            orderIdInput.value = orderId;
            productIdInput.value = productId;
            productNameDiv.textContent = productName;
            ratingInput.value = 5; // Default rating

            // Reset stars to show 5-star default
            updateStars(5);

            modal.style.display = 'block';
        }

        function closeReviewModal() {
            document.getElementById('reviewModal').style.display = 'none';
        }

        function updateStars(rating) {
            const stars = document.querySelectorAll('.star');
            stars.forEach((star, index) => {
                if (index < rating) {
                    star.classList.add('active');
                } else {
                    star.classList.remove('active');
                }
            });
        }

        // Star rating functionality
        document.addEventListener('DOMContentLoaded', function() {
            const stars = document.querySelectorAll('.star');
            stars.forEach(star => {
                star.addEventListener('click', function() {
                    const rating = parseInt(this.getAttribute('data-rating'));
                    document.getElementById('selectedRating').value = rating;
                    updateStars(rating);
                });

                star.addEventListener('mouseover', function() {
                    const rating = parseInt(this.getAttribute('data-rating'));
                    updateStars(rating);
                });
            });

            // Reset stars on mouse leave
            const starRating = document.querySelector('.star-rating');
            if (starRating) {
                starRating.addEventListener('mouseleave', function() {
                    const currentRating = document.getElementById('selectedRating').value;
                    updateStars(currentRating);
                });
            }
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            const requestModal = document.getElementById('requestModal');
            const reviewModal = document.getElementById('reviewModal');

            if (event.target == requestModal) {
                closeModal();
            }
            if (event.target == reviewModal) {
                closeReviewModal();
            }
        }
    </script>
</body>
</html>