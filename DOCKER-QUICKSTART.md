# Docker Quick Start - 5 Daqiqa

## 🚀 LOCAL (Docker Compose)

```bash
# 1. Copy environment
cp .env.docker .env

# 2. Copy SQL file
copy C:\Projects\univer\univer.sql docker\postgres\init\

# 3. Build & Run
docker-compose up -d

# 4. Setup keys
docker-compose exec backend php artisan key:generate
docker-compose exec backend php artisan jwt:secret

# 5. Test
curl http://localhost:8000/api/health
```

**✅ Tayyor! Backend: http://localhost:8000**

---

## ☸️ KUBERNETES (Production)

```bash
# 1. Build image
docker build -t univer-backend:v1.0.0 --target production .

# 2. Push to registry
docker tag univer-backend:v1.0.0 your-registry.com/univer-backend:v1.0.0
docker push your-registry.com/univer-backend:v1.0.0

# 3. Create secrets
kubectl create namespace univer-prod

kubectl create secret generic db-credentials \
  --from-literal=database=univer \
  --from-literal=username=postgres \
  --from-literal=password=YourPassword \
  -n univer-prod

kubectl create secret generic jwt-secret \
  --from-literal=secret=$(openssl rand -base64 32) \
  -n univer-prod

# 4. Deploy
kubectl apply -k k8s/overlays/prod

# 5. Check
kubectl get pods -n univer-prod
```

**✅ Deployed! Check ingress for URL**

---

## 🛠️ MAKEFILE (Helper)

```bash
# Docker Compose
make build        # Build images
make up           # Start containers
make down         # Stop containers
make logs         # View logs
make shell        # Enter container

# Laravel
make key-generate # Generate APP_KEY
make jwt-secret   # Generate JWT_SECRET
make optimize     # Cache configs

# Kubernetes
make k8s-deploy   # Deploy to K8s
make k8s-logs     # View K8s logs
make k8s-status   # Check deployment

# All commands
make help
```

---

## 📦 Yaratilgan Fayllar

```
✅ Dockerfile (multi-stage)
✅ docker-compose.yml
✅ .dockerignore
✅ .env.docker
✅ Makefile

✅ docker/nginx/default.conf
✅ docker/supervisor/supervisord.conf
✅ docker/postgres/init/01-import-schema.sh

✅ k8s/base/*.yaml (8 files)
✅ k8s/overlays/prod/*.yaml (2 files)
```

---

## 🎯 Mikroservis Features

- ✅ Multi-stage build (dev/prod)
- ✅ Health checks
- ✅ Auto-scaling (HPA)
- ✅ Rolling updates
- ✅ Resource limits
- ✅ Persistent storage
- ✅ Redis caching
- ✅ Nginx reverse proxy

**Full docs:** [docs/sonnet/09-docker-kubernetes-guide.md](../docs/sonnet/09-docker-kubernetes-guide.md)
