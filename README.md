# CloudShop starter files

This starter package supports the INFS3208 combined Dockerfile and Docker Compose assignment.

## Before you start

1. Copy `.env.example` to `.env` and change the two database passwords.
2. Create a `Dockerfile` in the project root.
3. Create a `docker-compose.yml` in the project root.
4. Validate the Compose file with `docker compose -f docker-compose.yml config`.
5. Start the application with `docker compose -f docker-compose.yml up --build -d --wait`.

The supplied PHP application expects the following Compose service names:

- `nginx`
- `app`
- `worker`
- `redis`
- `db`
- `phpmyadmin`

Do not rename `DB_HOST=db` or `REDIS_HOST=redis` unless you update the application environment consistently.

## Expected URLs

- Web application: `http://localhost:8080/`
- Submit an order: `http://localhost:8080/order.php`
- View processed orders: `http://localhost:8080/process.php`
- Health endpoint: `http://localhost:8080/health.php`
- phpMyAdmin: `http://localhost:8082/`

The host ports can be changed through `.env`.
