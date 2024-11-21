# Smart Delivery Service

[Demo](https://delivery.cloud-workflow.com)

php-8.3, Laravel-11, Octane, Roadrunner-2024, Temporal PHP SDK, VueJs-3

```sh
cp .env.example .env

cp src/.env.example src/.env

docker compose up -d

docker compose exec roadrunner php artisan wf-status-handler:start

docker compose exec roadrunner php artisan wf-status-handler:stop <workflow_id>

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

### Orders
![orders](https://drive.google.com/uc?id=1ne4dWNmYl61FYtOHXWiXIuQRGeaFGnam)
### Tasks
![tasks](https://drive.google.com/uc?id=1G82NcATV2v7x7CkhpAudWLYZyBytBRlh)
### Edit orders
![edit orders](https://drive.google.com/uc?id=13p-dkxYoKdHqmYZjewQkFSQTvv4UJC04)
### Routes
![routes](https://drive.google.com/uc?id=16cbE-RQyaELB9R_RPZvN4N1NBVVnh0as)

Uladzimir Sadkou hofirma@gmail.com MIT
