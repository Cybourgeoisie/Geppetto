# Using Docker

## Ways to debug:
	```bash
	docker logs [container]
	```

## Development Builds
	## At root (/)
	```bash
	docker build -f Dockerfile-tests -t geppetto-tests .
	```

	## At /sql/
	```bash
	docker build -f Dockerfile-postgresql -t geppetto-postgresql .
	```


# Development Run

## Instructions: Using Docker Compose

	## Run docker compose to set up the database container and web-accessible container
	```bash
	docker-compose up -d
	docker-compose down
	```

	## Entering the containers
	```bash
	docker exec -it geppetto-tests /bin/bash
	```
