# Menu CMS - Restaurant Daily Menu Management

A lightweight, self-hosted PHP-based CMS for managing daily menus of **Bufet v Hlubokáně** and **Nejen Caffé u Páji** restaurants.

## Features

- 📅 **Week Calendar View** - Edit menus for each day of the week
- 🍽️ **Multiple Restaurants** - Manage menus for both restaurants from one dashboard
- 🔄 **Repeatable Dish Fields** - Add soup, main dishes, and desserts
- 💾 **Save/Publish Workflow** - Draft and publish menus
- 📋 **Copy Menu** - Copy menu from previous week to save time
- 🔐 **Secure Login** - Session-based authentication with CSRF protection
- 📱 **Responsive UI** - Works on desktop and mobile
- 🗄️ **JSON Storage** - No database required, works on basic PHP hosting

## Requirements

- PHP 7.4 or higher
- Apache with mod_rewrite (for .htaccess protection) or nginx
- Write permissions for the `sprava/data/` directory

## Installation

### 1. Upload Files

Upload the entire project to your FTP/web hosting. The structure should be:

```
your-website/
├── index.html              # Bufet v Hlubokáně website
├── caffe-upaji.html        # Nejen Caffé u Páji website
├── login.php               # Redirect to CMS login
├── css/
├── js/
├── template/
├── original/
└── sprava/                    # CMS directory
    ├── index.php           # CMS entry point
    ├── login.php           # Login page
    ├── logout.php          # Logout handler
    ├── dashboard.php       # Admin dashboard
    ├── save-menu.php       # Save menu handler
    ├── copy-menu.php       # Copy menu handler
    ├── api.php             # Public API endpoint
    ├── includes/           # PHP includes
    │   ├── config.php      # Configuration
    │   ├── auth.php        # Authentication
    │   ├── menu.php        # Menu functions
    │   └── functions.php   # Helper functions
    ├── css/
    │   └── admin.css       # Admin styles
    ├── js/
    │   └── admin.js        # Admin JavaScript
    └── data/               # Data storage (auto-created)
        ├── .htaccess       # Protect data files
        ├── users.json      # User credentials
        └── menus.json      # Menu data
```

### 2. Set Directory Permissions

The `sprava/data/` directory needs write permissions:

```bash
chmod 755 sprava/data/
```

Or via your FTP client, set the `data` folder permissions to 755.

### 3. Configure (Optional)

Edit `sprava/includes/config.php` if needed:

```php
// Set your site URL if auto-detection doesn't work
define('SITE_URL', 'https://your-domain.com/cms');

// Session lifetime (8 hours by default)
define('SESSION_LIFETIME', 3600 * 8);
```

### 4. First Login

1. Navigate to `https://your-domain.com/sprava/login.php` or click the "Přihlásit se" button on either website
2. Use the default credentials:
   - **Username:** `admin`
   - **Password:** `admin123`
3. **Important:** Change the password after first login!

## Usage

### Managing Menus

1. **Select Restaurant** - Click on "Bufet v Hlubokáně" or "Nejen Caffé u Páji" in the sidebar
2. **Navigate Weeks** - Use the arrows to go to previous/next week
3. **Edit Daily Menus:**
   - Check "Zavřeno" if the restaurant is closed that day
   - Click "+ Přidat" to add dishes to each category
   - Enter dish name and price (Kč)
4. **Save Your Work:**
   - **"Uložit koncept"** - Saves as draft (not visible on websites)
   - **"Publikovat"** - Makes the menu live on the websites

### Dish Categories

- **Polévka** (Soup) - Daily soup offerings
- **Hlavní jídlo** (Main dish) - Main course options
- **Dezert** (Dessert) - Dessert items (optional)

### Copying Menus

Click "📋 Kopírovat z minulého týdne" to copy all dishes from the previous week. This saves time when your menu is similar week to week.

### Weekend Handling

Saturday and Sunday are marked as closed by default. You can uncheck "Zavřeno" and add menus for special weekend openings.

## API Documentation

The CMS provides a public JSON API for the frontend websites:

### Get Current Week Menu

```
GET /sprava/api.php?restaurant=bufet
GET /sprava/api.php?restaurant=caffe
```

### Get Specific Week Menu

```
GET /sprava/api.php?restaurant=bufet&week=2024-01-15
```

### Response Format

```json
{
  "restaurant": {
    "id": "bufet",
    "name": "Bufet v Hlubokáně",
    "slug": "bufet-v-hlubokane",
    "color": "#c8860a"
  },
  "week": "2024-01-15",
  "weekRange": "15. ledna 2024 – 21. ledna 2024",
  "status": "published",
  "lastModified": "2024-01-14 10:30:00",
  "menu": {
    "0": {
      "closed": false,
      "soup": [
        { "name": "Hovězí vývar s nudlemi", "price": "25 Kč" }
      ],
      "main": [
        { "name": "Svíčková na smetaně", "price": "115 Kč", "number": "1" }
      ],
      "dessert": []
    },
    "1": { ... },
    "2": { ... },
    "3": { ... },
    "4": { ... }
  }
}
```

Day indices: 0 = Monday, 1 = Tuesday, ..., 4 = Friday

## Security

### Changing Admin Password

1. Login to the CMS
2. The password is stored hashed in `sprava/data/users.json`
3. To manually change it, edit the file and replace the password hash:

```php
<?php
// Run this in a temporary PHP file to generate a new hash
echo password_hash('your-new-password', PASSWORD_DEFAULT);
?>
```

### Adding New Users

Edit `sprava/data/users.json`:

```json
{
  "admin": {
    "name": "Administrátor",
    "password": "$2y$10$...(hash)...",
    "role": "admin"
  },
  "editor": {
    "name": "Redaktor",
    "password": "$2y$10$...(hash)...",
    "role": "editor"
  }
}
```

### Security Features

- Session-based authentication
- CSRF token protection
- Password hashing (bcrypt)
- `.htaccess` protection for data directory
- Input sanitization

## Troubleshooting

### "Permission denied" errors

Ensure the `sprava/data/` directory has write permissions (755 or 775).

### Menu not updating on website

1. Make sure you clicked "Publikovat" and not just "Uložit koncept"
2. Clear your browser cache
3. Check browser console for API errors

### Login not working

1. Clear browser cookies
2. Check PHP session configuration on your hosting
3. Ensure PHP 7.4+ is running

### Blank page or PHP errors

1. Check PHP error logs
2. Enable error display temporarily in `sprava/includes/config.php`:
   ```php
   ini_set('display_errors', 1);
   ```

## File Structure

| File | Description |
|------|-------------|
| `sprava/login.php` | Login form and authentication |
| `sprava/dashboard.php` | Main admin interface with week calendar |
| `sprava/api.php` | Public API for fetching menus |
| `sprava/save-menu.php` | Handles menu save/publish |
| `sprava/copy-menu.php` | Handles menu copying |
| `sprava/includes/config.php` | Configuration constants |
| `sprava/includes/auth.php` | Authentication functions |
| `sprava/includes/menu.php` | Menu data functions |
| `sprava/includes/functions.php` | Helper utilities |
| `sprava/data/users.json` | User credentials (auto-created) |
| `sprava/data/menus.json` | Menu data (auto-created) |

## License

This CMS is built specifically for Bufet v Hlubokáně and Nejen Caffé u Páji restaurants.

## Support

For questions or issues, contact the system administrator.

