# 🚀 VON BARBER STUDIO - MVC Setup Guide
## Complete Code for All MVC Files

**For:** `von-barbershop-mvc` folder (mvc-refactor branch)

---

## 📋 Quick Start

### Step 1: Run the Setup Script
```bash
cd c:\Users\Lenovo\Downloads\von-barbershop-mvc
..\Barbershop_booking-system\setup_mvc.bat
```

This will create folders and config files automatically!

### Step 2: Create Core Files Manually
Copy the code below into each file.

---

## 1️⃣ app/Core/Database.php

```php
<?php
namespace App\Core;

use PDO;
use PDOException;

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        $config = require __DIR__ . '/../../config/database.php';
        
        try {
            $this->connection = new PDO(
                "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}",
                $config['username'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }
}
```

---

## 2️⃣ app/Core/Controller.php

```php
<?php
namespace App\Core;

class Controller {
    // Load a view
    protected function view($view, $data = []) {
        extract($data);
        $viewPath = __DIR__ . "/../Views/{$view}.php";
        
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            die("View not found: {$view}");
        }
    }

    // Load a model
    protected function model($model) {
        $modelClass = "App\\Models\\{$model}";
        
        if (class_exists($modelClass)) {
            return new $modelClass();
        }
        
        die("Model not found: {$model}");
    }

    // Redirect to another page
    protected function redirect($url) {
        header("Location: {$url}");
        exit;
    }

    // Return JSON response
    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
```

---

## 3️⃣ app/Core/Model.php

```php
<?php
namespace App\Core;

use App\Core\Database;
use PDO;

class Model {
    protected $db;
    protected $table;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Get all records
    public function findAll() {
        $stmt = $this->db->query("SELECT * FROM {$this->table}");
        return $stmt->fetchAll();
    }

    // Find by ID
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // Find by column
    public function findBy($column, $value) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$column} = ?");
        $stmt->execute([$value]);
        return $stmt->fetch();
    }

    // Create new record
    public function create($data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($data);
    }

    // Update record
    public function update($id, $data) {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "{$key} = :{$key}";
        }
        $setString = implode(', ', $set);
        
        $sql = "UPDATE {$this->table} SET {$setString} WHERE id = :id";
        $data['id'] = $id;
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    // Delete record
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
```

---

## 4️⃣ app/Core/Router.php

```php
<?php
namespace App\Core;

class Router {
    private $routes = [];

    // Add GET route
    public function get($path, $controller) {
        $this->routes['GET'][$path] = $controller;
    }

    // Add POST route
    public function post($path, $controller) {
        $this->routes['POST'][$path] = $controller;
    }

    // Dispatch the route
    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Remove base path (adjust if needed)
        $base = '/von-barbershop-mvc/public';
        $uri = str_replace($base, '', $uri);
        $uri = trim($uri, '/');

        if (empty($uri)) {
            $uri = 'home';
        }

        // Check if route exists
        if (isset($this->routes[$method][$uri])) {
            [$controller, $action] = explode('@', $this->routes[$method][$uri]);
            $controllerClass = "App\\Controllers\\{$controller}";
            
            if (class_exists($controllerClass)) {
                $controllerInstance = new $controllerClass();
                
                if (method_exists($controllerInstance, $action)) {
                    $controllerInstance->$action();
                } else {
                    die("Method {$action} not found in {$controller}");
                }
            } else {
                die("Controller {$controller} not found");
            }
        } else {
            http_response_code(404);
            echo "<h1>404 - Page Not Found</h1>";
        }
    }
}
```

---

## 5️⃣ public/index.php

```php
<?php
// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Load routes
use App\Core\Router;

$router = new Router();

// ========================
// DEFINE ROUTES HERE
// ========================

// Home
$router->get('home', 'HomeController@index');

// Authentication
$router->get('login', 'AuthController@showLogin');
$router->post('login', 'AuthController@login');
$router->get('register', 'AuthController@showRegister');
$router->post('register', 'AuthController@register');
$router->get('logout', 'AuthController@logout');

// Booking
$router->get('booking', 'BookingController@showBooking');
$router->post('booking', 'BookingController@book');
$router->get('api/get-slots', 'BookingController@getSlots');
$router->get('my-appointments', 'BookingController@myAppointments');

// Admin
$router->get('admin/dashboard', 'AdminController@dashboard');

// ========================

// Dispatch the request
$router->dispatch();
```

---

## 6️⃣ app/Models/User.php

```php
<?php
namespace App\Models;

use App\Core\Model;

class User extends Model {
    protected $table = 'users';

    // Find user by email
    public function findByEmail($email) {
        return $this->findBy('email', $email);
    }

    // Create new user with hashed password
    public function createUser($data) {
        // Hash password
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Set default role
        if (!isset($data['role'])) {
            $data['role'] = 'customer';
        }
        
        return $this->create($data);
    }

    // Verify password
    public function verifyPassword($email, $password) {
        $user = $this->findByEmail($email);
        
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        
        return false;
    }

    // Check if email exists
    public function emailExists($email) {
        return $this->findByEmail($email) !== false;
    }
}
```

---

## 7️⃣ app/Controllers/AuthController.php (LOGIN IN MVC!)

```php
<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

class AuthController extends Controller {
    
    // Show login page
    public function showLogin() {
        $this->view('auth/login');
    }

    // Handle login
    public function login() {
        // Get form data
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        // Validate
        if (empty($email) || empty($password)) {
            $this->view('auth/login', ['error' => 'Please fill in all fields']);
            return;
        }

        // Load User model
        $userModel = $this->model('User');
        
        // Verify credentials
        $user = $userModel->verifyPassword($email, $password);

        if ($user) {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];

            // Redirect based on role
            if ($user['role'] === 'admin' || $user['role'] === 'barber') {
                $this->redirect('admin/dashboard');
            } else {
                $this->redirect('my-appointments');
            }
        } else {
            // Show error
            $this->view('auth/login', ['error' => 'Invalid email or password']);
        }
    }

    // Show registration page
    public function showRegister() {
        $this->view('auth/register');
    }

    // Handle registration
    public function register() {
        $data = [
            'name' => $_POST['name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'password' => $_POST['password'] ?? '',
            'phone' => $_POST['phone'] ?? null,
        ];

        // Validate
        if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
            $this->view('auth/register', ['error' => 'Please fill in all required fields']);
            return;
        }

        $userModel = $this->model('User');
        
        // Check if email exists
        if ($userModel->emailExists($data['email'])) {
            $this->view('auth/register', ['error' => 'Email already registered']);
            return;
        }

        // Create user
        if ($userModel->createUser($data)) {
            $this->redirect('login?success=Registration successful! Please login.');
        } else {
            $this->view('auth/register', ['error' => 'Registration failed. Please try again.']);
        }
    }

    // Handle logout
    public function logout() {
        session_destroy();
        $this->redirect('login');
    }
}
```

---

## 8️⃣ app/Views/layouts/header.php

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VON BARBER STUDIO - MVC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: #1a1a1a;
            color: #F5F0E8;
        }
        .navbar {
            background: #000000 !important;
            border-bottom: 2px solid #c0c0c0;
        }
        .navbar-brand {
            color: #c0c0c0 !important;
            font-weight: bold;
        }
        .nav-link {
            color: #F5F0E8 !important;
        }
        .nav-link:hover {
            color: #c0c0c0 !important;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="/von-barbershop-mvc/public/home">
                <strong>VON BARBER STUDIO</strong>
            </a>
            <div class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span class="nav-link">Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?></span>
                    <a class="nav-link" href="/von-barbershop-mvc/public/logout">Logout</a>
                <?php else: ?>
                    <a class="nav-link" href="/von-barbershop-mvc/public/login">Login</a>
                    <a class="nav-link" href="/von-barbershop-mvc/public/register">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
```

---

## 9️⃣ app/Views/layouts/footer.php

```php
    </div>
    
    <footer class="mt-5 py-4 text-center" style="background: #000; border-top: 1px solid #c0c0c0;">
        <p class="mb-0" style="color: #c0c0c0;">
            &copy; <?= date('Y') ?> VON BARBER STUDIO - MVC Version
        </p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```

---

## 🔟 app/Views/auth/login.php (YOUR LOGIN PAGE IN MVC!)

```php
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6 col-lg-4">
        <div class="card" style="background: #2d2d2d; border: 1px solid #c0c0c0;">
            <div class="card-header text-center" style="background: #1a1a1a; border-bottom: 2px solid #c0c0c0;">
                <h4 style="color: #c0c0c0; margin: 0;">
                    <i class="bi bi-box-arrow-in-right"></i> Login
                </h4>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger" style="background: rgba(220, 53, 69, 0.2); border-color: #dc3545; color: #fff;">
                        <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success" style="background: rgba(40, 167, 69, 0.2); border-color: #28a745; color: #fff;">
                        <i class="bi bi-check-circle"></i> <?= htmlspecialchars($_GET['success']) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/von-barbershop-mvc/public/login">
                    <div class="mb-3">
                        <label style="color: #c0c0c0; font-weight: 600;">
                            <i class="bi bi-envelope"></i> Email Address
                        </label>
                        <input type="email" name="email" class="form-control" 
                               style="background: #1a1a1a; border: 1px solid #c0c0c0; color: #fff;" 
                               placeholder="your@email.com" required>
                    </div>

                    <div class="mb-3">
                        <label style="color: #c0c0c0; font-weight: 600;">
                            <i class="bi bi-lock"></i> Password
                        </label>
                        <input type="password" name="password" class="form-control" 
                               style="background: #1a1a1a; border: 1px solid #c0c0c0; color: #fff;" 
                               placeholder="Enter your password" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100" 
                            style="background: linear-gradient(135deg, #c0c0c0, #ffffff); border: none; color: #000; font-weight: 600;">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </button>
                </form>

                <div class="mt-3 text-center">
                    <p style="color: #c0c0c0;">
                        Don't have an account? 
                        <a href="/von-barbershop-mvc/public/register" style="color: #ffffff;">Register here</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
```

---

## 🎯 How to Test Your MVC Login

### Step 1: Start Local Server

```bash
cd c:\Users\Lenovo\Downloads\von-barbershop-mvc

# Using PHP built-in server
php -S localhost:8080 -t public
```

### Step 2: Open Browser

Visit: **http://localhost:8080/login**

### Step 3: You Should See:
- ✅ Beautiful login page with your branding
- ✅ Email and password fields
- ✅ Login button
- ✅ Link to register page

### Step 4: Test Login
- Enter your admin credentials
- Should redirect to admin dashboard
- Session should work!

---

## 📊 What You Just Learned

### Old Way (login.php):
```php
// Everything in ONE file ❌
<?php
session_start();
require 'config/db.php';
// HTML here
// PHP logic here
// Database queries here
// Redirect here
?>
```

### New Way (MVC):
```
✅ Router (public/index.php) → Defines URL routes
✅ Controller (AuthController.php) → Handles logic
✅ Model (User.php) → Database operations
✅ View (login.php) → Pure HTML/CSS
```

**Benefits:**
- ✅ Clean separation
- ✅ Easy to maintain
- ✅ Reusable code
- ✅ Professional structure

---

## 🚀 Next Steps

### 1. Test the Login
Make sure it works!

### 2. Convert Other Pages
Use the same pattern:
- `register.php` → AuthController@showRegister
- `book.php` → BookingController@showBooking
- `admin_dashboard.php` → AdminController@dashboard

### 3. Follow the Roadmap
Check `LEARNING_ROADMAP.md` for week-by-week plan!

---

## 💡 Key Differences to Notice

| Feature | Old (login.php) | New (MVC) |
|---------|----------------|-----------|
| **File location** | Root folder | app/Views/auth/ |
| **Database** | require 'config/db.php' | $this->model('User') |
| **Logic** | Mixed with HTML | In AuthController |
| **Routing** | Direct file access | Router handles URLs |
| **Session** | Manual session_start() | In public/index.php |
| **Redirect** | header() | $this->redirect() |

---

## ✅ Checklist

- [ ] Created folder structure
- [ ] Created all 10 files above
- [ ] Started PHP server
- [ ] Tested login at http://localhost:8080/login
- [ ] Login works and redirects correctly

---

**Congratulations! You just converted your first page to MVC!** 🎉

**Next:** Convert `register.php`, then `book.php`, and so on!

---

**Questions?** Check `LEARNING_ROADMAP.md` or `MIGRATION_GUIDE.md` for more help!
