<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Deployment Notes

## Hostinger Compatibility

This app can be uploaded to Hostinger, but the hosting type matters:

- Hostinger Web Hosting / Cloud Hosting: basic Laravel pages can run, but this project's real-time quiz features use Laravel Reverb / WebSockets and queued background work. Hostinger's support docs say Web and Cloud hosting do not allow incoming WebSocket connections, so the live quiz features will not work correctly there.
- Hostinger VPS: full deployment is supported. This is the recommended Hostinger option for this project.

If you deploy on Hostinger shared hosting anyway, expect broken or missing real-time updates for quiz lobby, countdowns, leaderboard sync, participant events, and other broadcast-driven features.

To run this project on `https://zynquiz.shadhinlab.xyz` with Laravel Reverb, make sure the browser-facing Reverb values and the server-side Reverb values stay separate.

Production `.env` essentials:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://zynquiz.shadhinlab.xyz

BROADCAST_CONNECTION=reverb
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST=zynquiz.shadhinlab.xyz
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
```

Server config templates are included here:

- `deploy/nginx/zynquiz.shadhinlab.xyz.conf`
- `deploy/supervisor/reverb.conf`
- `deploy/supervisor/queue-worker.conf`

## CloudPanel Deployment

CloudPanel is a supported deployment target for this project because it can run the full Laravel + Reverb stack on a VPS.

Use these repo files as your deployment references:

- `deploy/cloudpanel/README.md`
- `deploy/nginx/zynquiz.shadhinlab.xyz.conf`
- `deploy/supervisor/reverb.conf`
- `deploy/supervisor/queue-worker.conf`

Important: this app needs both Reverb and the queue worker running in the background. If only the website is online but those processes are missing, the quiz app will partially load but real-time updates and queued listeners will fail.

After updating env values on the server:

```bash
php artisan optimize:clear
npm run build
php artisan migrate --force
php artisan storage:link
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart online-quiz-queue
sudo supervisorctl restart online-quiz-reverb
sudo systemctl reload nginx
```

If the domain returns a WordPress-style `wp_die` error during WebSocket use, the domain or proxy is still pointing to the wrong backend instead of this Laravel + Reverb stack.
