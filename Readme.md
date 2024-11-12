Smart Delivery

php8.3, Laravel 11, Octane, Roadrunner 2024, Temporal PHP SDK

cp .env.example .env

cp src/.env.example src/.env

docker compose up -d

docker compose exec roadrunner php app.php handle-order-status

docker compose exec roadrunner php app.php handle-order-status

docker compose exec roadrunner rr -c /etc/rr/.rr.yaml reset

:80/order the main app
:8075/mailhog Mailhog
:8080 Temporal UI
:8090 go-server
