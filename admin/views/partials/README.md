# 📱 Admin Panel Responsive Layout System

## ✨ Features

### 1. **Hamburg Menu**

- ✅ Mobile-friendly sidebar toggle button
- ✅ Smooth slide-in/out animation
- ✅ Overlay background when sidebar is open
- ✅ Auto-close on navigation link click (mobile only)
- ✅ Keyboard support (ESC key to close)

### 2. **Fully Responsive Design**

- ✅ **Desktop (≥992px):** Fixed sidebar, full layout
- ✅ **Tablet (768px-991px):** Collapsible sidebar with hamburg menu
- ✅ **Mobile (≤767px):** Full-screen sidebar overlay, optimized spacing

### 3. **Accessibility**

- ✅ ARIA labels for screen readers
- ✅ Keyboard navigation support
- ✅ Focus management
- ✅ High contrast colors

---

## 📝 Usage Guide

### **How to Use in Your Pages**

```php
<?php
// Set page-specific variables
$pageTitle = 'Client Management - SSO Admin';
$currentPage = 'clients'; // Highlights the active menu item

// Optional: Add page-specific styles
$additionalStyles = "
    .table-responsive {
        border-radius: 10px;
    }
    .custom-class {
        color: #0d6efd;
    }
";

// Include header
include __DIR__ . '/partials/header.php';
?>

<!-- Your page content goes here -->
<div class="admin-page-header">
    <div class="admin-page-title">
        <h1 class="h2">
            <i class="fas fa-users me-2"></i>Client Management
        </h1>
        <div class="btn-toolbar">
            <button class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Add New
            </button>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <!-- Your content -->
    </div>
</div>

<?php
// Optional: Add page-specific scripts
$pageScripts = "
<script>
    console.log('Page loaded');
</script>
";

// Include footer
include __DIR__ . '/partials/footer.php';
?>
```

---

## 🎨 Breakpoints

| Device      | Width       | Sidebar Behavior               |
| ----------- | ----------- | ------------------------------ |
| **Desktop** | ≥992px      | Fixed, always visible          |
| **Tablet**  | 768px-991px | Collapsible with hamburg menu  |
| **Phone**   | ≤767px      | Full overlay with hamburg menu |

---

## 🎯 Menu Items

Set `$currentPage` variable to highlight the active menu:

```php
$currentPage = 'dashboard';      // Dashboard
$currentPage = 'clients';        // Client Applications
$currentPage = 'statistics';     // Usage Statistics
$currentPage = 'admin-users';    // Admin Users
$currentPage = 'backup-restore'; // Backup & Restore
$currentPage = 'settings';       // System Configuration
```

---

## 🔧 Customization

### **Add Custom Styles**

```php
$additionalStyles = "
    .my-custom-class {
        background: #f0f0f0;
    }
";
```

### **Add Custom Scripts**

```php
$pageScripts = "
<script>
    function myCustomFunction() {
        alert('Custom function');
    }
</script>
";
```

---

## 🌐 Mobile Features

### **Automatic Behaviors:**

1. **Sidebar auto-closes** when clicking menu links on mobile
2. **Sidebar auto-closes** when clicking overlay
3. **Sidebar auto-closes** when pressing ESC key
4. **Sidebar auto-closes** when resizing to desktop view
5. **Body scroll disabled** when sidebar is open on mobile

### **Touch-Friendly:**

- Large tap targets (min 44x44px)
- Swipe gestures supported
- Optimized spacing for touch

---

## 📱 Testing Responsive Design

### **Browser DevTools:**

```
1. Open Chrome DevTools (F12)
2. Click "Toggle Device Toolbar" (Ctrl+Shift+M)
3. Test on different devices:
   - iPhone SE (375px)
   - iPhone 12 Pro (390px)
   - iPad (768px)
   - iPad Pro (1024px)
   - Desktop (1920px)
```

### **Real Devices:**

Test on actual phones and tablets for best results.

---

## 🎨 CSS Variables

Available CSS variables for easy customization:

```css
:root {
  --sidebar-width: 250px; /* Sidebar width on desktop */
  --navbar-height: 56px; /* Top navbar height */
}
```

---

## ⚡ Performance

- **Lazy loading:** Sidebar slides in only when needed
- **CSS transitions:** Hardware-accelerated animations
- **No jQuery:** Pure vanilla JavaScript
- **Minimal overhead:** ~2KB additional code

---

## 🔒 Security Notes

- All user inputs are escaped with `htmlspecialchars()`
- Session data validated before display
- XSS protection built-in

---

## 🐛 Troubleshooting

### **Sidebar not showing:**

- Check if `#adminSidebar` element exists
- Verify JavaScript is loaded
- Check browser console for errors

### **Hamburg menu not working:**

- Ensure Bootstrap 5.3.0+ is loaded
- Check if `toggleSidebar()` function is defined
- Verify no JavaScript conflicts

### **Styles not applying:**

- Clear browser cache
- Check CSS specificity
- Verify Bootstrap CSS is loaded first

---

## 📚 Example Pages

See these files for complete examples:

- `/admin/views/clients.php` (will be updated)
- `/admin/views/admin-users.php` (will be updated)
- `/admin/views/backup-restore.php` (will be updated)

---

## 🚀 Next Steps

1. **Update existing pages** to use new template
2. **Test on multiple devices**
3. **Customize colors and spacing** as needed
4. **Add animations** for enhanced UX

---

**Created:** 2025-01-20  
**Version:** 1.0.0  
**Compatibility:** Bootstrap 5.3.0+, PHP 7.4+
