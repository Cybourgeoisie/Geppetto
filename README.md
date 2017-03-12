# Geppetto

Geppetto is a PHP ORM for use with PostgreSQL. Its goals are to simplify application development, reduce boilerplate code, improve data security, and streamline data management between PHP and relational databases.

## Notice

Geppetto is currently deployed in my production applications, so I can attest to its stability and utility. However, this repository is a work-in-progress, as I'm working to refactor the entire project and add a suite of unit tests for reliability, security and backward compatibility. **I do not recommend using this project until it reaches a stable state.**


# Running Tests with Docker

To simplify the testing process across multiple processes and system configurations, all of the tests can be run within Docker containers. You'll need to install the latest versions of Docker and Docker Compose first: [Docker website](https://www.docker.com).


To build and run, make the docker containers using the build script, and then use docker-compose to bring up the containers.

```bash
./build.sh
docker-compose up -d
```

And when you're finished, bring down the docker containers.

```bash
docker-compose down
```
