# 📦 Deployment Guide

This project uses a lightweight but production-ready deployment workflow based on:

- GitHub Actions (manual deploy workflow)
- SSH remote execution
- A small `deploy.sh` script
- Supervisor for long-running workers
- Cron (run as `www-data`) for Laravel’s Scheduler

The goal is to keep deployment simple, reproducible, and aligned with modern backend engineering practices.

---

## 1. Deployment Flow

1. Developer triggers the **Deploy** workflow in GitHub Actions
2. GitHub connects to the server over SSH
3. Server runs `deploy.sh`:
    - Pull latest code
    - Install optimized dependencies
    - Run migrations
    - Rebuild caches
4. Supervisor keeps the queue worker (`mq:work`) running
5. Cron (as `www-data`) runs Laravel’s scheduler every minute

This provides consistent deployments without requiring manual SSH operations.

---

## 2. GitHub Actions Deploy Workflow

Location: [`.github/workflows/deploy.yml`](../.github/workflows/deploy.yml)

Key points:

- Triggered manually via `workflow_dispatch`
- Secure SSH deployment using `appleboy/ssh-action`
- Uses Repository Secrets for credentials

Example:

```yaml
- name: Deploy via SSH
  uses: appleboy/ssh-action@v1.0.3
  with:
    host: ${{ secrets.SERVER_HOST }}
    username: ${{ secrets.SERVER_USER }}
    key: ${{ secrets.SERVER_SSH_KEY }}
    script: |
      cd /var/www/task-manager
      ./deploy.sh
```

This keeps CI/CD and server logic clearly separated.

---

## 3. Deployment Script (`deploy.sh`)

A minimal version used on the server:

```bash
#!/usr/bin/env bash
set -e

git pull origin main
composer install --no-dev --optimize-autoloader

php artisan migrate --force
php artisan optimize:clear
php artisan optimize

chown -R www-data:www-data storage bootstrap/cache

echo "Deploy complete."
```

Responsibilities:

- Update code
- Install optimized Composer dependencies
- Run database migrations
- Rebuild Laravel caches
- Fix permissions for runtime directories

This script stays intentionally simple for clarity and reliability.

---

## 4. Queue Workers (Supervisor)

`mq:work` is a long-running custom worker managed by Supervisor.

Example config (`/etc/supervisor/conf.d/mq-worker.conf`):

```ini
[program:mq-worker]
directory=/var/www/task-manager
command=php artisan mq:work
autostart=true
autorestart=true
user=www-data
stdout_logfile=/var/www/task-manager/storage/logs/mq-worker.log
redirect_stderr=true
stopwaitsecs=3600
```

Reload after updates:

```
sudo supervisorctl reread
sudo supervisorctl update
```

---

## 5. Scheduler (Cron)

Cron must run as **www-data** to avoid root-owned runtime files.

Edit www-data crontab:

```bash
sudo -u www-data crontab -e
```

Entry:

```
* * * * * cd /var/www/task-manager && php artisan schedule:run >> /dev/null 2>&1
```

Scheduler tasks run every minute and work in harmony with Supervisor.

---

## 6. Permissions

Laravel runtime directories must be writable by `www-data`:

```
storage/
bootstrap/cache/
```

`deploy.sh` enforces proper ownership:

```
chown -R www-data:www-data storage bootstrap/cache
```

This prevents permission conflicts between:

- Web requests
- Scheduler tasks
- Queue workers

---

## 7. Summary

The deployment approach balances **simplicity, automation, and correctness**:

- GitHub Actions → remote deploy
- `deploy.sh` → deterministic updates
- Supervisor → stable queue workers
- Cron → reliable scheduler
- Proper user isolation → safe storage operations

This setup demonstrates practical DevOps capability suitable for real-world backend work and remote development workflows.
