# This file contains the default variables used in the Makefile
# If you want to change them, duplicate the file, name it "Makefile.defaults.custom.mk" and make the changes you want
HOST_USER_ID=`id -u` # This works for linux. If it doesnt work on your OS, hardcode your host user ID there
DOCKER_NETWORK='benchmark-ids-network'
PROJECT='benchmark-ids'
HOST_IP="host.docker.internal" # For linux, override this in "Makefile.defaults.custom.mk" with "HOST_IP=`docker network inspect ${DOCKER_NETWORK} | grep Gateway | awk '{print $$2}' | tr -d '"'`"
OPTIONAL_CONTAINERS='' # Add optional containers here, ie: ' worker other_container ...'
COVERAGE_REPORT_PATH="var/coverage.clover.xml"
TESTS_REPORT_PATH="var/tests.junit.xml"
