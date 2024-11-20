# Smart Delivery Service

[Demo](https://delivery.cloud-workflow.com)

php8.3, Laravel 11, Octane, Roadrunner 2024, Temporal PHP SDK, Vue3

```sh
cp .env.example .env

cp src/.env.example src/.env

docker compose up -d

docker compose exec roadrunner php app.php handle-order-status

docker compose exec roadrunner php app.php handle-order-status

docker compose exec roadrunner rr -c /etc/rr/.rr.yaml reset

cd src 
npm install
npm run build
```
Services:
- :80 the main app
- :8075/mailhog Mailhog
- :8080 Temporal UI
- :8090 go-server (Mock of an ERP System)

It requires Nginx proxy on the host machine for https

Uladzimir Sadkou hofirma@gmail.com
