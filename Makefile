#
#	laravel-api-logger - Makefile
#
#	@author 	Jeroen Derks <jeroen@derks.it>
#	@since		2017/May/23
#	@license	GPLv3 https://www.gnu.org/licenses/gpl.html
#	@copyright	Copyright (c) 2017-2021 Jeroen Derks / Derks.IT
#	@url		https://github.com/Magentron/laravel-scripts/
#
#	This file is part of laravel-api-logger.
#
#	This file is subject to the terms and conditions defined in file 'LICENSE' (also
#	available as an HTML file: 'LICENSE.html'), which is part of this source code package.
#

IODIRNAME=Laravel-API-Logger
OS:=$(shell uname -s)
NPROCS:=$(shell [ Darwin = $(OS) ] && sysctl -n hw.ncpu || nproc)
PHP_SRC=src
PHP_SRC_TEST=config $(PHP_SRC) tests
SRC=$(PHP_SRC_TEST)
PHPDOCUMENTOR=phpdocumentor3
PHPUNIT=$(PHP) vendor/bin/phpunit -d xdebug.max_nesting_level=250 -d memory_limit=1024M  --testdox-xml=build/logs/phpunit.xml $(PHPUNIT_EXTRA)
TMP=.tmp

all:	composer test static-analysis phpdox phpdoc-md

$(TMP):
	mkdir -p $(TMP)

clean:
	rm -r $(TMP)

composer:
	composer install --dev

#
#	Testing
#
test t:
	time $(PHPUNIT) $(EXTRA)

test-fast fast-test testfast fasttest:
	time $(PHPUNIT) --no-coverage $(EXTRA)

test-func testfunc:
	@[ ! -z "$(FUNC)" ] || (echo "missing FUNC=..."; exit 1)
	make test EXTRA="--filter '/::$(FUNC)\$$\$$/' $(EXTRA)"

test-fast-func test-fastfunc testfast-func testfastfunc test-func-fast test-funcfast testfuncfast:
	@[ ! -z "$(FUNC)" ] || (echo "missing FUNC=..."; exit 1)
	make testfast EXTRA="--filter '/::$(FUNC)\$$\$$/' $(EXTRA)"

test-profiler testprofiler:
	@cwd=`pwd`; if [ -z "$(FUNC)" ]; then \
		make testfast PHP="$(PHP) -d xdebug.profiler_enable=1 -d xdebug.profiler_output_name=cachegrind.out.%p -d xdebug.profiler_output_dir=$$cwd/storage/tmp/xdebug" EXTRA='$(EXTRA)'; \
	 else \
		make testfastfunc PHP="$(PHP) -d xdebug.profiler_enable=1 -d xdebug.profiler_output_name=cachegrind.out.%p -d xdebug.profiler_output_dir=$$cwd/storage/tmp/xdebug" EXTRA='$(EXTRA)' FUNC='$(FUNC)'; \
	 fi

test-stop teststop:
	make test EXTRA="--stop-on-failure $(EXTRA)"

#
#	Lint
#
lint lint-parallel:	
	@make -j4 phplint xmllint

lint-sequential:	phplint xmllint 

bladelint blade-lint lint-blade lintblade:
	@: echo lint - Blade...
	@: nice -20 $(ARTISAN) blade:lint --quiet

jsonlint json-lint lint-json lintjson:
	@echo lint - JSON...
	@find $(SRC) -name '*.json' | nice -20 parallel 'echo {}:; jsonlint -q {}' > .tmp.jsonlint 2>&1;\
		egrep -B1 '^(Error:|\s|\.\.\. )' .tmp.jsonlint | egrep -v ^--; res=$$?; rm -f .tmp.jsonlint; [ 0 != "$$res" ]

phplint php-lint lint-php lintphp:
	@echo lint - PHP...
	@find $(PHP_SRC_TEST) -name '*.php' | nice -20 parallel 'php -l {}' | fgrep -v 'No syntax errors detected' > .tmp.phplint;\
		[ ! -s .tmp.phplint ]; res=$$?; cat .tmp.phplint; rm -f .tmp.phplint; exit $$res

xmllint xml-lint lint-xml lintxml:
	@echo lint - XML...
	@find $(SRC) -name '*.xml' | while read file; do nice -20 xmllint --noout "$$file"; done

#
#	Static code analysis
#
loc:
	@cloc --follow-links $(PHP_SRC_TEST)

static-analysis static-analyzis static analysis analyzis analyse analyze stat anal:	phplint phpcpd phpcs phploc phpmd

phpcbf:
	vendor/bin/phpcbf --standard=PSR2 -p --parallel=$(NPROCS) -s $(EXTRA) $(PHP_SRC_TEST) 

phpcpd:
	vendor/bin/phpcpd $(EXTRA) $(PHP_SRC_TEST) 

phpcs:	build/logs
	vendor/bin/phpcs --standard=PSR2 -p --parallel=$(NPROCS) --report-xml=build/logs/phpcs.xml -s $(EXTRA) $(PHP_SRC_TEST) 

phploc:	build/logs
	vendor/bin/phploc --log-xml=build/logs/phploc.xml $(EXTRA) $(PHP_SRC_TEST)

phpmd:
	-vendor/bin/phpmd $(PHP_SRC) ansi cleancode,codesize,controversial,design,naming,unusedcode $(EXTRA)

phpmd-xml phpmdx:	build/logs
	-vendor/bin/phpmd $(PHP_SRC) xml cleancode,codesize,controversial,design,naming,unusedcode --report-file build/logs/pmd.xml $(EXTRA)

phpstan:
	vendor/bin/phpstan analyse $(EXTRA) $(PHP_SRC_TEST)

phpdoc-md:
	vendor/bin/phpdoc-md

phpdox:	phpmdx
	vendor/bin/phpdox

build build/logs:
	mkdir -p $@

deploy:	static-analysis test phpdox phpdoc-md
	cp -va build/phpdoc-md/* ../magentron.github.io/$(IODIRNAME)/
	ln -nsf README.md ../magentron.github.io/$(IODIRNAME)/index.md
