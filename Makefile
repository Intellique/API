MAKEFLAGS       += -rR --no-print-directory
SHELL=/bin/bash

GIT_ARCHIVE=storiqone-backend_$(shell git describe | perl -pe 's/^v([^-]+).*$$/$$1/').tar

.DEFAULT_GOAL := all

all: clean
	@echo 'UPDATE src'
	@git archive --format=tar -o ../${GIT_ARCHIVE} master
	@gzip -9vf ../${GIT_ARCHIVE}
	@echo 'BUILD package'
	@dpkg-buildpackage -us -uc -rfakeroot -sa

clean:
	@echo 'CLEAN'
	@dh_clean
	@rm -Rf doc
