# Makefile
#
# This file contains the commands most used in DEV, plus the ones used in CI and PRD environments.
#

# Execute targets as often as wanted
.PHONY: config

# Mute all `make` specific output. Comment this out to get some debug information.
.SILENT:

# make commands be run with `bash` instead of the default `sh`
SHELL='/bin/bash'

include Makefile.defaults.mk
ifneq ("$(wildcard Makefile.defaults.custom.mk)","")
  include Makefile.defaults.custom.mk
endif

# .DEFAULT: If the command does not exist in this makefile
# default:  If no command was specified
.DEFAULT default:
	if [ -f ./Makefile.custom.mk ]; then \
	    $(MAKE) -f Makefile.custom.mk "$@"; \
	else \
	    if [ "$@" != "default" ]; then echo "Command '$@' not found."; fi; \
	    $(MAKE) help; \
	    if [ "$@" != "default" ]; then exit 2; fi; \
	fi

help:  ## Show this help
	@echo "Usage:"
	@echo "     [ARG=VALUE] [...] make [command]"
	@echo "     make env-status"
	@echo "     NAMESPACE=\"dummy-app-namespace\" RELEASE_NAME=\"another-dummy-app\" make env-status"
	@echo
	@echo "Available commands:"
	@grep '^[^#[:space:]].*:' Makefile | grep -v '^default' | grep -v '^\.' | grep -v '=' | grep -v '^_' | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m  %-40s\033[0m %s\n", $$1, $$2}' | sed 's/://'

########################################################################################################################

###############################
## Docker
###############################

docker-create-network: ## Create the docker network to be used by this project docker-compose
	-docker network create ${DOCKER_NETWORK}
	docker network inspect ${DOCKER_NETWORK} | grep Gateway | awk '{print $$2}' | tr -d '"'

docker-up:  ## Start the application and keep it running and showing the logs
	DOCKER_USER_ID=${HOST_USER_ID} DOCKER_NETWORK=${DOCKER_NETWORK} HOST_IP=${HOST_IP} PROJECT=${PROJECT} docker-compose -f docker/docker-compose.yml up bechmark_ids_php bechmark_ids_mysql${OPTIONAL_CONTAINERS}

docker-up-daemon:  ## Start the application and keep it running in the background
	DOCKER_USER_ID=${HOST_USER_ID} DOCKER_NETWORK=${DOCKER_NETWORK} HOST_IP=${HOST_IP} PROJECT=${PROJECT} docker-compose -f docker/docker-compose.yml up -d bechmark_ids_php bechmark_ids_mysql${OPTIONAL_CONTAINERS}

docker-down:  ## Stop the application
	DOCKER_USER_ID=${HOST_USER_ID} DOCKER_NETWORK=${DOCKER_NETWORK} HOST_IP=${HOST_IP} PROJECT=${PROJECT} docker-compose -f docker/docker-compose.yml down

docker-logs:  ## Show the application logs
	DOCKER_USER_ID=${HOST_USER_ID} DOCKER_NETWORK=${DOCKER_NETWORK} HOST_IP=${HOST_IP} PROJECT=${PROJECT} docker-compose -f docker/docker-compose.yml logs -f

docker-shell:  ## Open a shell into the web(php) container
	DOCKER_USER_ID=${HOST_USER_ID} DOCKER_NETWORK=${DOCKER_NETWORK} HOST_IP=${HOST_IP} PROJECT=${PROJECT} docker-compose -f docker/docker-compose.yml exec bechmark_ids_php bash

docker-run:  ## Run the benchmark inside the app(php) container
	DOCKER_USER_ID=${HOST_USER_ID} DOCKER_NETWORK=${DOCKER_NETWORK} HOST_IP=${HOST_IP} PROJECT=${PROJECT} docker-compose -f docker/docker-compose.yml exec bechmark_ids_php sh -c 'make run'

###############################
## Commands
###############################
run-all: ## Run all the benchmark
	./bin/run all

run-inserts: ## Run the inserts benchmarks
	./bin/run insert

run-queries: ## Run the queries benchmarks
	./bin/run query
