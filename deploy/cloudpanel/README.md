# CloudPanel Deployment

This project runs correctly on CloudPanel when it is deployed on a VPS with:

- PHP 8.2+
- MySQL or MariaDB
- Composer
- Node.js and npm
- Supervisor for background processes
- Nginx reverse proxy support for Laravel Reverb

## 1. Create the site

Create a PHP site in CloudPanel and point its document root to:

```text
/home/<site-user>/htdocs/<domain>/public
```

Upload the Laravel project one level above `public`, so the app structure looks like this:

```text
/home/<site-user>/htdocs/<domain>/
  app/
  bootstrap/
  config/
  public/
  resources/
  routes/
  storage/
  vendor/
```

## 2. Install dependencies

Run these commands inside the project root:

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

## 3. Configure `.env`

Use production values similar to this:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

SESSION_DRIVER=database
QUEUE_CONNECTION=database

BROADCAST_CONNECTION=reverb
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST=your-domain.com
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
VITE_APP_URL="${APP_URL}"
```

Keep the browser-facing `VITE_*` Reverb values separate from the server-side `REVERB_*` values.

## 4. Run Laravel setup

```bash
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 5. Start background services

This app needs both:

- a queue worker
- a Reverb server

Supervisor templates are included here:

- `deploy/supervisor/queue-worker.conf`
- `deploy/supervisor/reverb.conf`

Update the paths if your CloudPanel site directory is different, then enable them with Supervisor.

## 6. Configure WebSocket proxy

If your site uses a custom Nginx vhost, proxy `/app` to Reverb on `127.0.0.1:8080`.

You can use `deploy/nginx/zynquiz.shadhinlab.xyz.conf` as the reference. The important part is the `/app` proxy block with WebSocket upgrade headers.

## 7. Common deployment checks

- Make sure `public/build` exists after `npm run build`
- Make sure there is no `public/hot` file in production
- Make sure the site root points to `public/`
- Make sure `storage/` and `bootstrap/cache/` are writable
- Make sure Supervisor is keeping both worker processes alive
- Make sure the domain points to this Laravel app, not another app on the server

## 8. If real-time updates do not work

Check these first:

- `APP_URL` matches the live domain exactly
- `VITE_APP_URL` matches `APP_URL`
- `VITE_REVERB_HOST` is the live domain
- `VITE_REVERB_PORT=443`
- `VITE_REVERB_SCHEME=https`
- the queue worker is running
- the Reverb process is running
- Nginx is proxying `/app` to `127.0.0.1:8080`
