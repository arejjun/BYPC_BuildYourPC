<?php
session_start();
include "configuration/db.php";
include 'components/navigation.php';

// Fetch all suggested shops
$suggestedShops = $conn->query("SELECT * FROM Suggested_Shops ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services - BuildYourPC</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #000000ff 0%, #000000ff 100%);
            min-height: 100vh;
            padding-top: 120px;
        }

        .services-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .services-header {
            text-align: center;
            margin-bottom: 3rem;
            color: white;
        }

        .services-header h1 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1rem;
            text-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        .services-header p {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }

        .shops-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .shop-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 2rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .shop-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #00c8c8, #00a8a8);
        }

        .shop-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 200, 200, 0.3);
        }

        .shop-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .shop-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #00c8c8, #00a8a8);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            box-shadow: 0 8px 25px rgba(0, 200, 200, 0.3);
        }

        .shop-icon i {
            font-size: 1.8rem;
            color: white;
        }

        .shop-title {
            flex: 1;
        }

        .shop-title h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.5rem;
        }

        .shop-badge {
            background: rgba(0, 200, 200, 0.2);
            color: #00c8c8;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            border: 1px solid rgba(0, 200, 200, 0.3);
        }

        .shop-details {
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.6;
        }

        .detail-row {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            padding: 0.5rem 0;
        }

        .detail-row i {
            width: 20px;
            color: #00c8c8;
            margin-right: 1rem;
            font-size: 1.1rem;
        }

        .detail-row span {
            flex: 1;
        }

        .contact-actions {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .contact-btn {
            flex: 1;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 15px;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            min-width: 140px;
        }

        .btn-call {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
        }

        .btn-call:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(76, 175, 80, 0.4);
        }

        .btn-email {
            background: linear-gradient(135deg, #2196F3, #1976D2);
            color: white;
        }

        .btn-email:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(33, 150, 243, 0.4);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: rgba(255, 255, 255, 0.8);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: rgba(255, 255, 255, 0.5);
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: white;
        }

        .empty-state p {
            font-size: 1rem;
            opacity: 0.8;
        }

        @media (max-width: 768px) {
            .services-container {
                padding: 1rem;
            }

            .services-header h1 {
                font-size: 2rem;
            }

            .shops-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .shop-card {
                padding: 1.5rem;
            }

            .contact-actions {
                flex-direction: column;
            }

            .contact-btn {
                flex: none;
            }
        }
    </style>
</head>
<body>
    <div class="services-container">
        <div class="services-header">
            <h1><i class="fas fa-store"></i> Suggested Shops</h1>
            <p>Discover trusted PC component shops recommended by our community and experts</p>
        </div>

        <div class="shops-grid">
            <?php if ($suggestedShops && $suggestedShops->num_rows > 0): ?>
                <?php while ($shop = $suggestedShops->fetch_assoc()): ?>
                    <div class="shop-card">
                        <div class="shop-header">
                            <div class="shop-icon">
                                <i class="fas fa-store"></i>
                            </div>
                            <div class="shop-title">
                                <h3><?php echo htmlspecialchars($shop['shop_name']); ?></h3>
                                <span class="shop-badge">Recommended</span>
                            </div>
                        </div>

                        <div class="shop-details">
                            <div class="detail-row">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>
                                    <?php echo htmlspecialchars($shop['address_line1']); ?>
                                    <?php if (!empty($shop['address_line2'])): ?>
                                        <br><?php echo htmlspecialchars($shop['address_line2']); ?>
                                    <?php endif; ?>
                                </span>
                            </div>

                            <div class="detail-row">
                                <i class="fas fa-city"></i>
                                <span><?php echo htmlspecialchars($shop['district']); ?></span>
                            </div>

                            <div class="detail-row">
                                <i class="fas fa-map-pin"></i>
                                <span><?php echo htmlspecialchars($shop['pincode']); ?></span>
                            </div>

                            <div class="detail-row">
                                <i class="fas fa-phone"></i>
                                <span><?php echo htmlspecialchars($shop['phone_number']); ?></span>
                            </div>

                            <div class="detail-row">
                                <i class="fas fa-envelope"></i>
                                <span><?php echo htmlspecialchars($shop['email']); ?></span>
                            </div>
                        </div>

                        <div class="contact-actions">
                            <a href="tel:<?php echo htmlspecialchars($shop['phone_number']); ?>" class="contact-btn btn-call">
                                <i class="fas fa-phone"></i>
                                Call Now
                            </a>
                            <a href="mailto:<?php echo htmlspecialchars($shop['email']); ?>" class="contact-btn btn-email">
                                <i class="fas fa-envelope"></i>
                                Email
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-store-slash"></i>
                    <h3>No Shops Available Yet</h3>
                    <p>Our admin team is working to add trusted PC component shops. Please check back soon!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>