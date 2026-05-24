# Smart Delivery: AI Agent Context

## Purpose
Smart Delivery is a logistics/workflow system for managing customers, orders, couriers, routes, tasks, and delivery statuses.
It combines a Laravel backend, Vue SPA frontend, Temporal workflows, and realtime updates via Centrifugo.

## Repository Layout
- `src/`: Main application (Laravel + Vue + Vite + Tailwind)
- `docker/`: Infra and service configs (RoadRunner, Temporal, Centrifugo, Nginx, Go mock ERP)
- `compose.yml`: Local/dev orchestration for all services
- `docker/go_server/`: Mock ERP webhook service in Go

## Languages
- PHP (primary backend)
- JavaScript (frontend app and build config)
- Vue SFC (`.vue`)
- CSS (Tailwind + custom styles)
- Go (mock ERP service)
- YAML/JSON (infrastructure/configuration)

## Backend Stack
- Framework: Laravel `^11.47`
- Runtime: PHP `>=8.4`
- App server: Laravel Octane + RoadRunner
- Auth: Laravel Sanctum
- Permissions/Roles: Spatie Laravel Permission
- DTO/Data mapping: Spatie Laravel Data
- Workflow orchestration: Temporal PHP SDK (`temporal/sdk ^2.16`)
- Realtime broadcasting: Centrifugo (`opekunov/laravel-centrifugo-broadcaster`)
- JWT support: `firebase/php-jwt`
- Testing: PHPUnit 11

Key backend areas:
- `src/app/Temporal/`: workflows/activities (order assignment, unassignment, status updates, task finishing, ERP observer)
- `src/app/Http/Controllers/`: API and dashboard controllers
- `src/app/Models/`: Eloquent models (Order, Task, Courier, Route, Slot, etc.)
- `src/database/migrations/`: schema for orders/tasks/routes/slots/working_hours/holidays/users/permissions
- `src/routes/api.php`: main business API

## Frontend Stack
- Framework: Vue `3.5.x`
- Router: Vue Router `4.6.x`
- State: Pinia `2.3.x`
- Build tool: Vite `7.3.x`
- Laravel integration: `laravel-vite-plugin`
- CSS: TailwindCSS `3.4.x` + PostCSS + Autoprefixer
- UI libs: Flatpickr, Vue Multiselect, Chart.js, MDI icons, VueUse
- Maps: `vue3-google-map`
- Realtime client: `centrifuge`

Key frontend areas:
- `src/resources/js/views/`: page-level views (Orders, Tasks, Couriers, Slots, etc.)
- `src/resources/js/components/`: reusable UI components
- `src/resources/js/stores/`: Pinia stores
- `src/resources/js/composables/`: reusable logic (including Centrifugo auth/connection)

## Infrastructure and Services
Orchestrated in `compose.yml`:
- `roadrunner` (PHP/Laravel app, port 8000)
- `mysql` (app DB)
- `temporal` + `temporal-ui` + `temporal-admin-tools`
- `opensearch` and `postgresql` (Temporal dependencies)
- `centrifugo` (realtime WS)
- `mailhog` (dev mail)
- `go-server` (mock ERP callback sender)

RoadRunner config: `docker/roadrunner/rr.yaml`
- HTTP worker + Temporal worker process (`php /app/worker.php`)
- Single worker defaults (`num_workers: 1`) in current config

## Data and Domain Notes
Core domain entities:
- Customer
- Courier (+ working hours, holidays)
- Order
- Task
- Route and route points
- Slot/capacity windows

Main flow (high-level):
1. Orders are created.
2. Orders are assigned into tasks/routes.
3. Temporal workflows execute orchestration and side effects.
4. Status updates propagate to clients via Centrifugo.
5. Mock ERP service can asynchronously confirm/decline orders via `/api/erp-webhook`.

## API and Auth Notes
- API routes are in `src/routes/api.php`.
- Uses Sanctum-protected route groups for most business endpoints.
- Login/logout endpoints exist.
- Public registration endpoint is intentionally disabled.
- User creation is available only for authenticated users via `POST /api/users` (manual/admin-driven creation flow).
- Includes endpoints for:
  - orders and assignment
  - tasks and route updates
  - courier management and schedules
  - slots management
  - centrifugo token generation

## Business Flow to Code Map
- Main pages and router definitions:
  - Dashboard: `src/resources/js/views/HomeView.vue`, route name `dashboard` in `src/resources/js/router/index.js`
  - Orders: `src/resources/js/views/Orders.vue`, route name `orders`
  - Tasks: `src/resources/js/views/Tasks.vue`, route name `tasks`
  - Edit Route: `src/resources/js/views/EditRouteView.vue`, route name `edit-route`

- Customer creates delivery order from point A to point B:
  - UI: `src/resources/js/views/OrderCreateFormView.vue`
  - API: `POST /api/order` in `src/routes/api.php`
  - Controller: `App\\Http\\Controllers\\OrderCreateController::createOrder`
  - Workflow trigger: `CreateOrderWorkflowInterface` (Temporal)
  - Order includes `startPointId` (pickup), `endPointId` (destination), date, and time slot (`slotId` -> `from`/`to`)

- Agent/driver delivers multiple orders (task assignment):
  - Task creation UI with selectable orders/courier: `src/resources/js/views/TaskCreateFormView.vue`
  - Task create API: `POST /api/task` -> `TaskController::createTask`
  - Order assignment/unassignment inside task:
    - UI: `src/resources/js/views/UpdateOrderInTaskView.vue`
    - APIs: `POST /api/add-orders-to-task`, `POST /api/unassign-order`
    - Controller: `OrderController`
    - Workflows: `AssignOrderWorkflowInterface`, `UnassignOrderWorkflowInterface`
  - Guardrail: `OrdersCanBeAddedRule` prevents adding orders already assigned to a task.

- Route editing and pickup/delivery sequence:
  - UI: `src/resources/js/views/EditRouteView.vue`
  - Data load:
    - Task list: `GET /api/tasks`
    - Orders by task: `GET /api/orders-by-task/{taskUuid}`
    - Route points: `GET /api/route/{taskUuid}`
  - Save route order:
    - API: `POST /api/update-route`
    - Controller: `RouteController::updateRoute`
    - Workflow: `UpdateRouteWorkflowInterface` -> `UpdateRoteActivity::updateRoute`
  - Validation:
    - Request class: `EditRouteRequest`
    - Rule: `FirstLastRouteRule`
    - Enforces first point must be one of assigned orders' pickup points and last point must be one of assigned orders' delivery points.
  - Frontend also provides immediate visual checks for invalid first/last route points in `EditRouteView.vue`.

- Route point semantics:
  - Enum: `src/app/Enums/RoutePointTypeEnum.php`
  - Types include pickup/start and delivery/finish variants (`START`, `FINISH`, `INTERMEDIATE`).
  - Resource returned to UI: `RouteResource` (`pointId`, `pointAddress`, `sequence`, `type`).

## Build and Run
Backend dependencies:
- Composer in `src/composer.json`

Frontend dependencies:
- npm in `src/package.json`

Typical local flow:
1. `docker compose up -d`
2. DB migrations/seeding via `php artisan`
3. Frontend build via `npm install && npm run build` (inside `src/`)

## Agent Working Guidelines for This Repo
- Treat this as a Laravel + Vue monorepo under `src/`.
- Check both workflow and HTTP paths when changing business logic:
  - `app/Temporal/*`
  - `app/Http/*`
- For UI changes, inspect views + composables + stores together.
- For status/event issues, inspect:
  - Temporal workflow/activity classes
  - Centrifugo service/composables
  - webhook endpoints (`/api/erp-webhook`)
- Validate changes against Dockerized runtime assumptions (RoadRunner/Octane, Temporal, MySQL).

## Important Config Entry Points
- `src/composer.json`
- `src/package.json`
- `src/config/octane.php`
- `docker/roadrunner/rr.yaml`
- `compose.yml`
- `src/routes/api.php`
- `src/routes/web.php`

## Known Characteristics / Caveats
- Repository currently includes built frontend artifacts under `src/public/build/`.
- Repository currently includes installed dependencies folders (`src/vendor/`, `src/node_modules/`) in workspace.
- `.agents/` and `.codex/` directories exist but are read-only in current filesystem state.
