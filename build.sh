#!/bin/sh

# Build the docker images
docker build -f ./tests/Dockerfile-tests -t geppetto-tests .
docker build -f ./tests/Dockerfile-postgresql -t geppetto-postgresql .
