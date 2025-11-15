# Smart Delivery Service

[Demo](https://delivery.cloud-workflow.com)

php-8.3, Laravel-11, Octane, Roadrunner-2025, Temporal PHP SDK, VueJs-3

```sh
cp .env.example .env

cp src/.env.example src/.env 
``` 
Change your real passwords, keys and other credentials for example centrifugo config
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
