#!/bin/sh

# Build the docker images
docker build -f ./docker/Dockerfile-tests -t geppetto-tests .
docker build -f ./docker/Dockerfile-postgresql -t geppetto-postgresql .
