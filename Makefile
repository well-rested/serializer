LOCAL_TAG=serializer:local
DOCKER_RUN=docker run -v "$(shell pwd):/srv" -w /srv -it --rm $(LOCAL_TAG)

.PHONY: exec build init copy-phpunit-xml

copy-phpunit-xml:
	if [ ! -f phpunit.xml ]; then cp phpunit.xml.dist phpunit.xml; fi

# Initialise the application repo for development
init: copy-phpunit-xml build

build:
	docker build -t $(LOCAL_TAG) .

exec:
	$(DOCKER_RUN) bash

test:
	$(DOCKER_RUN) ./vendor/bin/phpunit