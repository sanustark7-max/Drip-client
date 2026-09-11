# DRIP Admin Panel

## Railway deploy (fixed)

If Nixpacks fails, Railway will use **Dockerfile** automatically when present.

1. Push these files to GitHub repo `Drip-client`
2. Railway → Redeploy
3. Generate domain
4. Open `https://YOUR-DOMAIN/`
5. Login API: `https://YOUR-DOMAIN/l.php`

Admin password: `dripadmin123` (change in api.php)

## Local
```bash
php -S 0.0.0.0:8080
```
