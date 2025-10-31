<?php
session_start();
include "configuration/db.php";

// --- Handle filters ---
$where = [];
$params = [];
$types = "";

// Category filter
if (!empty($_GET['category'])) {
    $where[] = "p.category = ?";
    $params[] = $_GET['category'];
    $types .= "s";
}

// Brand filter
if (!empty($_GET['brand'])) {
    $where[] = "p.brand LIKE ?";
    $params[] = "%" . $_GET['brand'] . "%";
    $types .= "s";
}

// Price range filter
if (!empty($_GET['min_price']) && is_numeric($_GET['min_price'])) {
    $where[] = "p.price >= ?";
    $params[] = $_GET['min_price'];
    $types .= "d";
}
if (!empty($_GET['max_price']) && is_numeric($_GET['max_price'])) {
    $where[] = "p.price <= ?";
    $params[] = $_GET['max_price'];
    $types .= "d";
}

// Search filter
if (!empty($_GET['search'])) {
    $where[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%" . $_GET['search'] . "%";
    $params[] = "%" . $_GET['search'] . "%";
    $types .= "ss";
}

// Build query
$sql = "SELECT p.*, pi.image_path, s.shop_name,
        COALESCE(AVG(r.rating), 0) as avg_rating,
        COUNT(r.rating) as review_count
        FROM products p
        LEFT JOIN product_images pi ON p.product_id = pi.product_id
        INNER JOIN shops s ON p.shop_id = s.shop_id
        LEFT JOIN reviews r ON p.product_id = r.product_id
        WHERE p.availability = 1";

if (!empty($where)) {
    $sql .= " AND " . implode(" AND ", $where);
}

$sql .= " GROUP BY p.product_id ORDER BY p.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Products - Explore BYPC</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #e3f2fd 0%, #f8f9ff 100%);
            color: #2c3e50;
            line-height: 1.6;
            min-height: 100vh;
            animation: fadeIn 0.8s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #4fc3f7 0%, #29b6f6 100%);
            color: white;
            padding: 30px 0;
            text-align: center;
            box-shadow: 0 4px 20px rgba(79, 195, 247, 0.3);
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .header h1 {
            font-size: 2.8rem;
            font-weight: 300;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        /* Filter Section */
        .filters {
            background: white;
            padding: 30px;
            margin: 30px 0;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(79, 195, 247, 0.1);
            border: 1px solid rgba(79, 195, 247, 0.1);
            animation: slideUp 0.6s ease-out 0.2s both;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-weight: 600;
            color: #37474f;
            margin-bottom: 8px;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-input, .filter-select {
            padding: 12px 16px;
            border: 2px solid #e3f2fd;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: white;
        }

        .filter-input:focus, .filter-select:focus {
            outline: none;
            border-color: #4fc3f7;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(79, 195, 247, 0.2);
        }

        .filter-buttons {
            display: flex;
            gap: 15px;
            grid-column: 1 / -1;
            justify-content: center;
            margin-top: 20px;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4fc3f7 0%, #29b6f6 100%);
            color: white;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #90a4ae 0%, #78909c 100%);
            color: white;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        /* Product Grid */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            padding: 20px 0;
            animation: slideUp 0.6s ease-out 0.4s both;
        }

        .product-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(79, 195, 247, 0.1);
            border: 1px solid rgba(79, 195, 247, 0.05);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        .product-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #4fc3f7 0%, #29b6f6 100%);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .product-card:hover::before {
            transform: scaleX(1);
        }

        .product-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 50px rgba(79, 195, 247, 0.2);
        }

        .product-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .product-image {
            width: 100%;
            height: 220px;
            object-fit: contain;
            margin-bottom: 20px;
            border-radius: 10px;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-image {
            transform: scale(1.05);
        }

        .product-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
            line-height: 1.3;
        }

        .product-meta {
            color: #607d8b;
            font-size: 0.9rem;
            margin-bottom: 15px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        .product-meta i {
            color: #4fc3f7;
        }

        .price {
            font-size: 1.8rem;
            font-weight: 700;
            color: #4caf50;
            margin: 15px 0;
            background: linear-gradient(135deg, #4caf50, #66bb6a);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .shop-name {
            color: #4fc3f7;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 20px;
            padding: 5px 15px;
            background: rgba(79, 195, 247, 0.1);
            border-radius: 15px;
            display: inline-block;
        }

        .btn-container {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .btn-cart {
            flex: 1;
            background: linear-gradient(135deg, #4fc3f7 0%, #29b6f6 100%);
            color: white;
        }

        .btn-buy {
            flex: 1;
            background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%);
            color: white;
        }

        .out-of-stock {
            color: #f44336;
            font-weight: 600;
            padding: 15px;
            background: rgba(244, 67, 54, 0.1);
            border-radius: 10px;
            margin-top: 20px;
            border: 1px solid rgba(244, 67, 54, 0.2);
        }

        .rating-section {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin: 10px 0;
            padding: 8px 15px;
            background: rgba(79, 195, 247, 0.05);
            border-radius: 15px;
            border: 1px solid rgba(79, 195, 247, 0.1);
        }

        .stars {
            color: #ffd700;
            font-size: 1rem;
        }

        .rating-info {
            font-size: 0.9rem;
            color: #607d8b;
            font-weight: 500;
        }

        .no-reviews {
            color: #90a4ae;
            font-style: italic;
            font-size: 0.85rem;
        }

        .no-products {
            text-align: center;
            padding: 60px 20px;
            color: #607d8b;
        }

        .no-products i {
            font-size: 4rem;
            color: #4fc3f7;
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-20px); }
            60% { transform: translateY(-10px); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header h1 {
                font-size: 2rem;
            }
            
            .filter-form {
                grid-template-columns: 1fr;
            }
            
            .product-grid {
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 20px;
            }
            
            .btn-container {
                flex-direction: column;
            }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(79, 195, 247, 0.3);
            border-radius: 50%;
            border-top-color: #4fc3f7;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <?php include 'components/navigation.php'; ?>

<div class="header" style="margin-top: 80px;">
    <div class="container">
        <h1><i class="fas fa-shopping-cart"></i>Explore BYPC</h1>
        <p>Discover the latest technology products at unbeatable prices</p>
    </div>
</div>

<div class="container">
    <!-- Filter Section -->
    <div class="filters">
        <form method="GET" class="filter-form">
            <div class="filter-group">
                <label for="search"><i class="fas fa-search"></i> Search</label>
                <input type="text" id="search" name="search" class="filter-input" placeholder="Search products..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
            </div>
            
            <div class="filter-group">
                <label for="category"><i class="fas fa-tags"></i> Category</label>
                <select name="category" id="category" class="filter-select">
                    <option value="">All Categories</option>
                    <?php
                    $cats = ['CPU','GPU','Motherboard','RAM','Storage','PSU','Cabinet','Cooling','Monitor','Peripherals'];
                    foreach ($cats as $cat) {
                        $sel = (($_GET['category'] ?? '') === $cat) ? "selected" : "";
                        echo "<option value='$cat' $sel>$cat</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="brand"><i class="fas fa-certificate"></i> Brand</label>
                <input type="text" id="brand" name="brand" class="filter-input" placeholder="Enter brand name" value="<?php echo htmlspecialchars($_GET['brand'] ?? ''); ?>">
            </div>
            
            <div class="filter-group">
                <label for="min_price"><i class="fas fa-rupee-sign"></i> Min Price</label>
                <input type="number" id="min_price" name="min_price" class="filter-input" placeholder="0" value="<?php echo htmlspecialchars($_GET['min_price'] ?? ''); ?>">
            </div>
            
            <div class="filter-group">
                <label for="max_price"><i class="fas fa-rupee-sign"></i> Max Price</label>
                <input type="number" id="max_price" name="max_price" class="filter-input" placeholder="999999" value="<?php echo htmlspecialchars($_GET['max_price'] ?? ''); ?>">
            </div>
            
            <div class="filter-buttons">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Apply Filters
                </button>
                <a href="products.php" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> Reset Filters
                </a>
            </div>
        </form>
    </div>

    <!-- Product Grid -->
    <div class="product-grid">
    <?php if ($result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <div class="product-card">
                <a href="productinner.php?id=<?php echo $row['product_id']; ?>" class="product-link">
                    <?php if (!empty($row['image_path'])): ?>
                        <img src="uploads/<?php echo htmlspecialchars($row['image_path']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" class="product-image">
                    <?php else: ?>
                        <img src="uploads/no-image.png" alt="No Image Available" class="product-image">
                    <?php endif; ?>
                    
                    <h3 class="product-title"><?php echo htmlspecialchars($row['name']); ?></h3>
                    
                    <div class="product-meta">
                        <span><i class="fas fa-industry"></i> <?php echo htmlspecialchars($row['brand']); ?></span>
                        <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($row['category']); ?></span>
                    </div>
                    
                    <div class="price">₹<?php echo number_format($row['price'], 2); ?></div>
                    
                    <div class="shop-name">
                        <i class="fas fa-store"></i> <?php echo htmlspecialchars($row['shop_name']); ?>
                    </div>

                    <!-- Rating Section -->
                    <div class="rating-section">
                        <?php
                        $avgRating = round($row['avg_rating'], 1);
                        $reviewCount = $row['review_count'];

                        if ($reviewCount > 0):
                        ?>
                            <div class="stars">
                                <?php
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $avgRating ? '★' : '☆';
                                }
                                ?>
                            </div>
                            <div class="rating-info">
                                <?php echo $avgRating; ?> (<?php echo $reviewCount; ?> review<?php echo $reviewCount != 1 ? 's' : ''; ?>)
                            </div>
                        <?php else: ?>
                            <div class="no-reviews">No reviews yet</div>
                        <?php endif; ?>
                    </div>
                </a>
                
                <?php if ($row['stock'] > 0): ?>
                    <div class="btn-container">
                        <a href="cart.php?action=add&id=<?php echo $row['product_id']; ?>" class="btn btn-cart">
                            <i class="fas fa-cart-plus"></i> Add to Cart
                        </a>
                        <a href="productinner.php?id=<?php echo $row['product_id']; ?>" class="btn btn-buy">
                            <i class="fas fa-bolt"></i> Buy Now
                        </a>
                    </div>
                <?php else: ?>
                    <div class="out-of-stock">
                        <i class="fas fa-exclamation-triangle"></i> Out of Stock
                    </div>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="no-products">
            <i class="fas fa-search"></i>
            <h3>No products found</h3>
            <p>Try adjusting your filters or search terms</p>
        </div>
    <?php endif; ?>
    </div>
</div>

<script>
    // Add smooth scrolling and enhanced interactions
    document.addEventListener('DOMContentLoaded', function() {
        // Animate cards on scroll
        const cards = document.querySelectorAll('.product-card');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.style.animation = `slideUp 0.6s ease-out both`;
                    }, index * 100);
                }
            });
        });

        cards.forEach(card => observer.observe(card));

        // Add loading animation to buttons on click
        const buttons = document.querySelectorAll('.btn');
        buttons.forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (this.type === 'submit') {
                    const spinner = document.createElement('span');
                    spinner.className = 'loading';
                    this.appendChild(spinner);
                }
            });
        });

        // Enhanced hover effects
        cards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
    });
</script>

</body>
</html>