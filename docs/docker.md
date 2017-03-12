# Using Docker

## Ways to debug:

	```
	docker logs [container]
	```

## Development Builds

	At root (/):

	```
	docker build -f ./docker/Dockerfile-tests -t geppetto-tests .
	docker build -f ./docker/Dockerfile-postgresql -t geppetto-postgresql .
	```


# Development Run

## Instructions: Using Docker Compose

	Run docker compose to set up the containers:
	
	```
	docker-compose up -d
	docker-compose down
	```

	Entering the containers:
	
	```
	docker exec -it geppetto-tests /bin/bash
	```
