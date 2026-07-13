# 💰 Payment System Implementation Guide

## 📋 Overview

This guide implements a **₱50 downpayment system** using GCash to reduce no-shows and cancellations.

**GCash Number:** 0969-055-8227

---

## ✅ What's Already Implemented

### 1. Database Migration ✅
- **File:** `database/add_payment_system.sql`
- **Status:** Ready to run
- **What it does:**
  - Adds payment columns to `appointments` table
  - Creates `payment_logs` table for tracking
  - Columns added: `payment_status`, `payment_proof`, `downpayment_amount`, `balance_amount`, `payment_verified_at`

### 2. Payment Upload Page ✅
- **File:** `payment_upload.php`
- **Status:** Complete
- **Features:**
  - Displays GCash QR code and number
  - Shows appointment details
  - Payment instructions
  - File upload form (JPG/PNG, max 5MB)
  - Validates and stores payment proof

### 3. Booking Redirect ✅
- **File:** `process_booking.php`
- **Status:** Updated
- **What changed:**
  - After booking, redirects to `payment_upload.php` instead of `my_appointments.php`
  - Customer must upload payment proof

---

## 🚧 What Needs to Be Done

### Step 1: Run Database Migration
```bash
# On your production server (Render)
mysql -h <your-host> -u <your-user> -p barbershop < database/add_payment_system.sql
```

**Or via phpMyAdmin/Aiven Console:**
1. Open your MySQL client
2. Run the SQL in `database/add_payment_system.sql`

---

### Step 2: Create Uploads Directory
```bash
# On your server
mkdir -p uploads/payments
chmod 755 uploads/payments
```

---

### Step 3: Create Admin Payment Verification Page
**File to create:** `admin_payments.php`

This page will:
- Show all pending payments
- Display payment proof images
- Allow admin to approve/reject payments
- Send email notifications to customers

**Features needed:**
- List of appointments with `payment_status = 'pending'`
- Image preview of payment proof
- Approve button → updates status to 'verified'
- Reject button → updates status to 'rejected'
- Filter by payment status

---

### Step 4: Create Payment Verification API
**Files to create:**
- `api/verify_payment.php` - Approves payment
- `api/reject_payment.php` - Rejects payment

**Logic:**
```php
// verify_payment.php
UPDATE appointments SET payment_status = 'verified', payment_verified_at = NOW() WHERE id = ?
INSERT INTO payment_logs SET status = 'verified', verified_by = ?
```

---

### Step 5: Update Admin Dashboard
**File to modify:** `admin_dashboard.php`

Add:
- Link to "Payment Verification" page in navbar
- Badge showing count of pending payments
- Payment status column in appointments table

---

### Step 6: Update Email Notifications
**Files to modify:**
- `process_booking.php` - Add payment instructions to confirmation email
- `config/mailer.php` - Update email templates

**Email should include:**
- "Please pay ₱50 downpayment to confirm your booking"
- GCash number: 0969-055-8227
- Link to upload payment proof

---

### Step 7: Update My Appointments Page
**File to modify:** `my_appointments.php`

Add:
- Payment status badge (Pending/Verified/Rejected)
- "Upload Payment" button (if status is pending)
- "View Payment Proof" link (if uploaded)

---

## 🎯 Complete Flow

### Customer Flow:
1. **Book appointment** → Fill form → Click "Confirm Booking"
2. **Redirect to payment page** → See GCash QR code
3. **Pay ₱50 via GCash** → Send to 0969-055-8227
4. **Upload screenshot** → Submit payment proof
5. **Wait for verification** → Admin reviews payment
6. **Receive confirmation** → Email: "Payment verified! Your appointment is confirmed"
7. **Attend appointment** → Pay remaining balance at shop

### Admin Flow:
1. **Receive notification** → "New payment pending verification"
2. **Go to admin_payments.php** → See list of pending payments
3. **View payment proof** → Check screenshot
4. **Approve or Reject** → Click button
5. **Customer notified** → Email sent automatically

---

## 📊 Database Schema

### appointments table (new columns):
```sql
payment_status ENUM('pending','verified','rejected') DEFAULT 'pending'
payment_proof VARCHAR(255) DEFAULT NULL
downpayment_amount DECIMAL(10,2) DEFAULT 50.00
balance_amount DECIMAL(10,2) DEFAULT 0.00
payment_verified_at TIMESTAMP NULL
```

### payment_logs table:
```sql
id INT AUTO_INCREMENT PRIMARY KEY
appointment_id INT NOT NULL
user_id INT NOT NULL
amount DECIMAL(10,2) NOT NULL
payment_method VARCHAR(50) DEFAULT 'GCash'
status ENUM('pending','verified','rejected') DEFAULT 'pending'
proof_filename VARCHAR(255) DEFAULT NULL
admin_notes TEXT
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
verified_at TIMESTAMP NULL
verified_by INT DEFAULT NULL
```

---

## 🔒 Security Considerations

1. **File Upload Validation:**
   - Only accept JPG/PNG images
   - Max file size: 5MB
   - Store files outside web root (or use .htaccess to prevent direct access)

2. **Payment Proof Storage:**
   - Files stored in `uploads/payments/`
   - Filename format: `payment_{appointment_id}_{timestamp}.jpg`
   - Prevent directory traversal attacks

3. **Admin Verification:**
   - Only admin/barber roles can verify payments
   - Log all verification actions with admin ID

---

## 📧 Email Templates

### Booking Confirmation Email (Updated):
```
Subject: Your Appointment Confirmation - Action Required

Dear [Customer Name],

Your appointment has been booked!

📅 Date: [Date]
⏰ Time: [Time]
📍 Location: [Location]

⚠️ ACTION REQUIRED:
To confirm your booking, please pay ₱50.00 downpayment via GCash:

GCash Number: 0969-055-8227
Amount: ₱50.00

After payment, upload your payment screenshot here:
[Link to payment_upload.php?appointment_id=X]

Your appointment will be confirmed once payment is verified.

Thank you!
VON BARBER STUDIO
```

### Payment Verified Email:
```
Subject: ✅ Payment Verified - Your Appointment is Confirmed

Dear [Customer Name],

Your ₱50.00 downpayment has been verified!

Your appointment is now confirmed:
📅 Date: [Date]
⏰ Time: [Time]
📍 Location: [Location]

Please pay the remaining balance at the shop.

See you soon!
VON BARBER STUDIO
```

---

## 🧪 Testing Checklist

- [ ] Run database migration
- [ ] Create uploads directory
- [ ] Book appointment → Redirect to payment page
- [ ] Upload payment proof → Success message
- [ ] Check payment appears in admin_payments.php
- [ ] Approve payment → Status updates to 'verified'
- [ ] Reject payment → Status updates to 'rejected'
- [ ] Customer receives email notifications
- [ ] My appointments page shows payment status

---

## 🚀 Next Steps

1. **Run database migration** on production
2. **Create admin_payments.php** (I can help with this)
3. **Create API endpoints** (verify_payment.php, reject_payment.php)
4. **Update email templates** with payment instructions
5. **Test complete flow** end-to-end
6. **Deploy to production**

---

## 💡 Future Enhancements

- Add payment reminders (email/SMS)
- Automatic refund system for cancellations
- Payment history dashboard
- Multiple payment methods (Maya, PayMaya, etc.)
- QR code generation (currently using placeholder icon)

---

**Need help implementing the remaining parts? Let me know!** 🚀
