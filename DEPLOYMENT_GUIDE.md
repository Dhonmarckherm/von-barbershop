# 🚀 V.O.N Barbershop - Deployment Guide

## Quick Deploy to Render.com (FREE)

### Prerequisites
- ✅ GitHub account
- ✅ Gmail account (for email notifications)
- ✅ Your project files ready

---

## Step 1: Push to GitHub

### 1.1 Create GitHub Repository

1. Go to https://github.com/new
2. Repository name: `von-barbershop`
3. Make it **Public** or **Private** (your choice)
4. **DON'T** initialize with README
5. Click **Create repository**

### 1.2 Initialize Git (in your project folder)

Open PowerShell in your project folder and run:

```powershell
# Initialize git
git init

# Add all files
git add .

# Create first commit
git commit -m "Initial commit - V.O.N Barbershop Booking System"

# Rename branch to main
git branch -M main

# Add your GitHub repository (REPLACE YOUR_USERNAME)
git remote add origin https://github.com/YOUR_USERNAME/von-barbershop.git

# Push to GitHub
git push -u origin main
```

**Note:** If git asks for credentials, use your GitHub username and personal access token.

---

## Step 2: Deploy to Render.com

### 2.1 Create Render Account

1. Go to https://render.com
2. Click **Get Started for Free**
3. Sign up with **GitHub** (easiest option)
4. Authorize Render to access your GitHub

### 2.2 Create Web Service

1. Click **New +** → **Web Service**
2. Click **Connect** next to `von-barbershop` repository
3. Configure the service:

```
Name: von-barbershop
Region: Oregon (closest to you)
Branch: main
Root Directory: (leave blank)
Environment: PHP
Build Command: composer install --no-dev --optimize-autoloader
Start Command: heroku-php-apache2
```

4. Choose **Free** plan
5. **DON'T click Create yet!**

### 2.3 Add Environment Variables

Scroll down to **Advanced** → **Environment Variables** and add:

```
DB_HOST = (we'll add this after creating database)
DB_PORT = 3306
DB_NAME = barbershop_booking
DB_USER = (we'll add this after creating database)
DB_PASS = (we'll add this after creating database)
MAIL_HOST = smtp.gmail.com
MAIL_PORT = 587
MAIL_USERNAME = your-email@gmail.com
MAIL_PASSWORD = your-gmail-app-password
MAIL_FROM_NAME = V.O.N Barbershop
APP_ENV = production
APP_DEBUG = false
```

6. Click **Create Web Service**
7. Wait for deployment (it will fail - that's OK, we need database first!)

---

## Step 3: Create MySQL Database

### Option A: Use Render PostgreSQL (Easier)

1. In Render dashboard, click **New +** → **PostgreSQL**
2. Configure:
```
Name: von-barbershop-db
Region: Oregon
Version: 15
Free plan
```
3. Click **Create Database**
4. Wait for database to be ready (5-10 minutes)
5. Go to database page and copy **Internal Connection URL**

Example: `postgresql://user:password@host:5432/dbname`

Parse it to get:
- Host
- User
- Password
- Database name

### Option B: Use External MySQL (Recommended for PHP)

Sign up for **free MySQL hosting**:

**Option 1: ClearDB (Free)**
- Go to https://www.cleardb.com
- Create free account
- Create database
- Get credentials

**Option 2: db4free.net (Free)**
- Go to https://www.db4free.net
- Sign up
- Create database
- Get credentials

---

## Step 4: Import Database Schema

### 4.1 Connect to Your Database

Use **phpMyAdmin** or **MySQL Workbench** or online tool provided by your host.

### 4.2 Import Schema

1. Open your database management tool
2. Create database: `barbershop_booking`
3. Import file: `database/schema.sql`
4. Verify tables are created

---

## Step 5: Update Database Credentials

### 5.1 Go Back to Render Web Service

1. Go to your Render dashboard
2. Click on `von-barbershop` web service
3. Go to **Environment** tab
4. Update these variables with your actual database credentials:

```
DB_HOST = your-database-host.com
DB_NAME = barbershop_booking
DB_USER = your-db-username
DB_PASS = your-db-password
```

5. Click **Save Changes**
6. Render will automatically redeploy!

---

## Step 6: Set Up Gmail for Emails

### 6.1 Create Gmail App Password

1. Go to https://myaccount.google.com
2. Click **Security**
3. Enable **2-Step Verification** (if not enabled)
4. Go to **App passwords**
5. Select app: **Mail**
6. Select device: **Other (Custom name)** → Enter: `VON Barbershop`
7. Click **Generate**
8. Copy the 16-character password (example: `abcd efgh ijkl mnop`)
9. Remove spaces: `abcdefghijklmnop`

### 6.2 Update Render Environment Variables

```
MAIL_USERNAME = your-email@gmail.com
MAIL_PASSWORD = abcdefghijklmnop (the app password you just created)
```

---

## Step 7: Test Your Live Site!

### 7.1 Get Your URL

Your site will be live at:
```
https://von-barbershop.onrender.com
```

### 7.2 Test Everything

- ✅ Visit homepage
- ✅ Register a test account
- ✅ Login
- ✅ Book an appointment
- ✅ Check email notifications
- ✅ Test admin dashboard
- ✅ Test all features

---

## 🎉 You're Live!

Share your link:
```
https://von-barbershop.onrender.com
```

Add it to:
- ✅ Facebook
- ✅ Instagram bio
- ✅ WhatsApp
- ✅ Twitter/X
- ✅ Business cards

---

## 🔧 Troubleshooting

### Site Shows Error
1. Check Render logs (Dashboard → Logs tab)
2. Verify database credentials
3. Check if database is accessible

### Email Not Sending
1. Verify Gmail app password is correct
2. Check MAIL_USERNAME is your full Gmail address
3. Ensure 2FA is enabled on Gmail

### Database Connection Failed
1. Verify database host is correct
2. Check if database allows external connections
3. Test connection locally first

### Slow Loading (Free Tier)
- Free tier sleeps after 15 minutes of inactivity
- First visit after sleep takes 30-50 seconds
- Subsequent visits are fast
- Upgrade to paid plan for always-on

---

## 📱 Custom Domain (Optional)

If you want `vonbarbershop.com`:

1. Buy domain from Namecheap/GoDaddy (~$10/year)
2. In Render dashboard → **Settings** → **Custom Domain**
3. Add your domain
4. Update DNS records as instructed
5. Wait 24-48 hours for DNS propagation

---

## 💰 Costs

**Free Setup:**
- Render hosting: FREE
- Database: FREE
- SSL: FREE
- Total: **$0/month**

**Optional Upgrades:**
- Custom domain: ~$10/year
- Render paid plan: $7/month (always on, faster)
- Better database: $5-10/month

---

## 🆘 Need Help?

Common issues and solutions are in the Troubleshooting section above.

If you're stuck, check:
1. Render logs for error messages
2. Database connection settings
3. Environment variables are correct

---

## 🎯 Next Steps After Deployment

1. ✅ Test all features thoroughly
2. ✅ Share your link on social media
3. ✅ Monitor Render dashboard for issues
4. ✅ Set up regular database backups
5. ✅ Consider custom domain for professionalism
6. ✅ Upgrade to paid plan when you get more users

---

**Good luck with your deployment! 🚀**
