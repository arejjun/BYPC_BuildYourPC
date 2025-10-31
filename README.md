# BYPC (BuildYourPC) - Role-Based File Structure

## Project Overview
BYPC is a PC component e-commerce platform that connects customers with local computer hardware shops. The application supports multi-role functionality with admin, shop owner, and customer interfaces.

## New File Structure

```
mini_project/
├── index.php                 # Main entry point
├── README.md                # This documentation
├── roles/                   # Role-based organization
│   ├── admin/              # Admin-specific files
│   │   ├── admin.php       # Admin dashboard
│   │   └── admindatabase.php
│   ├── shop_owner/         # Shop owner files
│   │   ├── shop_register.php
│   │   └── databaseproduct.php
│   ├── customer/           # Customer-facing files
│   │   ├── Mainpage.php    # Main landing page
│   │   ├── product.php     # Product catalog
│   │   ├── productinner.php
│   │   ├── cart.php
│   │   ├── placeorder.php
│   │   ├── ordertracker.php
│   │   ├── profile.php
│   │   ├── cancelorder.php
│   │   ├── returnorder.php
│   │   └── paymentdummy.html
│   └── common/             # Shared files
│       ├── login.php       # Authentication
│       ├── signup.php      # Registration
│       ├── db.php          # Database connection
│       ├── index.html      # Landing page
│       └── Roadmap.html    # Cloud builder
├── assets/                 # Static assets
│   ├── css/               # Stylesheets
│   ├── js/                # JavaScript files
│   ├── images/            # Static images
│   │   └── homephotos/    # Homepage images
│   └── uploads/           # User uploads
└── database/              # Database files
    ├── pcb (2).sql        # Main database schema
    └── SQL.sql            # Additional SQL scripts
```

## User Roles & Access

### Admin (`/roles/admin/`)
- **admin.php**: Complete admin dashboard
- **admindatabase.php**: Admin database operations
- **Access**: Restricted to users with `role = 'admin'`

### Shop Owner (`/roles/shop_owner/`)
- **shop_register.php**: Shop registration and management
- **databaseproduct.php**: Product management
- **Access**: Restricted to users with `role = 'shop_owner'`

### Customer (`/roles/customer/`)
- **Mainpage.php**: Main landing page
- **product.php**: Product browsing and filtering
- **productinner.php**: Individual product details
- **cart.php**: Shopping cart functionality
- **placeorder.php**: Order placement
- **ordertracker.php**: Order tracking
- **profile.php**: User profile management
- **cancelorder.php**: Order cancellation
- **returnorder.php**: Product returns
- **Access**: Available to all logged-in customers

### Common/Shared (`/roles/common/`)
- **login.php**: User authentication
- **signup.php**: New user registration
- **db.php**: Database connection configuration
- **index.html**: Public landing page
- **Roadmap.html**: AI PC builder tool

## Database Structure
- **Database Name**: `pcb`
- **3NF Compliant**: ✅ Yes
- **Tables**: users, shops, products, orders, order_items, reviews, product_images, etc.

## Key Features
- **Multi-role authentication system**
- **Role-based access control**
- **Product catalog with filtering**
- **Order management system**
- **Admin shop approval workflow**
- **Review and rating system**
- **Responsive design**

## Setup Instructions
1. Import database schema from `database/pcb (2).sql`
2. Update database credentials in `roles/common/db.php`
3. Ensure proper file permissions for uploads directory
4. Access application through `index.php`

## Authentication Flow
1. Users register through `roles/common/signup.php`
2. Login via `roles/common/login.php`
3. Redirected based on role:
   - Admin → `roles/admin/admin.php`
   - Shop Owner → `roles/shop_owner/shop_register.php`
   - Customer → `roles/customer/Mainpage.php`

## Security Features
- **Password hashing** using PHP's `password_verify()`
- **SQL injection protection** with prepared statements
- **Session management** for authentication
- **Role-based access control**
- **Input validation** and sanitization