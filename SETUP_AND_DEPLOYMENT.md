# Setup & Deployment Guide

## Prerequisites

- PHP 8.2+
- Laravel 12
- MySQL/PostgreSQL
- Redis (optional, for queue processing)
- Composer
- Node.js & npm (for frontend assets)

## Initial Setup

### 1. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Update `.env` with your database and service credentials:

```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=smautomation
DB_USERNAME=root
DB_PASSWORD=

# Queue Configuration (use 'database' for development, 'redis' for production)
QUEUE_CONNECTION=database

# Social Media API Keys (configure at /admin/settings)
# These can be set in the database settings table
```

### 2. Database Setup

```bash
php artisan migrate
php artisan db:seed
```

### 3. Queue Configuration

**For Development (Database Queue):**
```bash
php artisan queue:table
php artisan queue:failed-table
php artisan migrate
```

**For Production (Redis Queue):**
- Install Redis
- Update `.env`: `QUEUE_CONNECTION=redis`

### 4. Validate Configuration

```bash
php artisan queue:validate
```

This command checks:
- Queue connection health
- Database tables for queue
- Failed jobs status
- Redis connection (if applicable)

## Running the Application

### Development Server
```bash
php artisan serve
```

### Queue Worker (Required for async jobs)

**Development:**
```bash
php artisan queue:work --timeout=60
```

**Production (keep running):**
```bash
php artisan queue:work --daemon --timeout=60
```

Or use a process manager (Supervisor, systemd, PM2):

**Supervisor Example** (`/etc/supervisor/conf.d/smautomation.conf`):
```ini
[program:smautomation-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/app/artisan queue:work --timeout=60
autostart=true
autorestart=true
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/app/storage/logs/queue.log
```

## Testing

### Run All Tests

```bash
php artisan test
```

### Run Specific Test Suite

```bash
# Feature tests
php artisan test tests/Feature/PostControllerTest.php

# Queue jobs
php artisan test tests/Feature/PostQueueJobsTest.php

# Authentication
php artisan test tests/Feature/AuthenticationTest.php
```

### Test a Specific Test Method

```bash
php artisan test tests/Feature/PostControllerTest.php --filter test_user_can_create_post_with_single_platform
```

### Generate Test Coverage Report

```bash
php artisan test --coverage
```

## Critical Features

### 1. Post Management

**Creating Posts:**
- Always provide `title`, `description`, and at least one platform
- Posts attached to multiple platforms are created as a single database record
- If status is 'published', PublishPostJob is dispatched immediately
- If status is 'scheduled' and time is in the past, PublishPostJob is dispatched

**Publishing Posts:**
- Uses queue jobs (PublishPostJob) for asynchronous processing
- Publishes to all assigned social media accounts
- Supports platform-specific settings (FB privacy, IG caption, etc.)
- Stores platform-specific response data for tracking

**Deleting Posts:**
- Draft posts are deleted immediately
- Published posts trigger DeletePostJob for each platform before local deletion
- Uses database transactions to ensure consistency
- Cannot delete posts currently being processed

### 2. Authentication & Authorization

- Users can only access/modify their own posts and accounts
- Admin users can access any post
- Social account ownership is validated on post creation
- Admin middleware required for sensitive routes

### 3. Queue Jobs

**PublishPostJob:**
- Triggered on post creation (if status = 'published')
- Triggered on scheduled time (if scheduled_at is past)
- Retries 3 times with 10-second backoff
- Publishes to all social accounts attached to post
- Updates post status throughout process

**DeletePostJob:**
- Triggered when deleting published posts
- One job per platform
- Retries 3 times with 10-second backoff
- Logs failed deletions for manual review

## Monitoring & Troubleshooting

### Check Queue Status

```bash
# View pending jobs
php artisan queue:work --daemonize

# Monitor failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

### View Application Logs

```bash
tail -f storage/logs/laravel.log
```

### Failed Jobs

Failed jobs are stored in `failed_jobs` table. To handle:

```bash
# List failed jobs
php artisan queue:failed

# Retry specific job(s)
php artisan queue:retry {id}

# Retry all failed jobs
php artisan queue:retry all

# Forget (delete) failed jobs
php artisan queue:forget {id}
```

### Common Issues

**Queue jobs not processing:**
- Check if queue worker is running: `ps aux | grep queue:work`
- Verify database connection and `jobs` table exists
- Check logs in `storage/logs/laravel.log`
- Ensure `QUEUE_CONNECTION` is correct in `.env`

**Social media API failures:**
- Check API credentials in settings
- Verify access tokens haven't expired
- Check platform-specific rate limits
- Review error details in post's `platform_specific_data` JSON

**Tests failing:**
- Ensure different test database is configured: `DB_CONNECTION=sqlite` in `.env.testing`
- Run migrations: `php artisan migrate --env=testing`
- Clear cache: `php artisan cache:clear`

## API Integrations

### Supported Platforms

| Platform | Features | Notes |
|----------|----------|-------|
| Facebook | Post, Delete | Graph API v22.0 |
| Instagram | Post, Delete | Graph API v22.0 |
| X (Twitter) | Post, Delete | API v2 |
| LinkedIn | Post, Delete | UGC API v2 |
| TikTok | Post | Delete not supported via API |
| YouTube | Post, Delete | Data API v3 |

### Configuring API Credentials

1. Go to `/admin/settings`
2. Navigate to "Social Media" section
3. Add API keys for each platform:
   - `{platform}_client_id`
   - `{platform}_client_secret`
   - `{platform}_api_version` (optional, has defaults)

## Security Checklist

- [ ] All dependencies updated
- [ ] `.env` file contains secure credentials (not in git)
- [ ] Database user has limited permissions
- [ ] Queue worker runs with appropriate permissions
- [ ] CSRF protection enabled on all forms (default in Laravel)
- [ ] SQL injection prevented (using Eloquent ORM)
- [ ] Authorization checks on all user-sensitive routes
- [ ] Social account ownership validated
- [ ] Rate limiting applied to public endpoints
- [ ] HTTPS enabled in production
- [ ] Secrets not logged (check logs don't contain tokens)

## Performance Tips

1. **Queue Processing:**
   - Increase number of queue workers in production
   - Use Redis instead of database for better performance

2. **Database:**
   - Index frequently queried columns: `user_id`, `status`, `platform`
   - Schedule cleanup of old logs and failed jobs

3. **Caching:**
   - Cache platform helper data
   - Cache user timezone information

4. **Media:**
   - Store media files in cloud storage (S3, etc.)
   - Implement CDN for asset delivery

## Deployment

### Via Web Server

```bash
# Pull latest code
git pull origin main

# Install dependencies
composer install --no-dev
npm install && npm run build

# Migrate database
php artisan migrate --force

# Clear cache
php artisan cache:clear
php artisan config:clear

# Start queue worker (via Supervisor or systemd)
supervisorctl restart smautomation-queue
```

### Using Docker

See `docker-compose.yml` if available, or create one:

```yaml
version: '3.8'
services:
  app:
    build: .
    ports:
      - "80:80"
    environment:
      - APP_ENV=production
      - QUEUE_CONNECTION=redis
    depends_on:
      - mysql
      - redis
  
  mysql:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: smautomation
  
  redis:
    image: redis:7
  
  queue:
    build: .
    command: php artisan queue:work --daemon
    depends_on:
      - mysql
      - redis
```

## Support

For issues or questions:
1. Check logs in `storage/logs/`
2. Review error messages in posts' `platform_specific_data`
3. Run `php artisan queue:validate` to check setup
4. Check test coverage to ensure functionality works
