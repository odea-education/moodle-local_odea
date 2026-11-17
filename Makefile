# Dev toolchain for local_odea. `make check` is the quick local check.
#
# Composer is bootstrapped locally into .composer/ so nothing needs to be
# installed system-wide.

COMPOSER_DIR := .composer
COMPOSER := $(COMPOSER_DIR)/composer.phar

.PHONY: check lint stan fix deps clean release

check: lint stan

$(COMPOSER):
	mkdir -p $(COMPOSER_DIR)
	php -r "copy('https://getcomposer.org/installer', '$(COMPOSER_DIR)/composer-setup.php');"
	php $(COMPOSER_DIR)/composer-setup.php --install-dir=$(COMPOSER_DIR) --filename=composer.phar
	rm $(COMPOSER_DIR)/composer-setup.php

deps: $(COMPOSER)
	php $(COMPOSER) install --no-interaction --quiet

lint: deps
	vendor/bin/phpcs

fix: deps
	vendor/bin/phpcbf || true

stan: deps
	vendor/bin/phpstan analyse --no-progress --memory-limit=2G

clean:
	rm -rf vendor $(COMPOSER_DIR)

# Reads $plugin->release from version.php so the zip name always matches
# what the admin UI and the plugins directory will show for this build.
RELEASE := $(shell sed -n "s/.*\$$plugin->release *= *'\([^']*\)'.*/\1/p" version.php)

release:
	rm -rf build
	mkdir -p build/odea
	rsync -a \
		--exclude='vendor/' --exclude='.composer/' --exclude='composer.json' \
		--exclude='composer.lock' --exclude='.phpcs-cache' --exclude='phpstan.neon' \
		--exclude='tools/' --exclude='Makefile' --exclude='.github/' \
		--exclude='.git/' --exclude='.gitignore' --exclude='build/' \
		--exclude='phpcs.xml' --exclude='*.zip' \
		. build/odea/
	cd build && zip -rq ../local_odea-$(RELEASE).zip odea
	rm -rf build
	@echo "Built local_odea-$(RELEASE).zip"
	@unzip -l local_odea-$(RELEASE).zip
