<?php
session_start();
include "configuration/db.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid product.");
}
$product_id = intval($_GET['id']);

// --- Fetch product info ---
$sql = "SELECT p.*, s.shop_name, s.shop_id 
        FROM products p 
        INNER JOIN shops s ON p.shop_id = s.shop_id
        WHERE p.product_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    die("Product not found.");
}

// --- Fetch product images ---
$sqlImg = "SELECT * FROM product_images WHERE product_id = ?";
$stmtImg = $conn->prepare($sqlImg);
$stmtImg->bind_param("i", $product_id);
$stmtImg->execute();
$images = $stmtImg->get_result()->fetch_all(MYSQLI_ASSOC);

// --- Fetch reviews ---
$sqlRev = "SELECT r.*, u.name
           FROM reviews r
           INNER JOIN users u ON r.user_id = u.user_id
           INNER JOIN orders o ON r.order_id = o.order_id
           INNER JOIN order_items oi ON r.order_id = oi.order_id
           WHERE oi.product_id = ?";
$stmtRev = $conn->prepare($sqlRev);
$stmtRev->bind_param("i", $product_id);
$stmtRev->execute();
$reviews = $stmtRev->get_result();

// --- Check login status ---
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #fafbfc;
            color: #1a1a1a;
            line-height: 1.5;
            font-size: 16px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* Header */
        .header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 16px 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .back-link {
            color: #64748b;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color 0.2s ease;
        }

        .back-link:hover {
            color: #0ea5e9;
        }

        .breadcrumb {
            color: #94a3b8;
            font-size: 14px;
        }

        /* Main Content */
        .main-content {
            padding: 48px 0;
        }

        .product-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 64px;
            margin-bottom: 64px;
        }

        /* Gallery */
        .gallery {
            position: sticky;
            top: 120px;
            height: fit-content;
        }

        .main-image {
            width: 100%;
            aspect-ratio: 1;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .main-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .main-image:hover img {
            transform: scale(1.02);
        }

        .thumbnails {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding: 4px 0;
        }

        .thumbnail {
            width: 64px;
            height: 64px;
            min-width: 64px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .thumbnail:hover,
        .thumbnail.active {
            border-color: #0ea5e9;
        }

        .thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        /* Product Info */
        .product-info {
            padding-top: 8px;
        }

        .product-title {
            font-size: 32px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 8px;
            line-height: 1.2;
        }

        .product-subtitle {
            color: #64748b;
            font-size: 18px;
            margin-bottom: 32px;
        }

        .price {
            font-size: 28px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 24px;
        }

        .stock-status {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 32px;
            padding: 4px 0;
        }

        .in-stock {
            color: #059669;
        }

        .out-of-stock {
            color: #dc2626;
        }

        /* Product Details */
        .product-details {
            margin-bottom: 32px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .detail-label {
            color: #64748b;
            font-weight: 500;
        }

        .detail-value {
            color: #1a1a1a;
            font-weight: 500;
        }

        /* Actions */
        .actions {
            display: flex;
            gap: 12px;
            margin-bottom: 32px;
        }

        .btn {
            flex: 1;
            padding: 16px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            text-align: center;
            display: inline-block;
        }

        .btn-primary {
            background: #0ea5e9;
            color: white;
        }

        .btn-primary:hover {
            background: #0284c7;
        }

        .btn-secondary {
            background: white;
            color: #0ea5e9;
            border: 1px solid #0ea5e9;
        }

        .btn-secondary:hover {
            background: #f0f9ff;
        }

        .btn-disabled {
            background: #f1f5f9;
            color: #94a3b8;
            cursor: not-allowed;
        }

        /* Description */
        .description {
            margin-top: 48px;
            padding-top: 48px;
            border-top: 1px solid #e5e7eb;
        }

        .description-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 16px;
        }

        .description-content {
            color: #64748b;
            line-height: 1.6;
        }

        /* Reviews */
        .reviews-section {
            margin-top: 64px;
            padding-top: 64px;
            border-top: 1px solid #e5e7eb;
        }

        .section-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 32px;
        }

        .review {
            padding: 24px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .review:last-child {
            border-bottom: none;
        }

        .review-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .reviewer-name {
            font-weight: 600;
            color: #1a1a1a;
        }

        .review-rating {
            color: #fbbf24;
            font-size: 14px;
        }

        .review-text {
            color: #64748b;
            margin-bottom: 8px;
        }

        .review-date {
            font-size: 14px;
            color: #94a3b8;
        }

        .no-reviews {
            text-align: center;
            color: #94a3b8;
            font-style: italic;
            padding: 48px 0;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 32px;
            width: 100%;
            max-width: 480px;
            margin: 0 24px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-weight: 500;
            margin-bottom: 8px;
            color: #374151;
        }

        .form-input {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.2s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #0ea5e9;
        }

        .form-textarea {
            min-height: 80px;
            resize: vertical;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 0 16px;
            }

            .main-content {
                padding: 24px 0;
            }

            .product-layout {
                grid-template-columns: 1fr;
                gap: 32px;
            }

            .gallery {
                position: static;
            }

            .product-title {
                font-size: 24px;
            }

            .price {
                font-size: 24px;
            }

            .actions {
                flex-direction: column;
            }

            .reviews-section {
                margin-top: 48px;
                padding-top: 48px;
            }

            .modal-content {
                margin: 16px;
            }

            .modal-actions {
                flex-direction: column;
            }
        }

        /* Loading states */
        .loading {
            opacity: 0;
            animation: fadeIn 0.5s ease forwards;
        }

        @keyframes fadeIn {
            to { opacity: 1; }
        }
    </style>
</head>
<body>
    <?php include 'components/navigation.php'; ?>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="products.php" class="back-link">
                    ← Back
                </a>
                <div class="breadcrumb">
                    Products / <?php echo htmlspecialchars($product['category']); ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <div class="product-layout">
                <!-- Gallery -->
                <div class="gallery">
                    <div class="main-image">
                        <?php if (!empty($images)): ?>
                            <img id="mainImage" src="uploads/<?php echo htmlspecialchars($images[0]['image_path']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php else: ?>
                            <img id="mainImage" src="uploads/no-image.png" alt="No image available">
                        <?php endif; ?>
                    </div>
                    
                    <?php if (count($images) > 1): ?>
                    <div class="thumbnails">
                        <?php foreach ($images as $index => $img): ?>
                            <div class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>" 
                                 onclick="changeImage('uploads/<?php echo htmlspecialchars($img['image_path']); ?>', this)">
                                <img src="uploads/<?php echo htmlspecialchars($img['image_path']); ?>" alt="Product image">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Product Info -->
                <div class="product-info">
                    <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                    <div class="product-subtitle"><?php echo htmlspecialchars($product['brand']); ?></div>
                    
                    <div class="price">₹<?php echo number_format($product['price'], 2); ?></div>
                    
                    <div class="stock-status <?php echo $product['stock'] > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                        <?php echo $product['stock'] > 0 ? 'In Stock' : 'Out of Stock'; ?>
                    </div>

                    <!-- Actions -->
                    <?php if ($product['stock'] > 0): ?>
                        <div class="actions">
                            <?php if ($isLoggedIn): ?>
                                <a href="cart.php?action=add&id=<?php echo $product['product_id']; ?>" class="btn btn-secondary">
                                    Add to Cart
                                </a>
                                <button onclick="showDeliveryPopup()" class="btn btn-primary">
                                    Buy Now
                                </button>
                            <?php else: ?>
                                <button onclick="window.location.href='SignupandLogin/login.php'" class="btn btn-primary">
                                    Sign in to Purchase
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="actions">
                            <button class="btn btn-disabled" disabled>
                                Currently Unavailable
                            </button>
                        </div>
                    <?php endif; ?>

                    <!-- Product Details -->
                    <div class="product-details">
                        <div class="detail-row">
                            <span class="detail-label">Category</span>
                            <span class="detail-value"><?php echo htmlspecialchars($product['category']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Brand</span>
                            <span class="detail-value"><?php echo htmlspecialchars($product['brand']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Sold by</span>
                            <span class="detail-value"><?php echo htmlspecialchars($product['shop_name']); ?></span>
                        </div>
                    </div>

                    <!-- Description -->
                    <?php if (!empty($product['description'])): ?>
                    <div class="description">
                        <h3 class="description-title">Description</h3>
                        <div class="description-content">
                            <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Reviews -->
            <section class="reviews-section">
                <h2 class="section-title">Customer Reviews</h2>
                
                <?php if ($reviews->num_rows > 0): ?>
                    <?php while($review = $reviews->fetch_assoc()): ?>
                        <div class="review">
                            <div class="review-header">
                                <span class="reviewer-name"><?php echo htmlspecialchars($review['name']); ?></span>
                                <span class="review-rating">
                                    <?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?>
                                </span>
                            </div>
                            <div class="review-text"><?php echo htmlspecialchars($review['review_text']); ?></div>
                            <div class="review-date"><?php echo date('M j, Y', strtotime($review['created_at'])); ?></div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-reviews">
                        No reviews yet. Be the first to review this product.
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <!-- Modal -->
    <div id="deliveryModal" class="modal">
        <div class="modal-content">
            <h3 class="modal-title">Delivery Information</h3>
            <form method="POST" action="placeorder.php">
                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                <input type="hidden" name="shop_id" value="<?php echo $product['shop_id']; ?>">
                
                <div class="form-group">
                    <label class="form-label">Delivery Address</label>
                    <textarea name="delivery_address" class="form-input form-textarea" placeholder="Enter your complete delivery address" required></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Pincode</label>
                    <input type="text" name="pincode" class="form-input" placeholder="6-digit pincode" maxlength="6" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" name="phone" class="form-input" placeholder="10-digit mobile number" maxlength="15" required>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary">Confirm Order</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('deliveryModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function changeImage(src, thumbnail) {
            document.getElementById('mainImage').src = src;
            
            // Update active thumbnail
            document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
            thumbnail.classList.add('active');
        }

        function showDeliveryPopup() {
            document.getElementById('deliveryModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Close modal on outside click
        document.getElementById('deliveryModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal('deliveryModal');
            }
        });

        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal('deliveryModal');
            }
        });

        // Loading animation
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('.main-content').classList.add('loading');
        });
    </script>
</body>
</html>