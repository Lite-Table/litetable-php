.PHONY: help install test benchmark lint vuln-check clean

help:
	@echo "Available commands:"
	@echo "  make install     - Install composer dependencies"
	@echo "  make test        - Run test suite"
	@echo "  make benchmark   - Run performance benchmarks"
	@echo "  make lint        - Run static analysis and code style check"
	@echo "  make vuln-check  - Check dependencies for security vulnerabilities"
	@echo "  make clean       - Clean vendor, cache and temp files"

install:
	composer install

test:
	@bash scripts/test.sh

benchmark:
	@bash scripts/benchmark.sh

lint:
	@bash scripts/lint.sh

vuln-check:
	@bash scripts/vuln-check.sh

clean:
	rm -rf vendor/ .phpunit.result.cache build/
