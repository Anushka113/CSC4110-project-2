🧹 CSC4110 Project 2 — Home Cleaning Service Management System

A complete web-based management system built for Anna Johnson’s Home Cleaning Service.
The application supports client requests, billing, quotes, admin management, and secure login system using PHP + MySQL.

Developed for CSC 4110 – Software Engineering at Wayne State University.

🔥 Features Overview
👩‍💼 Admin (Anna) Features

View all cleaning requests (anna_requests.php)

View and manage all client orders (anna_orders.php)

Generate bills (bill_generate.php)

View billing history (anna_billing.php)

Admin dashboard (dashboard_anna.php)

View and manage client quotes (quote_anna.php)

👤 Client Features

Register and Login (register.php, login.php)

Submit cleaning service requests (new_request.php)

View quotes (client_quotes.php)

View bills (client_bills.php)

Logout (logout.php)

⚙ System Features

Secure login with hashed passwords

MySQL database-powered backend

Request → Quote → Billing workflow

File upload support for attachments

Organized admin & client portals

Clean, functional PHP project structure

🛠️ Technologies Used

PHP 8+

MySQL / MariaDB

Apache (XAMPP)

HTML, CSS, JavaScript

Git / GitHub

VS Code

🚀 Installation & Setup (Localhost – XAMPP)
1️⃣ Move the project into XAMPP

Put the folder inside:

C:\xampp\htdocs\CSC4110-project-2


Or clone it directly:

cd /c/xampp/htdocs
git clone https://github.com/Anushka113/CSC4110-project-2.git

2️⃣ Start XAMPP Services

Open XAMPP Control Panel → click Start for:

✔ Apache

✔ MySQL

Both must stay green.

3️⃣ Create MySQL Database

Visit:

http://localhost/phpmyadmin


Create a new database:

cleaning_db


Import the schema:

Go to Import

Select schema.sql

Click Go

4️⃣ Configure Database Connection

Open db.php and update credentials:

$servername = "localhost";
$username = "root";  // default for XAMPP
$password = "";      // empty password
$dbname = "cleaning_db";

5️⃣ Run the Website

Open:

http://localhost/CSC4110-project-2/index.php


or for home page:

http://localhost/CSC4110-project-2/home.php

📁 Project Structure
CSC4110-project-2/
│
├── index.php
├── home.php
├── login.php
├── logout.php
├── register.php
├── db.php
│
├── new_request.php
├── client_quotes.php
├── client_bills.php
│
├── anna_requests.php
├── anna_orders.php
├── anna_billing.php
├── quote_anna.php
├── dashboard_anna.php
├── bill_generate.php
│
├── uploads/               # Uploaded files
├── requests/              # Request attachments (if used)
│
├── schema.sql             # Database structure
└── README.md

🧪 Testing Instructions
✔ Functional Tests

Can a client register & log in?

Can a client submit a new cleaning request?

Can Anna view all requests?

Can Anna create quotes/bills?

Can clients view bills/quotes?

✔ Database Tests

Tables imported correctly

Records being inserted

Foreign keys linking clients ↔ requests ↔ billing

🔄 Git Commands

In the project folder:

git status
git add .
git commit -m "Updated request handling and billing pages"
git push
