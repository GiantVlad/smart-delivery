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
<iframe src="https://drive.google.com/file/d/1ne4dWNmYl61FYtOHXWiXIuQRGeaFGnam/preview" width="640" height="480" allow="autoplay"></iframe>
### Tasks
<iframe src="https://drive.google.com/file/d/1G82NcATV2v7x7CkhpAudWLYZyBytBRlh/preview" width="640" height="480" allow="autoplay"></iframe>
### Edit orders
<iframe src="https://drive.google.com/file/d/13p-dkxYoKdHqmYZjewQkFSQTvv4UJC04/preview" width="640" height="480" allow="autoplay"></iframe>
### Routes
<iframe src="https://drive.google.com/file/d/16cbE-RQyaELB9R_RPZvN4N1NBVVnh0as/preview" width="640" height="480" allow="autoplay"></iframe>

Uladzimir Sadkou hofirma@gmail.com MIT
