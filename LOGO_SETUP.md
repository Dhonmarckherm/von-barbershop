# 📋 HOW TO ADD YOUR LOGO (rubiks.jpg)

## Step-by-Step Instructions:

### Option 1: Manual Copy (Easiest)

1. **Find your rubiks.jpg file** on your computer
2. **Copy the file** (Ctrl+C)
3. **Navigate to:**
   ```
   C:\Users\Lenovo\Downloads\Barbershop_booking-system\assets\images\
   ```
4. **Paste the file** (Ctrl+V)
5. **Refresh your browser** - The logo should now appear!

### Option 2: Using File Explorer

1. Open File Explorer
2. Go to: `C:\Users\Lenovo\Downloads\Barbershop_booking-system\assets\images\`
3. Drag and drop your `rubiks.jpg` file into this folder
4. Refresh your browser

### Option 3: Rename if needed

If your logo file has a different name (e.g., `Rubiks.jpg`, `RUBIKS.JPG`):
- Rename it to exactly: `rubiks.jpg` (lowercase)
- Make sure the file extension is `.jpg` not `.jpeg` or `.png`

---

## ✅ What's Been Updated:

✅ Logo image will appear in the navbar (next to the barbershop name)  
✅ Logo size: 45px height (automatically scales width)  
✅ Rounded corners with shadow effect  
✅ Hover animation (slight zoom effect)  
✅ Removed the scissors emoji (✂)  

---

## 🎨 Current Setup:

- **Logo Path:** `assets/images/rubiks.jpg`
- **Barbershop Name:** Still shown next to logo (editable in database)
- **Logo Size:** 45px height × auto width
- **Styling:** Rounded corners, shadow, hover effect

---

## 🔧 To Change Barbershop Name:

The barbershop name is stored in the database. To update it:

1. Go to Admin Dashboard → Settings
2. Update "Barbershop Name" 
3. OR run this SQL query:
   ```sql
   UPDATE settings 
   SET setting_value = 'Your New Name' 
   WHERE setting_key = 'barbershop_name';
   ```

---

## 📁 File Location:

```
Barbershop_booking-system/
├── assets/
│   ├── css/
│   │   └── style.css
│   └── images/
│       └── rubiks.jpg  ← PUT YOUR LOGO HERE
└── includes/
    └── header.php
```

---

## 🚀 After Adding the Logo:

1. Refresh your browser (Ctrl+F5 for hard refresh)
2. Clear browser cache if needed
3. The logo should appear in the top-left navbar

---

## ⚠️ Troubleshooting:

**Logo not showing?**
- Check file name is exactly `rubiks.jpg` (case-sensitive)
- Check file is in `assets/images/` folder
- Hard refresh browser: Ctrl+Shift+R or Ctrl+F5
- Check browser console for 404 errors (F12)

**Logo too big/small?**
- Edit line 27 in `includes/header.php`
- Change `height: 40px` to your preferred size

**Wrong file format?**
- If your logo is `.png`, update header.php to: `rubiks.png`
- Supported: .jpg, .jpeg, .png, .svg, .webp

---

Need help? Contact support! ✂️
