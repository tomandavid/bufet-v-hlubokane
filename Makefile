# Makefile for Bufet v Hlubokáně website

# Default port for local server
PORT ?= 8080

.PHONY: run serve clean help docker docker-stop docker-logs

# Default target
help:
	@echo "Bufet v Hlubokáně - Website Development Commands"
	@echo ""
	@echo "Usage:"
	@echo "  make run         - Start local PHP development server on port $(PORT)"
	@echo "  make serve       - Alias for 'make run'"
	@echo "  make docker      - Start production-like Apache server via Docker (port 80)"
	@echo "  make docker-stop - Stop Docker container"
	@echo "  make docker-logs - View Docker container logs"
	@echo "  make clean       - Clean temporary files"
	@echo "  make help        - Show this help message"
	@echo ""
	@echo "To use a different port:"
	@echo "  make run PORT=3000"
	@echo ""
	@echo "After starting, open:"
	@echo "  http://localhost:$(PORT)           - Bufet v Hlubokáně (dev server)"
	@echo "  http://localhost                   - Bufet v Hlubokáně (Docker)"
	@echo "  http://localhost/caffe-upaji.html  - Nejen Caffé u Páji"
	@echo "  http://localhost/cms/              - CMS Login"

# Start local PHP development server
run:
	@echo "============================================"
	@echo "Starting PHP development server..."
	@echo "============================================"
	@echo ""
	@echo "Website URLs:"
	@echo "  Bufet v Hlubokáně:    http://localhost:$(PORT)"
	@echo "  Nejen Caffé u Páji:   http://localhost:$(PORT)/caffe-upaji.html"
	@echo "  CMS Admin:            http://localhost:$(PORT)/cms/"
	@echo ""
	@echo "Press Ctrl+C to stop the server"
	@echo "============================================"
	@php -S localhost:$(PORT)

# Alias for run
serve: run

# Clean temporary files
clean:
	@echo "Cleaning temporary files..."
	@find . -name ".DS_Store" -delete 2>/dev/null || true
	@find . -name "*.pyc" -delete 2>/dev/null || true
	@find . -name "__pycache__" -type d -delete 2>/dev/null || true
	@echo "Done."

# Start Docker container (production-like environment)
docker:
	@echo "============================================"
	@echo "Starting Apache + PHP via Docker..."
	@echo "============================================"
	@echo ""
	@echo "Website URLs:"
	@echo "  Bufet v Hlubokáně:    http://localhost"
	@echo "  Nejen Caffé u Páji:   http://localhost/caffe-upaji.html"
	@echo "  CMS Admin:            http://localhost/cms/"
	@echo ""
	@echo "Use 'make docker-stop' to stop the server"
	@echo "============================================"
	@docker-compose up -d
	@echo ""
	@echo "Container started! Access at http://localhost"

# Stop Docker container
docker-stop:
	@echo "Stopping Docker container..."
	@docker-compose down
	@echo "Done."

# View Docker logs
docker-logs:
	@docker-compose logs -f
