# Rule: Simple Hosting Stack (Apache, MySQL, Cronjobs Only)

## Problem Statement
Introducing complex infrastructure dependencies (like Redis, Elasticsearch, Node.js background processors, Supervisor, RabbitMQ, SQS, or specialized runners) makes the application difficult to host on simple, traditional, or standard VPS hosting environments. The architecture must remain ultra-portable and lightweight, relying exclusively on basic standard hosting features: Apache, MySQL, and standard Linux Cronjobs.

## Rules & Guidelines

### 1. Strictly Allowed Technologies
You may only rely on the following infrastructural components for production deployment:
- **Web Server**: Apache (with `.htaccess` for rewrites and rules).
- **Database**: MySQL (or MariaDB).
- **Task Scheduling**: Standard Linux Cronjobs (`crontab`).
- **Runtime**: PHP.

### 2. Forbidden Technologies
Do **NOT** introduce, configure, or require any of the following for the application to function:
- **Redis / Memcached**: Do not use in-memory key-value stores for caching or sessions.
- **Message Brokers**: Do not use RabbitMQ, AWS SQS, Kafka, etc.
- **Search Engines**: Do not use Elasticsearch, Meilisearch, or Algolia as hard requirements.
- **Node.js (Runtime)**: Do not require persistent Node.js processes (e.g., SSR servers, WebSocket servers running in Node, PM2). *Note: Node.js/NPM is perfectly fine for local compilation/build steps (like Vite), but NOT for production server execution.*
- **Process Monitors**: Do not rely on Supervisor, Systemd, or PM2 to keep daemon workers alive. All background processing must be triggerable via Cronjobs.

### 3. Caching and Sessions
- **Sessions**: Must use the `database` or `file` session driver. Do not use `redis` or `memcached`.
- **Cache**: Must use the `database` or `file` cache driver. Do not use `redis` or `memcached`.

### 4. Background Jobs and Queues
- **Queue Driver**: All asynchronous jobs must use the `database` driver. 
- **Queue Execution**: Since Supervisor or continuous daemon processes (like `php artisan queue:work`) are not always supported on simple shared hosting, queue processing must be designed to be triggered by Cronjobs. (e.g., scheduling a command that runs `php artisan queue:work --stop-when-empty` every minute, or using the Laravel Task Scheduler to run queued jobs).

### 5. WebSockets & Real-Time
- If real-time features are necessary, they must either use a third-party managed service (like Pusher API) or be designed to fallback gracefully to standard HTTP polling if a local WebSocket server (which requires a persistent daemon) cannot be run. Do not require a persistent local WebSocket daemon (like Laravel Reverb) as a strict dependency for the application to boot and function.

### 6. Summary Checklist
- [ ] Are we using the `database` or `file` driver for Cache and Session?
- [ ] Is the Queue driver set to `database`?
- [ ] Have we avoided introducing Redis, Meilisearch, or other external infrastructure services?
- [ ] Can all background tasks be scheduled and executed purely via standard Cronjobs?



---
