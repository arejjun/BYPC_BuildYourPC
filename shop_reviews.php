<?php
session_start();
include "configuration/db.php";

// Check if shop owner is logged in
if (!isset($_SESSION['shop_id'])) {
    header("Location: SignupandLogin/login.php");
    exit;
}

$shop_id = $_SESSION['shop_id'];

// Fetch reviews for shop's products
$reviewsQuery = "
    SELECT
        r.review_id,
        r.rating,
        r.review_text,
        r.created_at,
        p.name as product_name,
        p.product_id,
        c.name,
        o.order_id
    FROM reviews r
    INNER JOIN products p ON r.product_id = p.product_id
    INNER JOIN users c ON r.user_id = c.user_id
    INNER JOIN orders o ON r.order_id = o.order_id
    WHERE p.shop_id = ?
    ORDER BY r.created_at DESC
";

$stmt = $conn->prepare($reviewsQuery);
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$reviews = $stmt->get_result();

// Get review statistics
$statsQuery = "
    SELECT
        COUNT(*) as total_reviews,
        AVG(r.rating) as avg_rating,
        SUM(CASE WHEN r.rating = 5 THEN 1 ELSE 0 END) as five_stars,
        SUM(CASE WHEN r.rating = 4 THEN 1 ELSE 0 END) as four_stars,
        SUM(CASE WHEN r.rating = 3 THEN 1 ELSE 0 END) as three_stars,
        SUM(CASE WHEN r.rating = 2 THEN 1 ELSE 0 END) as two_stars,
        SUM(CASE WHEN r.rating = 1 THEN 1 ELSE 0 END) as one_star
    FROM reviews r
    INNER JOIN products p ON r.product_id = p.product_id
    WHERE p.shop_id = ?
";

$statsStmt = $conn->prepare($statsQuery);
$statsStmt->bind_param("i", $shop_id);
$statsStmt->execute();
$stats = $statsStmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Reviews - Shop Management</title>
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            color: white;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #ffd700;
        }

        .stat-label {
            font-size: 1rem;
            opacity: 0.8;
        }

        .rating-breakdown {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .rating-row {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .rating-stars {
            color: #ffd700;
            min-width: 60px;
        }

        .rating-bar {
            flex: 1;
            height: 20px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            overflow: hidden;
        }

        .rating-fill {
            height: 100%;
            background: linear-gradient(90deg, #ffd700, #ffb347);
            transition: width 0.3s ease;
        }

        .rating-count {
            min-width: 30px;
            text-align: right;
        }

        .reviews-section {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 2rem;
        }

        .section-title {
            color: white;
            font-size: 1.8rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .review-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .review-info {
            color: white;
        }

        .customer-name {
            font-weight: 600;
            font-size: 1.1rem;
        }

        .product-name {
            color: #00c8c8;
            font-size: 0.9rem;
            margin: 0.25rem 0;
        }

        .review-date {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.8rem;
        }

        .review-rating {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stars {
            color: #ffd700;
            font-size: 1.2rem;
        }

        .rating-number {
            background: rgba(255, 215, 0, 0.2);
            color: #ffd700;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-weight: 600;
        }

        .review-text {
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.6;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            border-left: 3px solid #00c8c8;
        }

        .no-reviews {
            text-align: center;
            color: rgba(255, 255, 255, 0.6);
            padding: 3rem;
            font-size: 1.1rem;
        }

        .no-reviews i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: rgba(255, 255, 255, 0.3);
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .page-title {
                font-size: 2rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .review-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <?php include 'components/navigation.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-star"></i> Customer Reviews</h1>
            <p class="page-subtitle">View and manage reviews for your products</p>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_reviews'] ?: '0'; ?></div>
                <div class="stat-label">Total Reviews</div>
            </div>

            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_reviews'] > 0 ? number_format($stats['avg_rating'], 1) : '0.0'; ?></div>
                <div class="stat-label">Average Rating</div>
            </div>

            <div class="stat-card">
                <div class="stat-label" style="margin-bottom: 1rem;">Rating Breakdown</div>
                <div class="rating-breakdown">
                    <?php
                    $ratings = [5, 4, 3, 2, 1];
                    $ratingCounts = [
                        5 => $stats['five_stars'] ?: 0,
                        4 => $stats['four_stars'] ?: 0,
                        3 => $stats['three_stars'] ?: 0,
                        2 => $stats['two_stars'] ?: 0,
                        1 => $stats['one_star'] ?: 0
                    ];
                    $totalReviews = $stats['total_reviews'] ?: 1;

                    foreach ($ratings as $rating):
                        $count = $ratingCounts[$rating];
                        $percentage = ($count / $totalReviews) * 100;
                    ?>
                        <div class="rating-row">
                            <div class="rating-stars">
                                <?php
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $rating ? '★' : '☆';
                                }
                                ?>
                            </div>
                            <div class="rating-bar">
                                <div class="rating-fill" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                            <div class="rating-count"><?php echo $count; ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Reviews List -->
        <div class="reviews-section">
            <h2 class="section-title">
                <i class="fas fa-comments"></i> Recent Reviews
            </h2>

            <?php if ($reviews->num_rows > 0): ?>
                <?php while ($review = $reviews->fetch_assoc()): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div class="review-info">
                                <div class="customer-name">
                                    <?php echo htmlspecialchars($review['name']); ?>
                                </div>
                                <div class="product-name">
                                    <i class="fas fa-cube"></i> <?php echo htmlspecialchars($review['product_name']); ?>
                                </div>
                                <div class="review-date">
                                    Order #<?php echo $review['order_id']; ?> • <?php echo date('M d, Y - h:i A', strtotime($review['created_at'])); ?>
                                </div>
                            </div>
                            <div class="review-rating">
                                <div class="stars">
                                    <?php
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo $i <= $review['rating'] ? '★' : '☆';
                                    }
                                    ?>
                                </div>
                                <div class="rating-number"><?php echo $review['rating']; ?>/5</div>
                            </div>
                        </div>

                        <?php if (!empty($review['review_text'])): ?>
                            <div class="review-text">
                                <?php echo htmlspecialchars($review['review_text']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-reviews">
                    <div><i class="fas fa-star"></i></div>
                    <h3>No Reviews Yet</h3>
                    <p>Your customers haven't left any reviews yet. Encourage them to share their experience!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>