# Smart Delivery Service

[Demo](https://delivery.cloud-workflow.com)

php-8.4, Laravel-11, Octane, Roadrunner-2025, Temporal PHP SDK, VueJs-3

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
docker compose exec roadrunner php artisan wf-status-handler:stop <workflow_id>
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

Uladzimir Sadkou hofirma@gmail.com MIT
