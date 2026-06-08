# Smart Delivery Service - Gemini AI Context

## Project Overview
Smart Delivery is a logistics/workflow system for managing customers, orders, couriers, routes, tasks, and delivery statuses.
It is a monolithic application leveraging a modern stack:
- **Backend:** Laravel 11 (PHP 8.4+) running on RoadRunner/Octane.
- **Frontend:** Vue 3 SPA built with Vite and styled with TailwindCSS.
- **Workflow Orchestration:** Temporal PHP SDK for complex background processes (e.g., task creation, order assignment, status propagation).
- **Real-time Updates:** Centrifugo is used to broadcast live status changes to the frontend.

## Directory Structure
- `src/`: The main application root (Laravel project + Vue frontend).
  - `src/app/Temporal/`: Contains Temporal workflows and activities.
  - `src/app/Http/`: Standard Laravel controllers and API logic.
  - `src/resources/js/`: Vue 3 SPA frontend files (views, components, stores).
- `docker/`: Infrastructure configuration for Centrifugo, Nginx, RoadRunner, Temporal, and a mock Go ERP server.
- `compose.yml`: Local development orchestration.

## Building and Running

**Dependencies Installation:**

1. Setup environment variables:
   ```sh
   cp .env.example .env
   cp src/.env.example src/.env 
   cp docker/centrifugo/config.json.example docker/centrifugo/config.json
   ```
2. Install PHP dependencies:
   ```sh
   cd src
   composer install --no-dev --optimize-autoloader --ignore-platform-req=ext-sockets
   # Or using docker as described in README
   ```
3. Install Node dependencies and build the frontend:
   ```sh
   cd src
   npm install
   npm run build
   ```

**Running the Application:**

Use Docker Compose from the root directory to spin up the entire infrastructure:
```sh
docker compose up -d
```
This will start:
- `roadrunner` (Laravel Octane & Temporal Worker on port 8000)
- `mysql`
- `temporal` & Temporal UI
- `centrifugo`
- `mailhog`
- `go-server` (Mock ERP callback server)

To run database migrations and seed data:
```sh
docker compose exec roadrunner php artisan migrate --seed
```

## Development Conventions

- **Full Stack Awareness:** Changes often require modifications across the stack. For instance, a new status update might involve a Temporal Workflow/Activity (`src/app/Temporal`), an API Controller (`src/app/Http`), a Centrifugo broadcast, and a Vue component update (`src/resources/js`).
- **Real-time First:** The application relies on Centrifugo for pushing updates. Avoid heavy polling where Centrifugo can be used to propagate state changes.
- **Workflows:** Long-running processes or multi-step operations (like Route calculation, ERP syncing) should be orchestrated via Temporal Workflows rather than standard Laravel queues or synchronous HTTP requests.
- **API Centric:** The frontend is a standalone Vue SPA talking to the Laravel backend via API (`src/routes/api.php`). Sanctum is used for session-based authentication.
- **Dockerized Runtime:** Assume the application is running within its RoadRunner/Docker environment when dealing with filesystem paths, server configurations, or background workers.
