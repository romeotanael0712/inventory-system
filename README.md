# Inventory Management System (PHP + PostgreSQL + Docker)

Isang simpleng web-based Inventory Management System na may real-time error handling, offline alert validation, at duplicate prevention.

## 🚀 Features
- **CRUD Operations**: Add, Edit, Delete, at Search ng inventory items.
- **Offline & Network Alert**: Nagpapakita ng error dialog (SweetAlert2) kapag offline ang user o offline ang server.
- **Duplicate Prevention**: Hinaharang ang pagdaragdag ng mga item na may kaparehong pangalan (case-insensitive).
- **Data Validation**: Bawal ang negatibong numero sa Quantity at Price.
- **Dockerized Setup**: Gumagamit ng Nginx, PHP-FPM, at PostgreSQL containers.

## 🛠️ Tech Stack
- **Frontend**: HTML5, Bootstrap 5, SweetAlert2, JavaScript (Fetch API)
- **Backend**: PHP 8.x
- **Database**: PostgreSQL
- **Environment**: Docker & Docker Compose

## 📦 How to Run
1. Clone the repository:
   ```bash
   git clone [https://github.com/YOUR_USERNAME/YOUR_REPOSITORY_NAME.git](https://github.com/YOUR_USERNAME/YOUR_REPOSITORY_NAME.git)