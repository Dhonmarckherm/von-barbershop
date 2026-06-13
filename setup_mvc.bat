@echo off
echo ========================================
echo MVC Structure Setup for VON BARBER STUDIO
echo ========================================
echo.

echo Creating folder structure...
mkdir "app\Core" 2>nul
mkdir "app\Controllers" 2>nul
mkdir "app\Models" 2>nul
mkdir "app\Views\layouts" 2>nul
mkdir "app\Views\auth" 2>nul
mkdir "app\Views\booking" 2>nul
mkdir "app\Views\admin" 2>nul
mkdir "public\assets\css" 2>nul
mkdir "public\assets\js" 2>nul
mkdir "public\assets\images" 2>nul
mkdir "config" 2>nul
echo ✓ Folders created!
echo.

echo Creating configuration files...

:: config/database.php
(
echo ^<?php
echo return [
echo     'host' =^> 'localhost',
echo     'database' =^> 'barbershop_db',
echo     'username' =^> 'root',
echo     'password' =^> '',
echo     'charset' =^> 'utf8mb4'
echo ];
) > config\database.php
echo ✓ config/database.php created

:: public/.htaccess
(
echo RewriteEngine On
echo RewriteCond %%{REQUEST_FILENAME} !-f
echo RewriteCond %%{REQUEST_FILENAME} !-d
echo RewriteRule ^ index.php [QSA,L]
) > public\.htaccess
echo ✓ public/.htaccess created

echo.
echo ========================================
echo Core MVC Files - Create These Manually:
echo ========================================
echo.
echo 1. app/Core/Database.php
echo 2. app/Core/Controller.php
echo 3. app/Core/Model.php
echo 4. app/Core/Router.php
echo 5. public/index.php
echo 6. app/Models/User.php
echo 7. app/Controllers/AuthController.php
echo 8. app/Views/layouts/header.php
echo 9. app/Views/layouts/footer.php
echo 10. app/Views/auth/login.php
echo.
echo See SETUP_GUIDE.md for complete code!
echo ========================================
pause
