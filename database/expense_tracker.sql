-- ExpenseTrack schema
-- Database: expense_tracker
-- Compatible with MySQL 5.7+ / MariaDB (XAMPP)

CREATE DATABASE IF NOT EXISTS expense_tracker
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE expense_tracker;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS budgets;
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  user_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(190) NOT NULL,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories (
  category_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  category_name VARCHAR(100) NOT NULL,
  category_type ENUM('income', 'expense') NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (category_id),
  UNIQUE KEY uq_user_category (user_id, category_name, category_type),
  KEY idx_categories_user_type (user_id, category_type),
  CONSTRAINT fk_categories_user
    FOREIGN KEY (user_id) REFERENCES users (user_id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE transactions (
  transaction_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NOT NULL,
  transaction_type ENUM('income', 'expense') NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  description VARCHAR(255) NOT NULL DEFAULT '',
  payment_method VARCHAR(50) NOT NULL DEFAULT 'Cash',
  transaction_date DATE NOT NULL,
  note TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (transaction_id),
  KEY idx_tx_user_date (user_id, transaction_date),
  KEY idx_tx_user_type (user_id, transaction_type),
  KEY idx_tx_category (category_id),
  KEY idx_tx_payment (user_id, payment_method),
  CONSTRAINT fk_transactions_user
    FOREIGN KEY (user_id) REFERENCES users (user_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_transactions_category
    FOREIGN KEY (category_id) REFERENCES categories (category_id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE budgets (
  budget_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NULL COMMENT 'NULL = overall monthly expense budget',
  budget_amount DECIMAL(12,2) NOT NULL,
  budget_month CHAR(7) NOT NULL COMMENT 'YYYY-MM',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (budget_id),
  UNIQUE KEY uq_budget_scope (user_id, budget_month, category_id),
  KEY idx_budgets_user_month (user_id, budget_month),
  CONSTRAINT fk_budgets_user
    FOREIGN KEY (user_id) REFERENCES users (user_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_budgets_category
    FOREIGN KEY (category_id) REFERENCES categories (category_id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- ExpenseTrack sample data
-- Demo login: demo@expensetrack.test / Demo@1234
USE expense_tracker;

SET NAMES utf8mb4;

DELETE FROM budgets;
DELETE FROM transactions;
DELETE FROM categories;
DELETE FROM users;

-- password_hash for Demo@1234 (bcrypt)
INSERT INTO users (user_id, name, email, password, created_at) VALUES
(1, 'Demo User', 'demo@expensetrack.test', '$2y$12$MFXkzqqkaqXL9vTWcpqfv.F9htMzIE6ECZ.3DdCIzTJSjaWfYKuxe', NOW());

INSERT INTO categories (category_id, user_id, category_name, category_type) VALUES
(1, 1, 'Food', 'expense'),
(2, 1, 'Transport', 'expense'),
(3, 1, 'Education', 'expense'),
(4, 1, 'Shopping', 'expense'),
(5, 1, 'Health', 'expense'),
(6, 1, 'Rent', 'expense'),
(7, 1, 'Electricity', 'expense'),
(8, 1, 'Internet', 'expense'),
(9, 1, 'Entertainment', 'expense'),
(10, 1, 'Mobile Recharge', 'expense'),
(11, 1, 'Personal', 'expense'),
(12, 1, 'Others', 'expense'),
(13, 1, 'Salary', 'income'),
(14, 1, 'Freelancing', 'income'),
(15, 1, 'Business', 'income'),
(16, 1, 'Scholarship', 'income'),
(17, 1, 'Gift', 'income'),
(18, 1, 'Other Income', 'income');

INSERT INTO transactions
(user_id, category_id, transaction_type, amount, description, payment_method, transaction_date, note) VALUES
(1, 13, 'income', 55000.00, 'Monthly salary', 'Bank', DATE_FORMAT(CURDATE(), '%Y-%m-01'), 'Credited to salary account'),
(1, 14, 'income', 12500.00, 'Website redesign project', 'bKash', DATE_SUB(CURDATE(), INTERVAL 12 DAY), NULL),
(1, 14, 'income', 8000.00, 'Bugfix retainer', 'Nagad', DATE_SUB(CURDATE(), INTERVAL 5 DAY), NULL),
(1, 17, 'income', 2000.00, 'Birthday gift', 'Cash', DATE_SUB(CURDATE(), INTERVAL 20 DAY), NULL),
(1, 13, 'income', 55000.00, 'Previous month salary', 'Bank', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01'), NULL),
(1, 15, 'income', 9500.00, 'Small shop profit', 'Cash', DATE_SUB(CURDATE(), INTERVAL 40 DAY), NULL),

(1, 6, 'expense', 15000.00, 'House rent', 'Bank', DATE_FORMAT(CURDATE(), '%Y-%m-02'), 'Paid to landlord'),
(1, 1, 'expense', 850.00, 'Grocery shopping', 'Cash', CURDATE(), NULL),
(1, 1, 'expense', 420.00, 'Lunch at office', 'bKash', DATE_SUB(CURDATE(), INTERVAL 1 DAY), NULL),
(1, 2, 'expense', 150.00, 'CNG to office', 'Cash', CURDATE(), NULL),
(1, 2, 'expense', 80.00, 'Bus fare', 'Cash', DATE_SUB(CURDATE(), INTERVAL 2 DAY), NULL),
(1, 8, 'expense', 1200.00, 'Home broadband bill', 'Card', DATE_SUB(CURDATE(), INTERVAL 3 DAY), NULL),
(1, 7, 'expense', 2100.00, 'Electricity bill', 'bKash', DATE_SUB(CURDATE(), INTERVAL 7 DAY), NULL),
(1, 10, 'expense', 399.00, 'Mobile recharge', 'Nagad', DATE_SUB(CURDATE(), INTERVAL 4 DAY), NULL),
(1, 4, 'expense', 3200.00, 'Clothes shopping', 'Card', DATE_SUB(CURDATE(), INTERVAL 9 DAY), NULL),
(1, 5, 'expense', 750.00, 'Pharmacy medicines', 'Cash', DATE_SUB(CURDATE(), INTERVAL 6 DAY), NULL),
(1, 9, 'expense', 600.00, 'Movie tickets', 'bKash', DATE_SUB(CURDATE(), INTERVAL 8 DAY), NULL),
(1, 3, 'expense', 4500.00, 'Course fee installment', 'Bank', DATE_SUB(CURDATE(), INTERVAL 15 DAY), NULL),
(1, 11, 'expense', 500.00, 'Haircut and toiletries', 'Cash', DATE_SUB(CURDATE(), INTERVAL 11 DAY), NULL),
(1, 12, 'expense', 250.00, 'Stationery', 'Cash', DATE_SUB(CURDATE(), INTERVAL 14 DAY), NULL),
(1, 1, 'expense', 1100.00, 'Weekly bazaar', 'Cash', DATE_SUB(CURDATE(), INTERVAL 10 DAY), NULL),
(1, 2, 'expense', 300.00, 'Uber ride', 'Rocket', DATE_SUB(CURDATE(), INTERVAL 13 DAY), NULL),
(1, 6, 'expense', 15000.00, 'Previous month rent', 'Bank', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-02'), NULL),
(1, 1, 'expense', 980.00, 'Previous month groceries', 'Cash', DATE_SUB(CURDATE(), INTERVAL 25 DAY), NULL),
(1, 9, 'expense', 450.00, 'Streaming subscription', 'Card', DATE_SUB(CURDATE(), INTERVAL 28 DAY), NULL);

-- Overall + category budgets for current month
INSERT INTO budgets (user_id, category_id, budget_amount, budget_month) VALUES
(1, NULL, 40000.00, DATE_FORMAT(CURDATE(), '%Y-%m')),
(1, 1, 8000.00, DATE_FORMAT(CURDATE(), '%Y-%m')),
(1, 2, 3000.00, DATE_FORMAT(CURDATE(), '%Y-%m')),
(1, 6, 15000.00, DATE_FORMAT(CURDATE(), '%Y-%m')),
(1, 4, 5000.00, DATE_FORMAT(CURDATE(), '%Y-%m'));

ALTER TABLE users AUTO_INCREMENT = 2;
ALTER TABLE categories AUTO_INCREMENT = 19;
