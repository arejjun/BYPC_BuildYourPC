-- BYPC Database Schema

USE pcb;

-- USERS Table
CREATE TABLE Users (
    user_id INT(11) AUTO_INCREMENT PRIMARY KEY,x
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone_number VARCHAR(15) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','shop_owner','customer') NOT NULL,
    user_status ENUM('active','banned') NOT NULL DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- SHOPS Table
CREATE TABLE Shops (
    shop_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    owner_id INT(11) NOT NULL,
    shop_name VARCHAR(100) NOT NULL,
    phone_number VARCHAR(15) NOT NULL,
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255),
    district VARCHAR(50) NOT NULL,
    status ENUM('pending','approved','rejected','banned') NOT NULL DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

-- PRODUCTS Table
CREATE TABLE Products (
    product_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    shop_id INT(11) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    brand VARCHAR(100),
    category ENUM(
        'CPU','GPU','Motherboard','RAM','Storage',
        'PSU','Cabinet','Cooling','Monitor','Peripherals'
    ) NOT NULL,
    stock INT DEFAULT 0,
    availability BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES Shops(shop_id) ON DELETE CASCADE
);

-- PRODUCT IMAGES Table
CREATE TABLE Product_Images (
    image_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    product_id INT(11) NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    FOREIGN KEY (product_id) REFERENCES Products(product_id) ON DELETE CASCADE
);

-- ORDERS Table
CREATE TABLE Orders (
    order_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    customer_id INT(11) NOT NULL,
    shop_id INT(11) NOT NULL,
    delivery_address TEXT,
    status ENUM('pending','confirmed','shipped','delivered','cancelled') DEFAULT 'pending',
    total_amount DECIMAL(10,2) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (shop_id) REFERENCES Shops(shop_id) ON DELETE CASCADE
);

-- ORDER ITEMS Table
CREATE TABLE Order_Items (
    order_item_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    order_id INT(11) NOT NULL,
    product_id INT(11) NOT NULL,
    quantity INT(11) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES Orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES Products(product_id) ON DELETE CASCADE
);

-- REVIEWS Table
CREATE TABLE Reviews (
    review_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    order_id INT(11) NOT NULL,
    customer_id INT(11) NOT NULL,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES Orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

-- REVIEW IMAGES Table
CREATE TABLE Review_Images (
    review_image_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    review_id INT(11) NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    FOREIGN KEY (review_id) REFERENCES Reviews(review_id) ON DELETE CASCADE
);

-- SEARCH HISTORY Table
CREATE TABLE Search_History (
    search_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    search_term VARCHAR(100) NOT NULL,
    product_id INT(11),
    shop_id INT(11),
    searched_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES Products(product_id) ON DELETE SET NULL,
    FOREIGN KEY (shop_id) REFERENCES Shops(shop_id) ON DELETE SET NULL
);

-- USER FAVORITES Table
CREATE TABLE User_Favorites (
    favorite_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    product_id INT(11),
    shop_id INT(11),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY uniq_user_product (user_id, product_id),
    
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES Products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (shop_id) REFERENCES Shops(shop_id) ON DELETE CASCADE
);
