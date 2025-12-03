-- CSC4110 Project 2 - Home Cleaning Service
-- Database schema

CREATE TABLE Client (
    client_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    address VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    credit_card_last4 CHAR(4),
    registration_date DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE ServiceRequest (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    service_address VARCHAR(255) NOT NULL,
    cleaning_type ENUM('basic', 'deep', 'move-out') NOT NULL,
    num_rooms INT NOT NULL,
    preferred_datetime DATETIME NOT NULL,
    proposed_budget DECIMAL(10,2),
    notes TEXT,
    status ENUM('pending', 'rejected', 'accepted', 'canceled') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES Client(client_id)
);

CREATE TABLE RequestPhoto (
    photo_id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES ServiceRequest(request_id)
);

CREATE TABLE Quote (
    quote_id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    status ENUM('pending-client', 'accepted', 'rejected', 'canceled') DEFAULT 'pending-client',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES ServiceRequest(request_id)
);

CREATE TABLE QuoteMessage (
    quote_msg_id INT AUTO_INCREMENT PRIMARY KEY,
    quote_id INT NOT NULL,
    sender_type ENUM('client','anna') NOT NULL,
    adjusted_price DECIMAL(10,2),
    scheduled_time_window VARCHAR(100),
    note TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quote_id) REFERENCES Quote(quote_id)
);

CREATE TABLE `Order` (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    quote_id INT NOT NULL,
    client_id INT NOT NULL,
    order_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('scheduled', 'completed', 'canceled') DEFAULT 'scheduled',
    FOREIGN KEY (request_id) REFERENCES ServiceRequest(request_id),
    FOREIGN KEY (quote_id) REFERENCES Quote(quote_id),
    FOREIGN KEY (client_id) REFERENCES Client(client_id)
);

CREATE TABLE Bill (
    bill_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('unpaid', 'paid', 'disputed') DEFAULT 'unpaid',
    FOREIGN KEY (order_id) REFERENCES `Order`(order_id)
);

CREATE TABLE BillMessage (
    bill_msg_id INT AUTO_INCREMENT PRIMARY KEY,
    bill_id INT NOT NULL,
    sender_type ENUM('client','anna') NOT NULL,
    adjusted_amount DECIMAL(10,2),
    note TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bill_id) REFERENCES Bill(bill_id)
);

CREATE TABLE Payment (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    bill_id INT NOT NULL,
    paid_amount DECIMAL(10,2) NOT NULL,
    paid_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    payment_method ENUM('credit_card') DEFAULT 'credit_card',
    status ENUM('successful','failed') DEFAULT 'successful',
    FOREIGN KEY (bill_id) REFERENCES Bill(bill_id)
);
