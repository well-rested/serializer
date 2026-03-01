IMG_TAG ?= well-rested/serializer:local
IMG_BASE_VERSION ?= 8.4

DOCKER_RUN=docker run -v "$(shell pwd):/srv" -w /srv -it --rm $(IMG_TAG)

.PHONY: exec build init copy-phpunit-xml verify

# Chances are this will work on quite a lot of version of node/npm. We only use
# it for commitlint which is optional really. It will just warn if the versions
# are not at least the same versions used in CI.
check-node-npm-versions:
	@NODE_MIN=24.13.1; \
	NPM_MIN=11.8.0; \
	\
	if ! command -v node >/dev/null 2>&1; then \
		echo "[WARN] node is not installed, this only effects local development when using commitlint"; \
	fi; \
	\
	if ! command -v npm >/dev/null 2>&1; then \
		echo "[WARN] npm is not installed, this only effects local development when using commitlint"; \
	fi; \
	\
	NODE_VER=$$(node -v | sed 's/^v//'); \
	NPM_VER=$$(npm -v); \
	\
	if [ "$$(printf '%s\n' "$$NODE_MIN" "$$NODE_VER" | sort -V | head -n1)" != "$$NODE_MIN" ]; then \
		echo "[WARN] node version '$$NODE_VER' is lower than required '$$NODE_MIN', this only effects local development when using commitlint"; \
	fi; \
	\
	if [ "$$(printf '%s\n' "$$NPM_MIN" "$$NPM_VER" | sort -V | head -n1)" != "$$NPM_MIN" ]; then \
		echo "[WARN] npm version '$$NPM_VER' is lower than required '$$NPM_MIN', this only effects local development when using commitlint"; \
	fi; \
	\

# Ensure docker is installed.
# Version shouldn't matter all that much as we're using pretty basic stuff that 
# has been there as long as I remember
check-docker:
	@if ! command -v docker >/dev/null 2>&1; then \
		echo "docker is not installed, you should be using this for development"; \
		exit 1; \
	fi; \

# Verify that any requirements are present for local development
verify: check-docker check-node-npm-versions

# Ensure phpunit.xml is present
copy-phpunit-xml:
	if [ ! -f phpunit.xml ]; then cp phpunit.xml.dist phpunit.xml; fi

# Initialise the application repo for development
init: verify copy-phpunit-xml build

# Build the docker image for local development
build:
	docker build -t $(IMG_TAG) .

# Exec into a php runtime where you can run things
exec:
	$(DOCKER_RUN) bash

# Run the tests within the php runtime (via docker)
test:
	$(DOCKER_RUN) composer test

# Lints the last commit on the current branch to ensure it adheres to the standards
# set out by commit lint.
lint-last-commit:
	@npx commitlint --last