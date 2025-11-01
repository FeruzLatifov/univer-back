# Univer Backend - Docker & Kubernetes Helper
.PHONY: help build up down logs shell test deploy k8s-deploy k8s-delete

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

# Docker Compose
build: ## Build Docker images
	docker-compose build

up: ## Start containers
	docker-compose up -d

down: ## Stop containers
	docker-compose down

logs: ## View logs
	docker-compose logs -f backend

shell: ## Enter backend container
	docker-compose exec backend sh

ps: ## Show running containers
	docker-compose ps

restart: ## Restart services
	docker-compose restart

# Database
db-import: ## Import univer.sql to database
	docker-compose exec -T postgres psql -U postgres -d univer < C:/Projects/univer/univer.sql

db-backup: ## Backup database
	docker-compose exec postgres pg_dump -U postgres univer > backup_$(shell date +%Y%m%d_%H%M%S).sql

# Laravel
artisan: ## Run artisan command (usage: make artisan CMD="migrate")
	docker-compose exec backend php artisan $(CMD)

key-generate: ## Generate APP_KEY
	docker-compose exec backend php artisan key:generate

jwt-secret: ## Generate JWT_SECRET
	docker-compose exec backend php artisan jwt:secret

cache-clear: ## Clear all caches
	docker-compose exec backend php artisan cache:clear
	docker-compose exec backend php artisan config:clear
	docker-compose exec backend php artisan route:clear

optimize: ## Optimize Laravel
	docker-compose exec backend php artisan config:cache
	docker-compose exec backend php artisan route:cache
	docker-compose exec backend php artisan view:cache

# Testing
test: ## Run tests
	docker-compose exec backend php artisan test

tinker: ## Laravel Tinker
	docker-compose exec backend php artisan tinker

# Kubernetes
k8s-build: ## Build and push K8s image
	docker build -t univer-backend:latest --target production .
	docker tag univer-backend:latest your-registry.com/univer-backend:latest
	docker push your-registry.com/univer-backend:latest

k8s-deploy: ## Deploy to Kubernetes
	kubectl apply -k k8s/overlays/prod

k8s-delete: ## Delete from Kubernetes
	kubectl delete -k k8s/overlays/prod

k8s-status: ## Check K8s deployment status
	kubectl get pods -n univer-prod
	kubectl get svc -n univer-prod
	kubectl get ingress -n univer-prod

k8s-logs: ## View K8s logs
	kubectl logs -f deployment/univer-backend -n univer-prod

k8s-shell: ## Enter K8s pod
	kubectl exec -it deployment/univer-backend -n univer-prod -- sh

# Production
prod-deploy: build up optimize ## Full production deployment
	@echo "✅ Production deployment complete"

# Cleanup
clean: ## Remove all containers and volumes
	docker-compose down -v
	docker system prune -f
