# UpAdmin — Upwork-Style Admin Panel

Full-stack admin panel for a freelance marketplace platform built with **Laravel (PHP)** backend and **vanilla JS + Chart.js** frontend.

---

## Project Structure

```
upwork-admin/
├── backend/                    # Laravel PHP API
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/Admin/
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── UserController.php
│   │   │   │   ├── JobController.php
│   │   │   │   ├── PaymentController.php
│   │   │   │   └── ReportController.php
│   │   │   └── Middleware/
│   │   │       └── AdminMiddleware.php
│   │   └── Models/
│   │       ├── User.php
│   │       ├── Job.php
│   │       └── Payment.php
│   ├── database/
│   │   └── migrations/
│   │       └── 2024_01_01_create_tables.php
│   └── routes/
│       └── api.php
└── frontend/
    ├── admin-panel.html         # Complete frontend (drop-in, no build needed)
    └── src/
        └── services/
            └── api.js           # API service module (for React/Vue integration)
```

---

## Backend Setup (Laravel)

### 1. Install in your existing Laravel project

Copy the files into your Laravel project:

```bash
# Copy controllers
cp backend/app/Http/Controllers/Admin/* app/Http/Controllers/Admin/

# Copy middleware
cp backend/app/Http/Middleware/AdminMiddleware.php app/Http/Middleware/

# Copy models (or merge into existing)
cp backend/app/Models/*.php app/Models/

# Copy migration
cp backend/database/migrations/2024_01_01_create_tables.php database/migrations/

# Add routes to your routes/api.php
```

### 2. Register middleware in `app/Http/Kernel.php`

```php
protected $routeMiddleware = [
    // ... existing middleware
    'admin' => \App\Http\Middleware\AdminMiddleware::class,
];
```

### 3. Install Laravel Sanctum (if not already)

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

### 4. Run migrations

```bash
php artisan migrate
```

### 5. Create your first admin user

```bash
php artisan tinker
```
```php
\App\Models\User::create([
    'name'     => 'Super Admin',
    'email'    => 'admin@yoursite.com',
    'password' => bcrypt('yourpassword'),
    'role'     => 'admin',
    'status'   => 'active',
]);
```

### 6. Add CORS headers (if frontend is on a different domain)

In `config/cors.php`:
```php
'paths' => ['api/*'],
'allowed_origins' => ['https://youradmin.com'],
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
```

---

## Frontend Setup

### Option A — Drop-in HTML (simplest)

1. Open `frontend/admin-panel.html` in a text editor
2. Find this line near the bottom of the `<script>` tag:
   ```js
   const API = (window.API_BASE_URL || 'http://localhost:8000/api');
   ```
3. Change to your Laravel API URL:
   ```js
   const API = 'https://yoursite.com/api';
   ```
4. Upload `admin-panel.html` to your server and visit it in a browser.

### Option B — Inject API URL via server

Add this before your `<script>` in the HTML:
```html
<script>window.API_BASE_URL = '<?= env("APP_URL") ?>/api';</script>
```

### Option C — React/Vue integration

Use the `src/services/api.js` module:
```js
import { login, getUsers, getJobs, getPayments } from './services/api';

// Set VITE_API_URL in .env
VITE_API_URL=https://yoursite.com/api
```

---

## API Reference

### Authentication
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/admin/login` | Admin login → returns Bearer token |
| POST | `/api/admin/logout` | Revoke token |
| GET | `/api/admin/me` | Get current admin profile |

### Dashboard
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/dashboard/stats` | Key metrics (users, jobs, revenue, disputes) |
| GET | `/api/admin/dashboard/revenue-chart?period=30` | Revenue over time |
| GET | `/api/admin/dashboard/recent-activity` | Latest users/jobs/payments |

### Users
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/users` | Paginated user list (search, role, status filters) |
| GET | `/api/admin/users/{id}` | Single user details |
| PUT | `/api/admin/users/{id}` | Update user |
| DELETE | `/api/admin/users/{id}` | Delete user |
| PATCH | `/api/admin/users/{id}/suspend` | Suspend account |
| PATCH | `/api/admin/users/{id}/activate` | Activate account |
| PATCH | `/api/admin/users/{id}/verify` | Verify user |

### Jobs
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/jobs` | Paginated job list |
| PATCH | `/api/admin/jobs/{id}/approve` | Approve pending job |
| PATCH | `/api/admin/jobs/{id}/reject` | Reject job |
| PATCH | `/api/admin/jobs/{id}/close` | Close job |
| PATCH | `/api/admin/jobs/{id}/flag` | Toggle flag |

### Payments
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/payments` | Paginated transaction list |
| PATCH | `/api/admin/payments/{id}/refund` | Process refund |
| PATCH | `/api/admin/payments/{id}/dispute` | Resolve dispute |
| GET | `/api/admin/payments/summary` | Payment totals/summary |

### Reports
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/reports/overview?period=30` | Full platform overview |
| GET | `/api/admin/reports/revenue?period=30` | Revenue breakdown |
| GET | `/api/admin/reports/users?period=30` | User growth/top users |
| GET | `/api/admin/reports/jobs?period=30` | Job posting trends |
| GET | `/api/admin/reports/export` | Export data (CSV/JSON) |

---

## Admin Panel Features

- **Dashboard** — Live stats, revenue chart, recent activity
- **User Management** — Search, filter by role/status, suspend/activate/verify
- **Jobs & Listings** — Approve/reject/flag jobs, search and filter
- **Payments** — Full transaction log, process refunds, resolve disputes
- **Reports** — Charts for revenue, user growth, job trends with period selection
- **Export** — CSV export for all data types

---

## Security Notes

- All admin routes are protected by `auth:sanctum` + `admin` middleware
- Tokens are revoked on logout
- Admin users cannot be deleted via the panel
- Only completed payments can be refunded
- Input validation on all write endpoints
