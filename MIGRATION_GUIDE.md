# 🚀 VON BARBER STUDIO - Migration Guide
## From Plain PHP to MVC Architecture & Laravel Framework

**Author:** Dhon Marck V. Hermosura  
**Certified IT Specialist & AI Prompt Engineer**  
**Date:** January 2026

---

## 📋 Table of Contents

1. [Migration Overview](#migration-overview)
2. [Option A: Custom MVC Architecture](#option-a-custom-mvc-architecture)
3. [Option B: Laravel Framework](#option-b-laravel-framework)
4. [Recommended Migration Path](#recommended-migration-path)
5. [Timeline & Effort Estimation](#timeline--effort-estimation)
6. [Next Steps](#next-steps)

---

## 📊 Migration Overview

### Current Architecture (Plain PHP)
```
Barbershop_booking-system/
├── book.php              (HTML + PHP logic + DB queries mixed)
├── login.php             (HTML + PHP logic + DB queries mixed)
├── register.php          (HTML + PHP logic + DB queries mixed)
├── admin_dashboard.php   (HTML + PHP logic + DB queries mixed)
├── process_booking.php   (Business logic)
├── api/
│   ├── get_slots.php     (API endpoints)
│   ├── update_status.php
│   └── ...
├── config/
│   ├── db.php            (Database connection)
│   ├── mailer.php        (Email configuration)
│   └── session.php       (Session handling)
└── includes/
    ├── header.php
    └── footer.php
```

**Problems:**
- ❌ Mixed concerns (HTML + PHP + DB logic)
- ❌ Hard to maintain and test
- ❌ Code duplication
- ❌ No separation of concerns
- ❌ Difficult to scale

---

## Option A: Custom MVC Architecture

### Step 1: Create MVC Folder Structure

```
Barbershop_booking-system/
├── app/
│   ├── Core/
│   │   ├── Database.php          (PDO wrapper)
│   │   ├── Controller.php        (Base controller)
│   │   ├── Model.php             (Base model)
│   │   ├── View.php              (View renderer)
│   │   └── Router.php            (URL router)
│   ├── Controllers/
│   │   ├── AuthController.php    (Login, Register, Logout)
│   │   ├── BookingController.php (Book appointments)
│   │   ├── AdminController.php   (Admin dashboard)
│   │   └── ApiController.php     (API endpoints)
│   ├── Models/
│   │   ├── User.php              (User operations)
│   │   ├── Appointment.php       (Appointment operations)
│   │   └── Service.php           (Service operations)
│   └── Views/
│       ├── layouts/
│       │   ├── header.php
│       │   ├── footer.php
│       │   └── main.php
│       ├── auth/
│       │   ├── login.php         (Pure HTML)
│       │   └── register.php      (Pure HTML)
│       ├── booking/
│       │   └── book.php          (Pure HTML)
│       └── admin/
│           └── dashboard.php     (Pure HTML)
├── public/
│   ├── index.php                 (Entry point)
│   ├── .htaccess                 (URL rewriting)
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   └── images/
│   └── api/                      (AJAX endpoints)
├── config/
│   ├── database.php              (DB credentials)
│   ├── app.php                   (App settings)
│   └── mailer.php                (Email config)
└── vendor/                       (Dependencies)
```

### Step 2: Create Core Classes

#### **app/Core/Database.php**
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
                "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4",
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

#### **app/Core/Controller.php**
```php
<?php
namespace App\Core;

class Controller {
    protected function view($view, $data = []) {
        extract($data);
        $viewPath = __DIR__ . "/../Views/{$view}.php";
        
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            die("View not found: {$view}");
        }
    }

    protected function model($model) {
        $modelClass = "App\\Models\\{$model}";
        
        if (class_exists($modelClass)) {
            return new $modelClass();
        }
        
        die("Model not found: {$model}");
    }

    protected function redirect($url) {
        header("Location: {$url}");
        exit;
    }

    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
```

#### **app/Core/Model.php**
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

    public function findAll() {
        $stmt = $this->db->query("SELECT * FROM {$this->table}");
        return $stmt->fetchAll();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($data);
    }

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

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
```

### Step 3: Create Models

#### **app/Models/User.php**
```php
<?php
namespace App\Models;

use App\Core\Model;

class User extends Model {
    protected $table = 'users';

    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function createUser($data) {
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        return $this->create($data);
    }

    public function verifyPassword($email, $password) {
        $user = $this->findByEmail($email);
        
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        
        return false;
    }
}
```

#### **app/Models/Appointment.php**
```php
<?php
namespace App\Models;

use App\Core\Model;

class Appointment extends Model {
    protected $table = 'appointments';

    public function getAvailableSlots($date) {
        // Get booked slots
        $stmt = $this->db->prepare(
            "SELECT appointment_time FROM {$this->table} 
             WHERE appointment_date = ? AND status != 'cancelled'"
        );
        $stmt->execute([$date]);
        $booked = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Generate all slots (9 AM - 5 PM)
        $allSlots = [];
        for ($hour = 9; $hour <= 16; $hour++) {
            foreach (['00', '30'] as $minute) {
                $time = sprintf('%02d:%s:00', $hour, $minute);
                $allSlots[] = $time;
            }
        }

        // Mark availability
        $slots = [];
        foreach ($allSlots as $time) {
            $slots[] = [
                'time' => $time,
                'available' => !in_array($time, $booked)
            ];
        }

        return $slots;
    }

    public function getUserAppointments($userId) {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY appointment_date DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
```

### Step 4: Create Controllers

#### **app/Controllers/AuthController.php**
```php
<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

class AuthController extends Controller {
    public function showLogin() {
        $this->view('auth/login');
    }

    public function login() {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $userModel = $this->model('User');
        $user = $userModel->verifyPassword($email, $password);

        if ($user) {
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];

            // Redirect based on role
            if ($user['role'] === 'admin' || $user['role'] === 'barber') {
                $this->redirect('/admin/dashboard');
            } else {
                $this->redirect('/my-appointments');
            }
        } else {
            $this->view('auth/login', ['error' => 'Invalid credentials']);
        }
    }

    public function logout() {
        session_start();
        session_destroy();
        $this->redirect('/login');
    }
}
```

#### **app/Controllers/BookingController.php**
```php
<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Appointment;

class BookingController extends Controller {
    public function showBooking() {
        // Check authentication
        session_start();
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $this->view('booking/book', ['user' => $_SESSION]);
    }

    public function book() {
        session_start();
        
        $data = [
            'user_id' => $_SESSION['user_id'],
            'haircut_description' => $_POST['haircut_description'] ?? '',
            'location' => $_POST['location'] ?? '',
            'appointment_date' => $_POST['appointment_date'] ?? '',
            'appointment_time' => $_POST['appointment_time'] ?? '',
            'status' => 'pending'
        ];

        $appointmentModel = $this->model('Appointment');
        
        if ($appointmentModel->create($data)) {
            // Send email notification
            require_once __DIR__ . '/../../config/mailer.php';
            sendBookingConfirmationEmail($_SESSION['user_email'], $_SESSION['user_name'], $data);

            $this->redirect('/my-appointments?success=Booking confirmed! Check your email.');
        } else {
            $this->redirect('/booking?error=Failed to book appointment');
        }
    }

    public function getSlots() {
        $date = $_GET['date'] ?? '';
        
        $appointmentModel = $this->model('Appointment');
        $slots = $appointmentModel->getAvailableSlots($date);

        $this->json(['slots' => $slots]);
    }
}
```

### Step 5: Create Router

#### **app/Core/Router.php**
```php
<?php
namespace App\Core;

class Router {
    private $routes = [];

    public function get($path, $controller) {
        $this->routes['GET'][$path] = $controller;
    }

    public function post($path, $controller) {
        $this->routes['POST'][$path] = $controller;
    }

    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Remove base path if needed
        $base = '/Barbershop_booking-system/public';
        $uri = str_replace($base, '', $uri);

        if (isset($this->routes[$method][$uri])) {
            [$controller, $action] = explode('@', $this->routes[$method][$uri]);
            $controllerClass = "App\\Controllers\\{$controller}";
            $controllerInstance = new $controllerClass();
            $controllerInstance->$action();
        } else {
            http_response_code(404);
            echo "404 - Page Not Found";
        }
    }
}
```

### Step 6: Create Entry Point

#### **public/index.php**
```php
<?php
// Autoloader
spl_autoload_register(function ($class) {
    $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $file = __DIR__ . "/../{$class}.php";
    
    if (file_exists($file)) {
        require $file;
    }
});

// Load routes
use App\Core\Router;

$router = new Router();

// Auth routes
$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->get('/logout', 'AuthController@logout');

// Booking routes
$router->get('/booking', 'BookingController@showBooking');
$router->post('/booking', 'BookingController@book');
$router->get('/api/get-slots', 'BookingController@getSlots');

// Admin routes
$router->get('/admin/dashboard', 'AdminController@showDashboard');
$router->post('/admin/update-status', 'AdminController@updateStatus');

// Dispatch
$router->dispatch();
```

### Step 7: URL Rewriting

#### **public/.htaccess**
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

---

## Option B: Laravel Framework

### Step 1: Install Laravel

```bash
# Install Laravel via Composer
composer create-project laravel/laravel von-barbershop-laravel

# Or install into existing directory
cd c:\Users\Lenovo\Downloads
composer create-project laravel/laravel von-barbershop-laravel "10.*"
```

### Step 2: Configure Database

**Edit `.env`:**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=barbershop_db
DB_USERNAME=root
DB_PASSWORD=your_password
```

### Step 3: Create Models & Migrations

```bash
# Generate models with migrations
php artisan make:model User -m
php artisan make:model Appointment -m
php artisan make:model Service -m

# Generate controllers
php artisan make:controller AuthController
php artisan make:controller BookingController
php artisan make:controller AdminController
php artisan make:controller ApiController
```

### Step 4: Define Routes

**routes/web.php:**
```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\AdminController;

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/booking', [BookingController::class, 'showBooking'])->name('booking');
    Route::post('/booking', [BookingController::class, 'book']);
    Route::get('/my-appointments', [BookingController::class, 'myAppointments']);
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // Admin routes
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::post('/update-status', [AdminController::class, 'updateStatus']);
    });
});

// API routes
Route::get('/api/get-slots', [ApiController::class, 'getSlots']);
```

### Step 5: Create Models

**app/Models/Appointment.php:**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    protected $fillable = [
        'user_id',
        'haircut_description',
        'location',
        'appointment_date',
        'appointment_time',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getAvailableSlots($date)
    {
        $booked = self::where('appointment_date', $date)
            ->where('status', '!=', 'cancelled')
            ->pluck('appointment_time')
            ->toArray();

        $allSlots = [];
        for ($hour = 9; $hour <= 16; $hour++) {
            foreach (['00', '30'] as $minute) {
                $time = sprintf('%02d:%s:00', $hour, $minute);
                $allSlots[] = $time;
            }
        }

        return collect($allSlots)->map(function ($time) use ($booked) {
            return [
                'time' => $time,
                'available' => !in_array($time, $booked)
            ];
        })->toArray();
    }
}
```

### Step 6: Create Controllers

**app/Http/Controllers/BookingController.php:**
```php
<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    public function showBooking()
    {
        return view('booking.book');
    }

    public function book(Request $request)
    {
        $validated = $request->validate([
            'haircut_description' => 'required|string',
            'location' => 'required|string',
            'appointment_date' => 'required|date',
            'appointment_time' => 'required',
        ]);

        $appointment = Appointment::create([
            ...$validated,
            'user_id' => Auth::id(),
            'status' => 'pending',
        ]);

        // Send email
        // Mail::to(Auth::user())->send(new BookingConfirmation($appointment));

        return redirect('/my-appointments')
            ->with('success', 'Booking confirmed! Check your email.');
    }

    public function getSlots(Request $request)
    {
        $appointment = new Appointment();
        $slots = $appointment->getAvailableSlots($request->date);

        return response()->json(['slots' => $slots]);
    }
}
```

### Step 7: Create Blade Views

**resources/views/booking/book.blade.php:**
```blade
@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Book an Appointment</h1>
    
    <form action="/booking" method="POST">
        @csrf
        
        <div class="form-group">
            <label>Haircut/Style</label>
            <input type="text" name="haircut_description" class="form-control" required>
        </div>

        <div class="form-group">
            <label>Location</label>
            <input type="text" name="location" class="form-control" required>
        </div>

        <div class="form-group">
            <label>Date</label>
            <input type="date" name="appointment_date" class="form-control" required>
        </div>

        <div id="slotsContainer" class="form-group">
            <!-- Slots loaded via AJAX -->
        </div>

        <button type="submit" class="btn btn-primary">Confirm Booking</button>
    </form>
</div>
@endsection
```

---

## Recommended Migration Path

### Phase 1: Prepare (Week 1)
1. ✅ Backup current system
2. ✅ Document all features
3. ✅ Create database schema documentation
4. ✅ List all API endpoints

### Phase 2: Custom MVC (Week 2-3)
1. ✅ Create MVC folder structure
2. ✅ Build Core classes (Database, Controller, Model)
3. ✅ Migrate 1-2 simple pages first (login, register)
4. ✅ Test thoroughly
5. ✅ Migrate remaining pages
6. ✅ Deploy to staging

### Phase 3: Laravel (Month 2-3)
1. ✅ Install Laravel fresh
2. ✅ Set up database migrations
3. ✅ Create Eloquent models
4. ✅ Build controllers one by one
5. ✅ Create Blade views
6. ✅ Integrate email system
7. ✅ Test everything
8. ✅ Deploy to production

---

## Timeline & Effort Estimation

| Phase | Task | Time | Difficulty |
|-------|------|------|------------|
| **Preparation** | Backup & documentation | 2-3 days | Easy |
| **Custom MVC** | Core architecture | 3-5 days | Medium |
| **Custom MVC** | Migrate all pages | 7-10 days | Medium |
| **Custom MVC** | Testing & debugging | 3-5 days | Medium |
| **Laravel** | Setup & learning | 5-7 days | Hard |
| **Laravel** | Complete rebuild | 15-20 days | Hard |
| **Laravel** | Testing & deployment | 5-7 days | Medium |

**Total Time:**
- Custom MVC: **2-3 weeks**
- Laravel: **4-6 weeks**

---

## Next Steps

### Immediate Actions:
1. ✅ Get your first client with current system
2. ✅ Keep current system running smoothly
3. ✅ Start learning MVC principles
4. ✅ Watch Laravel tutorials

### Short-term (1-2 months):
1. ✅ Build Custom MVC version as practice
2. ✅ Compare both versions
3. ✅ Learn from the refactoring process

### Long-term (3-6 months):
1. ✅ Build Laravel version
2. ✅ Market as "Premium Version"
3. ✅ Charge higher prices
4. ✅ Expand to other business types

---

## Resources & Learning Materials

### MVC Architecture:
- https://www.php-theright-way.com/
- https://designpatternsphp.readthedocs.io/
- YouTube: "PHP MVC from Scratch"

### Laravel:
- https://laravel.com/docs/10.x
- https://laracasts.com/ (Best Laravel tutorials)
- YouTube: "Laravel 10 Full Course"

### Recommended Books:
- "PHP & MySQL: Novice to Ninja"
- "Laravel Up & Running"

---

## Questions?

Feel free to ask for clarification on any step!

**Good luck with your migration journey!** 🚀

---

**Created by:** Dhon Marck V. Hermosura  
**Certified IT Specialist & AI Prompt Engineer**  
**Solo Developer - VON BARBER STUDIO**
