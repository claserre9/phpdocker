
# PHP Docker

## Overview
This project sets up a multi-container Docker application, orchestrating various services such as Redis, MySQL, PHPMyAdmin, a webserver (Nginx), and PHP-FPM. It is configured using Docker Compose with a version specification of '3.8'. Below is a brief overview of each service and its role within the application.

## Services

### Redis
- **Image**: `redis:alpine`
- **Purpose**: Provides an in-memory data store.
- **Volumes**: 
  - `redis-data` is used to persist data stored in Redis.

### Redis Commander
- **Image**: `rediscommander/redis-commander:latest`
- **Purpose**: Offers a web-based user interface to manage Redis databases.
- **Environment Variables**:
  - `REDIS_HOSTS` specifies the Redis instance to connect to.
- **Ports**:
  - Maps port 8081 inside the container to port 8082 on the host.

### MySQL
- **Image**: `mysql:8.0`
- **Purpose**: Offers database services.
- **Working Directory**: `/app`
- **Volumes**:
  - `mysql-data` persists MySQL data.
  - Maps the project directory (`.`) to `/app` inside the container.
- **Environment Variables**:
  - Sets MySQL credentials and database information.
- **Ports**:
  - Maps port 3306 inside the container to port 16002 on the host.

### PHPMyAdmin
- **Image**: `phpmyadmin`
- **Purpose**: Provides a web interface for MySQL database management.
- **Restart Policy**: Always restart.
- **Ports**:
  - Maps port 80 inside the container to port 8080 on the host.
- **Environment Variables**:
  - Configures the connection to the MySQL service.

### Webserver (Nginx)
- **Image**: `nginx:alpine`
- **Working Directory**: `/app`
- **Volumes**:
  - Maps the project directory and Nginx configuration file.
  - `nginx-logs` persists Nginx log files.
- **Ports**:
  - Maps port 80 inside the container to port 16000 on the host.

### PHP-FPM
- **Build**: Specifies the Dockerfile located in the current directory.
- **Working Directory**: `/app`
- **Volumes**:
  - Maps the project directory and PHP INI overrides file.
  - `php-logs` persists PHP log files.

## Volumes
The following volumes are defined for data persistence:
- `redis-data`
- `mysql-data`
- `nginx-logs`
- `php-logs`

## Usage
To get started with this application, ensure you have Docker and Docker Compose installed. Then, navigate to the project's root directory and run:

```bash
docker-compose up
```

This command will start all the configured services. You can access PHPMyAdmin at `http://localhost:8080`, Redis Commander at `http://localhost:8082`, and the webserver at `http://localhost:16000`. 

## Customization
You can customize the application by modifying the `docker-compose.yml` file to suit your needs, adjusting service configurations, environment variables, and port mappings as necessary.

For more detailed information on Docker Compose, refer to the [official documentation](https://docs.docker.com/compose/).
