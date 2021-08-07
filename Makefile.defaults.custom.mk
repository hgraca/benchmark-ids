HOST_IP=`docker network inspect ${DOCKER_NETWORK} | grep Gateway | awk '{print $$2}' | tr -d '"'`
