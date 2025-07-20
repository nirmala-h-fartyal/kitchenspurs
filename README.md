# Kitchenspurs Laravel Project

## Project Overview
This is a Laravel-based Article Management System with role-based access control (Admin/Author), category management, and AI-powered slug/summary generation.

---

## Requirements
- PHP 8.1+
- Composer
- MySQL or compatible database
- Node.js & npm (for frontend assets, if needed)
- [Optional] Redis or other queue driver for jobs

---

## Setup Instructions

### 1. Clone the Repository
```bash
# Clone the project
 git clone https://github.com/nirmala-h-fartyal/kitchenspurs.git
 cd kitchenspurs
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Copy & Configure Environment File
```bash
cp .env.example .env
```
- Set your database credentials in `.env`:
  - `DB_DATABASE=your_db_name`
  - `DB_USERNAME=your_db_user`
  - `DB_PASSWORD=your_db_password`
- Set your `APP_URL` (e.g., `http://127.0.0.1:8000`)
- Set your Gemini API credentials if using AI features:
  - `GEMINI_API_KEY=your_gemini_api_key`
  - `GEMINI_API_URL=your_gemini_api_url` (optional)

### 4. Generate Application Key
```bash
php artisan key:generate
```

### 5. Run Migrations
```bash
php artisan migrate
```

### 6. (Optional) Seed the Database
```bash
php artisan db:seed
```

### 7. (Optional) Install Frontend Dependencies
If you have frontend assets:
```bash
npm install && npm run dev
```

### 8. Run the Development Server
```bash
php artisan serve
```
Visit [http://127.0.0.1:8000](http://127.0.0.1:8000)

---

## API Authentication
- Uses Laravel Sanctum for API token authentication.
- Obtain a token via `/api/login` endpoint.
- Pass the token as a Bearer token in the `Authorization` header for all protected API requests.

---

## Roles & Permissions
- **Admin**: Manage all articles and categories
- **Author**: Manage only their own articles

---

## Useful Artisan Commands
- `php artisan migrate:fresh --seed` — Reset and seed the database
- `php artisan queue:work` — Run the queue worker for jobs (AI slug/summary)
- `php artisan config:cache` — Cache config
- `php artisan route:list` — List all routes

---

## Troubleshooting
- Ensure your `.env` file is configured correctly
- Check database connection and credentials
- Run `composer install` and `php artisan key:generate` if you see errors
- For queue jobs, ensure your queue driver is set up and run `php artisan queue:work`

---

## License
This project is for demonstration and educational purposes.
