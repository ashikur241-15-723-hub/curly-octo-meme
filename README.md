# ExpenseTrack

Personal expense & transaction tracker built with **PHP 8+**, **MySQL**, and **Bootstrap 5**. All amounts are stored and displayed in **Bangladeshi Taka (BDT)** using the ৳ symbol and grouping like `৳1,25,000.00`.

## Features

- Secure registration / login (`password_hash` / `password_verify`), CSRF tokens, prepared statements, session hardening, and user-scoped data
- Dashboard with live totals, Chart.js charts, recent transactions, and budget alerts
- Transaction CRUD (income/expense), search, filters, sorting, and pagination
- Category management (default income & expense sets + custom categories)
- Monthly overall and category budgets with remaining balance and warnings
- Reports: daily, weekly, monthly, yearly, custom range, category-wise, income vs expense
- Print-friendly statements, CSV export, and client-side PDF (jsPDF)
- Dark mode, responsive sidebar layout, JSON backup & restore

## Requirements (XAMPP)

- XAMPP with **Apache**, **MySQL/MariaDB**, and **PHP 8+**
- PHP extensions: `pdo_mysql`, `mbstring`, `json`

## Installation (XAMPP)

1. **Start** Apache and MySQL from the XAMPP Control Panel.

2. **Copy the project** into your web root, for example:
   - Windows: `C:\xampp\htdocs\ExpenseTrack`
   - macOS: `/Applications/XAMPP/htdocs/ExpenseTrack`
   - Linux: `/opt/lampp/htdocs/ExpenseTrack`

3. **Create the database**
   - Open [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
   - Import **`database/expense_tracker.sql`** (schema + sample data), **or**
   - Import `database/schema.sql` then `database/seed.sql`

4. **Configure the app**
   ```bash
   cp config/config.sample.php config/config.php
   ```
   Edit `config/config.php` if your MySQL user/password differ from XAMPP defaults (`root` / empty password).

5. **Open the app**
   - [http://localhost/ExpenseTrack/](http://localhost/ExpenseTrack/)  
   - If the folder name differs, set `app.base_path` in `config/config.php` (e.g. `'/ExpenseTrack'`).

### Demo account (from seed data)

| Field    | Value                   |
|----------|-------------------------|
| Email    | `demo@expensetrack.test` |
| Password | `Demo@1234`             |

You can also register a new account; default categories are created automatically.

## Project structure

```
config/           App configuration (sample + local config.php)
database/         SQL schema, seed, and combined dump
includes/         Bootstrap, auth, CSRF, DB, queries, layout
assets/css|js     Styles and front-end scripts
*.php             App pages (dashboard, transactions, budgets, reports, …)
```

## Security notes

- Never commit `config/config.php` with production secrets (it is gitignored).
- Passwords are stored only as bcrypt hashes.
- All money columns use `DECIMAL(12,2)` — never `FLOAT`/`DOUBLE`.

## License

Personal / educational use.
