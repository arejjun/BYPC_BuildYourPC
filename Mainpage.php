    <?php
    session_start();

    // Check if user is logged in
    $isLoggedIn = isset($_SESSION['user_id']);
    $userName = $isLoggedIn ? $_SESSION['username'] : '';

    // Handle logout
    if (isset($_GET['logout'])) {
        session_destroy();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
    ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BuildYourPC BYPC</title>
    <style>
        /* Promo Banner Section */
.promo-banner {
    width: 100%;
    overflow: hidden;
    position: relative;
}

.promo-image-container {
    position: relative;
    width: 100%;
    max-height: 550px;
    overflow: hidden;
}

.promo-img {
    width: 100%;
    height: auto;
    object-fit: cover;
    display: block;
}

.promo-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    text-align: center;
    background: rgba(0, 0, 0, 0.4);
    padding: 2rem;
    border-radius: 15px;
    max-width: 90%;
}

.promo-text h2 {
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 1rem;
}

.promo-text p {
    font-size: 1.2rem;
    margin-bottom: 1.5rem;
}

.banner-btn {
    background: #00c8c8;
    color: white;
    padding: 0.8rem 2rem;
    border-radius: 25px;
    font-weight: 600;
    text-decoration: none;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.banner-btn:hover {
    background: #009191;
    transform: translateY(-3px);
}

/* Responsive */
@media (max-width: 768px) {
    .promo-text h2 {
        font-size: 1.5rem;
    }

    .promo-text p {
        font-size: 1rem;
    }

    .banner-btn {
        font-size: 0.9rem;
    }
}

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #ffffff;
            color: #1a1a1a;
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #00c8c8;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #00a8a8;
        }

        /* Navigation - Restored */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(0, 200, 200, 0.1);
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 4px 20px rgba(0, 200, 200, 0.1);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 800;
            color: #00c8c8;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.05);
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            color: #1a1a1a;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            position: relative;
            padding: 0.5rem 0;
        }

        .nav-links a:hover {
            color: #00c8c8;
            transform: translateY(-2px);
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 0;
            background: linear-gradient(90deg, #00c8c8, #00a8a8);
            transition: width 0.3s ease;
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .auth-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .login-btn {
            background: linear-gradient(135deg, #00c8c8, #00a8a8);
            padding: 0.75rem 1.75rem;
            border-radius: 25px;
            color: white !important;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(0, 200, 200, 0.2);
        }

        .login-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0, 200, 200, 0.4);
        }

        .user-dropdown {
            position: relative;
            display: inline-block;
        }

        .user-btn {
            background: linear-gradient(135deg, #00c8c8, #00a8a8);
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            color: white !important;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            min-width: 140px;
            justify-content: space-between;
        }

        .user-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 200, 200, 0.3);
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            min-width: 180px;
            box-shadow: 0 15px 35px rgba(0, 200, 200, 0.2);
            border-radius: 15px;
            z-index: 1002;
            border: 1px solid rgba(0, 200, 200, 0.1);
            padding: 0.5rem 0;
            top: calc(100% + 1rem);
            margin-top: -15px
        }

        .user-dropdown:hover .dropdown-content {
            display: block;
        }

        .dropdown-content a {
            color: #1a1a1a !important;
            padding: 1rem 1.5rem;
            text-decoration: none;
            display: block;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .dropdown-content a:hover {
            background: rgba(0, 200, 200, 0.1);
            color: #00c8c8 !important;
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #1a1a1a;
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }
            .mobile-menu-btn {
                display: block;
            }
        }

        /* Hero Section - Enhanced */
   .hero {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 4rem 6rem;
  min-height: 100vh;
  background: #f5faff;
}

.hero-content {
  flex: 1;
  padding-right: 2rem;
}

.hero-content h1 {
  font-size: 3rem;
  font-weight: bold;
  margin-bottom: 1rem;
  line-height: 1.2;
}

.hero-content .highlight {
  color: #00d4ff; /* Blue highlight */
}

.hero-content p {
  font-size: 1.1rem;
  color: #555;
  margin-bottom: 2rem;
  max-width: 500px;
}

.hero-buttons {
  display: flex;
  gap: 1rem;
}

.hero-buttons button {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 30px;
  font-size: 1rem;
  cursor: pointer;
}

.hero-buttons button:first-child {
  background-color: #00d4ff;
  color: white;
}

.hero-buttons button:last-child {
  border: 2px solid #00d4ff;
  color: #00d4ff;
  background: transparent;
}


.background-video {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    z-index: 0;
    opacity: 1; /* optional, to make text more readable */
}

.hero-container {
    position: relative;
    z-index: 1;
    text-align: center;
    padding: 2rem;
    color: #fff; /* make sure text color contrasts with the video */
}


        /* .hero-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            position: relative;
            z-index: 2;
        } */
 .hero-container {
    max-width: 1200px;
    width: 100%;
    margin: 0 0;
    padding: 0 rem;
    display: flex;
    justify-content: flex-start; /* aligns content to the left */
    align-items: center;
    position: relative;
    z-index: 2;
}



        /* .hero-content {
            opacity: 0;
            transform: translateY(50px);
            animation: fadeInUp 1.2s cubic-bezier(0.4, 0, 0.2, 1) 0.5s forwards;
        } */

.hero-content {
    text-align: left;
    opacity: 0;
    transform: translateY(50px);
    animation: fadeInUp 1.2s cubic-bezier(0.4, 0, 0.2, 1) 0.5s forwards;
}


        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 800;
            color: #1a1a1a;
            margin-bottom: 1.5rem;
            line-height: 1.1;
            letter-spacing: -0.02em;
        }

        .hero-content .highlight {
            background: linear-gradient(135deg, #00c8c8, #00a8a8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-content p {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 2.5rem;
            max-width: 500px;
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 1.2s cubic-bezier(0.4, 0, 0.2, 1) 0.8s forwards;
        }

        .hero-cta {
            display: flex;
            gap: 1rem;
            align-items: center;
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 1.2s cubic-bezier(0.4, 0, 0.2, 1) 1.1s forwards;
        }

        .primary-btn {
            background: linear-gradient(135deg, #00c8c8, #00a8a8);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 25px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            display: inline-block;
            position: relative;
            overflow: hidden;
        }

        .primary-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .primary-btn:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 10px 30px rgba(0, 200, 200, 0.4);
        }

        .primary-btn:hover::before {
            left: 100%;
        }

        .secondary-btn {
            background: transparent;
            color: #00c8c8;
            padding: 1rem 2rem;
            border: 2px solid #00c8c8;
            border-radius: 25px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            display: inline-block;
            position: relative;
            overflow: hidden;
        }

        .secondary-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 100%;
            background: linear-gradient(135deg, #00c8c8, #00a8a8);
            transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: -1;
        }

        .secondary-btn:hover {
            color: white;
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 8px 25px rgba(0, 200, 200, 0.3);
        }

        .secondary-btn:hover::before {
            width: 100%;
        }

        .hero-visual {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transform: scale(0.8);
            animation: scaleIn 1.5s cubic-bezier(0.4, 0, 0.2, 1) 0.8s forwards;
        }

        .pc-showcase {
            width: 400px;
            height: 400px;
            background: linear-gradient(135deg, #00c8c8, #00a8a8);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8rem;
            color: white;
            position: relative;
            animation: float 6s ease-in-out infinite;
            box-shadow: 0 30px 80px rgba(0, 200, 200, 0.3);
            overflow: hidden;
        }

        .pc-showcase::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        /* New Advertisement Sections */
        .features-showcase {
            padding: 8rem 0;
            background: #ffffff;
            position: relative;
        }

        .features-grid {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 3rem;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 3rem 2rem;
            text-align: center;
            border: 1px solid rgba(0, 200, 200, 0.1);
            transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 0;
            transform: translateY(50px);
        }

        .feature-card.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .feature-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 60px rgba(0, 200, 200, 0.15);
            background: rgba(248, 253, 255, 0.9);
        }

        .feature-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            display: block;
            background: linear-gradient(135deg, #00c8c8, #00a8a8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 1rem;
        }

        .feature-card p {
            color: #666;
            font-size: 1rem;
            line-height: 1.6;
        }

        /* Performance Stats Section */
        .stats-section {
            padding: 8rem 0;
            background: linear-gradient(135deg, #f8fdff 0%, #e6f9ff 100%);
            position: relative;
        }

        .stats-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 2rem;
            text-align: center;
        }

        .stats-header {
            margin-bottom: 4rem;
        }

        .stats-header h2 {
            font-size: 2.5rem;
            font-weight: 800;
            color: #1a1a1a;
            margin-bottom: 1rem;
        }

        .stats-header p {
            font-size: 1.2rem;
            color: #666;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 3rem;
            margin-top: 4rem;
        }

        .stat-item {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .stat-item.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            background: linear-gradient(135deg, #00c8c8, #00a8a8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
            display: block;
        }

        .stat-label {
            font-size: 1.1rem;
            color: #666;
            font-weight: 600;
        }

        /* Technology Showcase */
        .tech-showcase {
            padding: 8rem 0;
            background: #1a1a1a;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .tech-showcase::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 30% 70%, rgba(0, 200, 200, 0.1) 0%, transparent 50%);
        }

        .tech-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            position: relative;
            z-index: 2;
        }

        .tech-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .tech-header h2 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }

        .tech-header p {
            font-size: 1.2rem;
            opacity: 0.8;
        }

        .tech-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .tech-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 2rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 0;
            transform: translateY(30px);
        }

        .tech-card.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .tech-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(0, 200, 200, 0.3);
        }

        .tech-card h4 {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #00c8c8;
        }

        .tech-card p {
            opacity: 0.8;
            line-height: 1.6;
        }

        /* Products Section - Enhanced */
        .products-section {
            padding: 8rem 0;
            background: #ffffff;
        }

        .section-header {
            text-align: center;
            max-width: 800px;
            margin: 0 auto 4rem;
            padding: 0 2rem;
        }

        .section-header h2 {
            font-size: 2.5rem;
            font-weight: 800;
            color: #1a1a1a;
            margin-bottom: 1rem;
        }

        .section-header p {
            font-size: 1.2rem;
            color: #666;
        }

        .products-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .product-card {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            margin-bottom: 6rem;
            padding: 3rem;
            border-radius: 25px;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            box-shadow: 0 15px 50px rgba(0, 200, 200, 0.08);
            border: 1px solid rgba(0, 200, 200, 0.1);
            opacity: 0;
            transform: translateY(50px);
            transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .product-card.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .product-card:hover {
            transform: translateY(-15px) scale(1.02);
            box-shadow: 0 30px 80px rgba(0, 200, 200, 0.15);
        }

        .product-card:nth-child(even) {
            background: linear-gradient(135deg, rgba(248, 253, 255, 0.8) 0%, rgba(255, 255, 255, 0.8) 100%);
        }

        .product-card:nth-child(even) .product-content {
            order: 2;
        }

        .product-card:nth-child(even) .product-visual {
            order: 1;
        }

        .product-content h3 {
            font-size: 2rem;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 1rem;
        }

        .product-content p {
            color: #666;
            margin-bottom: 1.5rem;
            font-size: 1.05rem;
            line-height: 1.6;
        }

        .price-tag {
            font-size: 2.2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #00c8c8, #00a8a8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1.5rem;
        }

        .specs-list {
            list-style: none;
            margin-bottom: 2rem;
        }

        .specs-list li {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(0, 200, 200, 0.1);
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .specs-list li:hover {
            background: rgba(0, 200, 200, 0.05);
            padding-left: 1rem;
            padding-right: 1rem;
            border-radius: 8px;
        }

        .specs-list li:last-child {
            border-bottom: none;
        }

        .spec-name {
            font-weight: 600;
            color: #1a1a1a;
        }

        .spec-value {
            color: #666;
        }

        .product-visual {
            text-align: center;
            position: relative;
        }

        .pc-image-container {
            width: 280px;
            height: 280px;
            background: linear-gradient(135deg, #00c8c8, #00a8a8);
            /* background: url('homephotos/pc1.jpeg')  no-repeat center center;
            background-size:cover; */
            border-radius: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 15px 40px rgba(0, 200, 200, 0.2);
            overflow: hidden;
            position: relative;
        }
        #ar{
             background: url('homephotos/main3.jpg') no-repeat center center;
            background-size:cover;
        }
          #br{
             background: url('homephotos/main1.jpg') no-repeat center center;
            background-size:cover;
          }
        #Lr{
            background: url('homephotos/main2.jpg') no-repeat center center;
            background-size:cover;
            }



        .pc-image-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 15s linear infinite;
        }

        .pc-image-container:hover {
            transform: scale(1.08) rotateY(5deg);
            box-shadow: 0 25px 60px rgba(0, 200, 200, 0.3);
        }

        .pc-icon {
            font-size: 4rem;
            color: white;
            position: relative;
            z-index: 2;
            filter: drop-shadow(0 0 20px rgba(255,255,255,0.3));
        }

        .visual-label {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1a1a1a;
            margin-top: 1rem;
        }

        /* Cloud Build Section - Enhanced */
        .cloud-section {
            padding: 8rem 0;
            background: linear-gradient(135deg, #00c8c8, #00a8a8);
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cloud-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 70% 30%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
        }

        .cloud-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 2rem;
            position: relative;
            z-index: 2;
        }

        .cloud-section h2 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
        }

        .cloud-section p {
            font-size: 1.2rem;
            margin-bottom: 2.5rem;
            opacity: 0.9;
        }

        .cloud-btn {
            background: white;
            color: #00c8c8;
            padding: 1rem 2.5rem;
            border: none;
            border-radius: 25px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .cloud-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0,200,200,0.1), transparent);
            transition: left 0.5s;
        }

        .cloud-btn:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 15px 40px rgba(255, 255, 255, 0.3);
        }

        .cloud-btn:hover::before {
            left: 100%;
        }

        /* About Section - Enhanced */
        .about-section {
            padding: 8rem 0;
            background: linear-gradient(135deg, #f8fdff 0%, #ffffff 100%);
            position: relative;
        }

        .about-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 2rem;
            text-align: center;
        }

        .about-container h2 {
            font-size: 2.5rem;
            font-weight: 800;
            color: #1a1a1a;
            margin-bottom: 2rem;
        }

        .about-container p {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 2rem;
            line-height: 1.8;
        }

        /* Welcome message - Enhanced */
        .welcome-message {
            background: linear-gradient(135deg, #00c8c8, #00a8a8);
            color: white;
            padding: 2rem 3rem;
            border-radius: 20px;
            margin: 2rem auto;
            max-width: 600px;
            text-align: center;
            box-shadow: 0 15px 40px rgba(0, 200, 200, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            position: relative;
            overflow: hidden;
            animation: slideInScale 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .welcome-message::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        .welcome-message h3 {
            position: relative;
            z-index: 2;
            font-size: 1.3rem;
            font-weight: 700;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes slideInScale {
            from {
                opacity: 0;
                transform: translateY(-30px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Parallax Effect */
        .parallax-element {
            transition: transform 0.1s ease-out;
        }

        /* Scroll Reveal Animation */
        .reveal {
            opacity: 0;
            transform: translateY(50px);
            transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .nav-links {
                gap: 1.5rem;
            }
            
            .hero-container {
                gap: 3rem;
            }
            
            .hero-content h1 {
                font-size: 3rem;
            }
            
            .product-card {
                gap: 3rem;
            }

            .features-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 2rem;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 2rem;
            }

            .tech-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .mobile-menu-btn {
                display: block;
            }

            .nav-container {
                padding: 1rem;
            }

            .hero-container {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 2rem;
                padding: 2rem 1rem;
            }

            .hero-content h1 {
                font-size: 2.5rem;
            }

            .hero-content p {
                font-size: 1.1rem;
            }

            .hero-cta {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }

            .pc-showcase {
                width: 300px;
                height: 300px;
                font-size: 6rem;
            }

            .product-card {
                grid-template-columns: 1fr;
                gap: 2rem;
                padding: 2rem;
                margin-bottom: 3rem;
            }

            .product-card:nth-child(even) .product-content {
                order: 1;
            }

            .product-card:nth-child(even) .product-visual {
                order: 2;
            }

            .pc-image-container {
                width: 220px;
                height: 220px;
            }

            .section-header h2, .stats-header h2, .tech-header h2, .cloud-section h2, .about-container h2 {
                font-size: 2rem;
            }

            .product-content h3 {
                font-size: 1.5rem;
            }

            .price-tag {
                font-size: 1.8rem;
            }

            .cloud-section p, .section-header p, .stats-header p, .tech-header p, .about-container p {
                font-size: 1.1rem;
            }

            .welcome-message {
                margin: 1rem;
                padding: 1.5rem 2rem;
            }

            .features-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .feature-card {
                padding: 2rem 1.5rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 2rem;
            }

            .tech-grid {
                grid-template-columns: 1fr;
            }

            .tech-card {
                padding: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .hero-content h1 {
                font-size: 2rem;
            }

            .hero-content p {
                font-size: 1rem;
            }

            .pc-showcase {
                width: 250px;
                height: 250px;
                font-size: 5rem;
            }

            .product-card {
                padding: 1.5rem;
            }

            .pc-image-container {
                width: 180px;
                height: 180px;
            }

            .primary-btn, .secondary-btn, .cloud-btn {
                padding: 0.8rem 1.5rem;
                font-size: 1rem;
            }

            .user-btn {
                padding: 0.6rem 1.2rem;
                min-width: 120px;
            }

            .user-name {
                font-size: 0.8rem;
                max-width: 80px;
            }

            .dropdown-content {
                min-width: 160px;
            }

            .feature-card {
                padding: 1.5rem;
            }

            .feature-icon {
                font-size: 3rem;
            }

            .stat-number {
                font-size: 2.5rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .welcome-message {
                padding: 1.5rem;
            }

            .welcome-message h3 {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="Mainpage.php" class="logo">Build Your PC</a>

            <!-- Desktop Navigation -->
            <ul class="nav-links">
                <li><a href="#about">About</a></li>
                <li><a href="Roadmap.html">Cloud Build</a></li>
                <li><a href="product.php">Products</a></li>
                <li><a href="services.php">Services</a></li>
            </ul>

            <!-- Auth Section -->
            <div class="auth-section">
                <?php if ($isLoggedIn): ?>
                    <div class="user-dropdown">
                        <div class="user-btn">
                            <span class="user-name"><?php echo htmlspecialchars($userName); ?></span>
                            <span class="dropdown-icon">▼</span>
                        </div>
                        <div class="dropdown-content">
                            <a href="profile.php">My Profile</a>
                            <a href="ordertracker.php">My Orders</a>
                            <a href="cart.php">My Cart</a>
                            <a href="?logout=1">Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="SignupandLogin/signup.php" class="login-btn">Get Connected</a>
                <?php endif; ?>

                <!-- Mobile Menu Button -->
                <button class="mobile-menu-btn" onclick="toggleMobileMenu()">☰</button>
            </div>
        </div>
    </nav>

 

    <!-- Hero Section -->
    <!-- <section class="hero">
        <div class="hero-container">
            <div class="hero-content">
                <h1>Build Your <span class="highlight">Dream PC</span> Today</h1>
                <p>Experience ultimate performance with our custom-built gaming and workstation PCs. Professional assembly, premium components, lifetime support.</p>
                <div class="hero-cta">
                    <a href="#products" class="primary-btn">View Builds</a>
                    <a href="#cloud" class="secondary-btn">AI Builder</a>
                </div>
            </div>
          
        </div>
    </section> -->

    <section class="hero">
    <video autoplay muted loop playsinline class="background-video">
        <source src="homephotos/pcvideo1.mp4" type="video/mp4">
        Your browser does not support the video tag.
    </video>

    <div class="hero-container">
        <div class="hero-content">
            <h1>Build Your <br><span class="highlight">Dream PC</span> <br>Today</h1>
            <p>Experience ultimate performance with <br>our custom-built gaming and workstation<br> PCs. Professional assembly<br> premium components<br> lifetime support.</p>
            <div class="hero-cta">
                <a href="#products" class="primary-btn">View Builds</a>
                <a href="#cloud" class="secondary-btn">Cloud Build</a>
            </div>
        </div>
    </div>
</section>


    <!-- Features Showcase -->
    <section class="features-showcase">
        <div class="features-grid">
            <div class="feature-card reveal">
                <span class="feature-icon">⚡</span>
                <h3>Lightning Fast Performance</h3>
                <p>Experience blazing speeds with cutting-edge processors and ultra-fast NVMe storage. Every component optimized for maximum performance.</p>
            </div>
            <div class="feature-card reveal">
                <span class="feature-icon">🎯</span>
                <h3>Precision Engineering</h3>
                <p>Each PC is meticulously crafted by certified technicians with years of experience. Quality you can trust, performance you can feel.</p>
            </div>
            <div class="feature-card reveal">
                <span class="feature-icon">🛡️</span>
                <h3>Lifetime Support</h3>
                <p>Comprehensive warranty and 24/7 technical support. We're here for you every step of your PC journey.</p>
            </div>
        </div>
    </section>

    <!-- Performance Stats -->
    <section class="stats-section">
        <div class="stats-container">
            <div class="stats-header">
                <h2>Numbers That Speak</h2>
                <p>Trusted by thousands of gamers, creators, and professionals worldwide</p>
            </div>
            <div class="stats-grid">
                <div class="stat-item reveal">
                    <span class="stat-number" data-target="15000">0</span>
                    <div class="stat-label">PCs Built</div>
                </div>
                <div class="stat-item reveal">
                    <span class="stat-number" data-target="99">0</span>
                    <div class="stat-label">% Satisfaction</div>
                </div>
                <div class="stat-item reveal">
                    <span class="stat-number" data-target="24">0</span>
                    <div class="stat-label">Hour Support</div>
                </div>
                <div class="stat-item reveal">
                    <span class="stat-number" data-target="5">0</span>
                    <div class="stat-label">Year Warranty</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Technology Showcase -->
    <section class="tech-showcase">
        <div class="tech-container">
            <div class="tech-header">
                <h2>Cutting-Edge Technology</h2>
                <p>Powered by the latest innovations in computing hardware</p>
            </div>
            <div class="tech-grid">
                <div class="tech-card reveal">
                    <h4>AI-Powered Optimization</h4>
                    <p>Our proprietary AI algorithms optimize every component for your specific use case, ensuring peak performance.</p>
                </div>
                <div class="tech-card reveal">
                    <h4>Advanced Cooling Systems</h4>
                    <p>Revolutionary liquid cooling and precision airflow management keep your system running cool and quiet.</p>
                </div>
                <div class="tech-card reveal">
                    <h4>Future-Proof Design</h4>
                    <p>Built with upgrade paths in mind, ensuring your investment stays relevant for years to come.</p>
                </div>
                <div class="tech-card reveal">
                    <h4>RGB Ecosystem</h4>
                    <p>Synchronized lighting effects that respond to your system's performance and create an immersive experience.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Products Section -->
    <section class="products-section" id="products">
        <div class="section-header">
            <h2>Powerful PCs for Every Need</h2>
            <p>From budget-friendly builds to high-end workstations, we have the perfect PC configuration for your requirements.</p>
        </div>
        
        <div class="products-container">
            <div class="product-card reveal">
                <div class="product-content">
                    <h3>Gaming Beast</h3>
                    <p>Dominate every game with this high-performance gaming rig. Built for 4K gaming, streaming, and content creation with zero compromises.</p>
                    <div class="price-tag">Price starts from 4 lakhs</div>
                    <ul class="specs-list">
                        <li><span class="spec-name">Processor</span><span class="spec-value">Intel i9-13900K</span></li>
                        <li><span class="spec-name">Graphics</span><span class="spec-value">RTX 4080 16GB</span></li>
                        <li><span class="spec-name">Memory</span><span class="spec-value">32GB DDR5</span></li>
                        <li><span class="spec-name">Storage</span><span class="spec-value">2TB NVMe SSD</span></li>
                    </ul>
                    <a href="#build" class="primary-btn">Customize Build</a>
                </div>
                <div class="product-visual">
                       <div class="pc-image-container" id="Lr">
                        <span class="pc-icon"></span>
                        <img src="assets/banner-main2.jpg">

                    </div>
                    <div class="visual-label">Gaming Powerhouse</div>
                </div>
            </div>

            <div class="product-card reveal">
                <div class="product-content">
                    <h3>Creator Pro</h3>
                    <p>Unleash your creativity with professional-grade performance. Perfect for video editing, 3D rendering, and design work.</p>
                    <div class="price-tag">Price starts at 1.5 lakhs </div>
                    <ul class="specs-list">
                        <li><span class="spec-name">Processor</span><span class="spec-value">AMD Ryzen 9 7950X</span></li>
                        <li><span class="spec-name">Graphics</span><span class="spec-value">RTX 4090 24GB</span></li>
                        <li><span class="spec-name">Memory</span><span class="spec-value">64GB DDR5</span></li>
                        <li><span class="spec-name">Storage</span><span class="spec-value">4TB NVMe SSD</span></li>
                    </ul>
                    <a href="#build" class="primary-btn">Customize Build</a>
                </div>
                <div class="product-visual">
                    <div class="pc-image-container" id="ar">
                        <span class="pc-icon"></span>
                        <img src="assets/banner-main1.jpg">

                    </div>
                    <div class="visual-label">Creative Workstation</div>
                </div>
            </div>

            <div class="product-card reveal">
                <div class="product-content">
                    <h3>Budget Champion</h3>
                    <p>Get excellent performance without breaking the bank. Perfect for everyday tasks, light gaming, and productivity.</p>
                    <div class="price-tag">Price starts from 50000</div>
                    <ul class="specs-list">
                        <li><span class="spec-name">Processor</span><span class="spec-value">AMD Ryzen 5 7600X</span></li>
                        <li><span class="spec-name">Graphics</span><span class="spec-value">RTX 4060 8GB</span></li>
                        <li><span class="spec-name">Memory</span><span class="spec-value">16GB DDR5</span></li>
                        <li><span class="spec-name">Storage</span><span class="spec-value">1TB NVMe SSD</span></li>
                    </ul>
                    <a href="#build" class="primary-btn">Customize Build</a>
                </div>
                <div class="product-visual">
                        <div class="pc-image-container" id="br">
                 
                        <span class="pc-icon"><pc1 class="jpeg"></pc1></span>
                          <img src="assets/banner-main3.jpg">
                        
                    </div>
                    <div class="visual-label">Value Performance</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Cloud Build Section -->
    <section class="cloud-section" id="cloud">
        <div class="cloud-container">
            <h2>AI-Powered PC Builder</h2>
            <p>Let our advanced AI help you build the perfect PC for your needs. Just tell us what you want to do, and we'll recommend the ideal configuration.</p>
            <button class="cloud-btn" onclick="window.location.href='Roadmap.html'">Start Building</button>
        </div>
    </section>

    <!-- About Section -->
    <section class="about-section" id="about">
        <div class="about-container">
            <h2>Why Choose BuildYourPC?</h2>
            <p>We're passionate about creating the perfect PC experience for every user. Our team of experts combines years of experience with cutting-edge technology to deliver systems that exceed expectations.</p>
            <p>From consultation to assembly to support, we're with you every step of the way. Your dream PC is just a click away.</p>
        </div>
    </section>

    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Parallax effect
        window.addEventListener('scroll', function() {
            const parallaxElements = document.querySelectorAll('.parallax-element');
            const scrolled = window.pageYOffset;
            
            parallaxElements.forEach(element => {
                const rate = scrolled * -0.5;
                element.style.transform = `translateY(${rate}px)`;
            });
        });

        // Scroll reveal animation
        function revealOnScroll() {
            const reveals = document.querySelectorAll('.reveal, .feature-card, .product-card, .stat-item, .tech-card');
            
            reveals.forEach(element => {
                const windowHeight = window.innerHeight;
                const elementTop = element.getBoundingClientRect().top;
                const elementVisible = 150;
                
                if (elementTop < windowHeight - elementVisible) {
                    element.classList.add('visible');
                }
            });
        }

        window.addEventListener('scroll', revealOnScroll);

        // Counter animation for stats
        function animateCounters() {
            const counters = document.querySelectorAll('.stat-number');
            
            counters.forEach(counter => {
                const target = parseInt(counter.getAttribute('data-target'));
                const current = parseInt(counter.textContent);
                
                if (current < target) {
                    const increment = target / 100;
                    const newValue = Math.ceil(current + increment);
                    counter.textContent = newValue;
                    
                    setTimeout(() => animateCounters(), 20);
                }
            });
        }

        // Trigger counter animation when stats section is visible
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounters();
                    observer.unobserve(entry.target);
                }
            });
        });

        observer.observe(document.querySelector('.stats-section'));

        // Mobile menu functionality
        function toggleMobileMenu() {
            const mobileMenu = document.getElementById('mobileMenu');
            mobileMenu.classList.toggle('active');
        }

        function closeMobileMenu() {
            const mobileMenu = document.getElementById('mobileMenu');
            mobileMenu.classList.remove('active');
        }

        // Initial reveal check
        document.addEventListener('DOMContentLoaded', function() {
            revealOnScroll();
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>