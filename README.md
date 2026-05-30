# Smart Delivery Service
[Demo](https://delivery.cloud-workflow.com)

[TemporalUi](https://docs.temporal.io/references/web-ui-configuration)

php-8.4, Laravel-11, Octane, Roadrunner-2025, Temporal PHP SDK, VueJs-3

## Features and Pages

### Authentication
- `Login` (`/login`)
- Session-based auth via Laravel Sanctum for all protected pages.

### Dashboard (`/`)
- Delivered orders trend chart for the latest 10 days.
- Chart groups data by courier/agent with a separate color per courier.
- Customers table with real data loaded from API.
- Google map widget.

### Orders
- `Orders` (`/orders`)
  - List of orders with status, customer, courier, pickup/destination, date and time window.
- `Create Order` (`/order`)
  - Create customer delivery orders from point A to point B.
  - Customer creation from modal.
  - Delivery slot selection by date and available capacity.

### Tasks and Assignment
- `Tasks` (`/tasks`)
  - List of tasks, courier, order count, and task status.
- `Create Task` (`/task`)
  - Select date, available courier, and accepted orders.
  - Create delivery task through workflow.
- `Update Order in Task` (`/update-order-in-task`)
  - Change order status inside a task.
  - Unassign order from task.
  - Add accepted orders to existing task.

### Route Planning
- `Edit Route` (`/edit-route`)
  - Load route points for selected task.
  - Drag-and-drop route sequence.
  - Validate first and last points (pickup first, delivery last).
  - Save updated route sequence.

### Couriers
- `Couriers` (`/couriers`)
  - List couriers and statuses.
  - Create courier.
  - Edit courier (name, phone, status).
- `Courier Working Hours` (`/courier-working-hours/:courierId`)
  - Manage weekly working hours.
  - Add/update/delete working intervals.
  - Manage courier holidays (date range + reason).

### Users
- `Users` (`/users`)
  - List users.
  - Manual user creation for authenticated staff.
  - Public self-registration endpoint is disabled.

### Realtime and Workflow
- Realtime status updates via Centrifugo.
- Temporal workflows for:
  - order creation and ERP status handling,
  - task creation/finishing,
  - order assignment/unassignment,
  - route updates and related activities.

### API and Health
- Main API under `/api/*`.
- Health endpoint: `/api/health`.

```sh
cp .env.example .env
cp src/.env.example src/.env 
cp docker/centrifugo/config.json.example docker/centrifugo/config.json
``` 
Change your real passwords, keys and other credentials for example centrifugo config
To install dependencies for first time run:
```sh
cd src
sudo docker run --rm \
    -v $(pwd):/app \
    -w /app \
    composer:2 \
    composer install --no-dev --optimize-autoloader --ignore-platform-req=ext-sockets
```

In production environment use nginx in front of roadrunner. To obtain SSL certificates run:
```
docker compose run --rm certbot certonly \
  --webroot \
  --webroot-path=/var/www/certbot \
  --email hofirma@gmail.com \
  --agree-tos \
  --no-eff-email \
  -d delivery.cloud-workflow.com \
  -d www.delivery.cloud-workflow.com
```

```
docker compose up -d

docker compose exec roadrunner php artisan key:generate

docker compose exec roadrunner php artisan migrate

docker compose exec roadrunner php artisan db:seed

docker compose exec roadrunner php artisan wf-status-handler:start

docker compose exec roadrunner rr -c /etc/rr/.rr.yaml reset

sudo apt install npm
cd src
npm install
npm run build
```
Populate default slots in the DB, for example:
id|from |to   |capacity|available
1 |8:00 |12:00|10      |10
2 |12:00|16:00|15      |15
3 |16:00|20:00|8       |8
4 |20:00|23:59|4       |4



to stop status handler:
```
docker compose exec roadrunner php artisan wf-status-handler:stop workflow-status-handler-v1
```

Services:
- :80 the main app
- :8075/mailhog Mailhog
- :8080 Temporal UI
- :8090 go-server (Mock of an ERP System)
- :8010 Centrifuge (websockets)

It requires Nginx proxy on the host machine for https, see docker/nginx/app.conf example

To create a docker image with roadrunner php and grpc:
```
docker build -t  gaintvlad/php-grpc-rrunner:v2025.1.2 -f docker/roadrunner/DockerfileRR .
```

There is a Golang mock erp in the docker/go_server folder.

### Orders
![orders](https://drive.google.com/uc?id=1ne4dWNmYl61FYtOHXWiXIuQRGeaFGnam)
### Tasks
![tasks](https://drive.google.com/uc?id=1G82NcATV2v7x7CkhpAudWLYZyBytBRlh)
### Edit orders
![edit orders](https://drive.google.com/uc?id=13p-dkxYoKdHqmYZjewQkFSQTvv4UJC04)
### Routes
![routes](https://drive.google.com/uc?id=16cbE-RQyaELB9R_RPZvN4N1NBVVnh0as)

### Google Map
https://vue3-google-map.com/getting-started

### UI Base Template
This project UI is based on [justboil/admin-one-vue-tailwind](https://github.com/justboil/admin-one-vue-tailwind).

Uladzimir Sadkou hofirma@gmail.com MIT
