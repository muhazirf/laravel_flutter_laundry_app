
# Docker untuk Laravel API - Jagoan Laundry

Docker configuration untuk backend Laravel API aplikasi Jagoan Laundry.

## 📋 Table of Contents

- [Prerequisites](#prerequisites)
- [Struktur Direktori](#struktur-direktori)
- [Instalasi](#instalasi)
- [Penggunaan](#penggunaan)
- [Environment Variables](#environment-variables)
- [Service URLs](#service-urls)
- [Common Commands](#common-commands)
- [Development Workflow](#development-workflow)
- [Production Deployment](#production-deployment)

## 🚀 Prerequisites

Sebelum memulai, pastikan Anda telah menginstal:

- [Docker](https://docs.docker.com/get-docker/) (v20.10+)
- [Docker Compose](https://docs.docker.com/compose/install/) (v2.0+)
- [Git](https://git-scm.com/)

## 📁 Struktur Direktori

```
laravel-laundry-be/
├── docker-compose.yml              # Konfigurasi Docker Compose untuk development
├── docker-compose.prod.yml         # Konfigurasi Docker Compose untuk production
├── .env.example                    # Template environment file untuk development
├── .env.production.example         # Template environment file untuk production
├── .env.docker.example             # Template environment file untuk Docker
├── README.md                       # Dokumentasi ini
├── TROUBLESHOOTING.md              # Troubleshooting dan optimasi
├── DOCKER-UPDATE.md               # Update konfigurasi terbaru
├── FILES-TO-UPDATE.md             # Daftar file yang perlu diperbarui
├── nginx/                          # Konfigurasi Nginx
│   ├── Dockerfile                  # Dockerfile untuk Nginx
│   ├── default.conf                # Konfigurasi virtual host
│   └── fastcgi_params              # Konfigurasi FastCGI
├── php/                            # Konfigurasi PHP
│   ├── Dockerfile                  # Dockerfile untuk PHP-FPM
│   └── php.ini                     # Konfigurasi PHP
├── mysql/                          # Konfigurasi MySQL
│   ├── init.sql                    # Script inisialisasi database
│   └── my.cnf                      # Konfigurasi MySQL
├── scripts/                        # Skrip utility
│   ├── setup.sh                    # Skrip setup awal
│   ├── start.sh                    # Skrip start containers
│   ├── stop.sh                     # Skrip stop containers
│   ├── utils.sh                    # Skrip utility commands
│   └── make-executable.sh          # Skrip untuk membuat skrip executable
└── src/                            # Laravel application code
    ├── app/
    ├── bootstrap/
    ├── config/
    ├── database/
    ├── public/
    ├── resources/
    ├── routes/
    ├── storage/
    ├── tests/
    ├── .env
    ├── .env.example
    ├── artisan
    ├── composer.json
    └── ...
```

## 🔧 Instalasi

### 1. Clone Repository

```bash
git clone <repository-url>
cd laravel-laundry-be
```

### 2. Setup Awal

Jalankan skrip setup untuk mengkonfigurasi environment:

```bash
cd scripts
./make-executable.sh
./setup.sh
```

Skrip ini akan:
- Membuat direktori yang diperlukan
- Menyalin file environment
- Generate APP_KEY
- Set permissions
- Build dan start containers
- Install dependencies

### 3. Manual Setup (Opsional)

Jika Anda ingin melakukan setup secara manual:

```bash
# 1. Buat direktori yang diperlukan
mkdir -p logs/{nginx,mysql,php} backups nginx/ssl

# 2. Salin environment files
cp .env.example .env
cp .env.production.example .env.production
cp .env.docker.example .env.docker

# 3. Update .env file dengan konfigurasi Anda
nano .env

# 4. Build dan start containers
docker-compose down
docker-compose build --no-cache
docker-compose up -d

# 5. Install Laravel dependencies (jika diperlukan)
docker-compose exec php composer install
docker-compose exec php php artisan migrate
docker-compose exec php php artisan db:seed
```

## 🎯 Penggunaan

### Start Containers

```bash
# Start semua containers
./scripts/start.sh

# Atau dengan docker-compose
docker-compose up -d
```

### Stop Containers

```bash
# Stop semua containers
./scripts/stop.sh

# Atau dengan docker-compose
docker-compose down
```

### Restart Containers

```bash
# Restart semua containers
docker-compose restart

# Restart service tertentu
docker-compose restart php
docker-compose restart nginx
docker-compose restart mysql
```

### View Logs

```bash
# View semua logs
./scripts/utils.sh logs

# View logs service tertentu
./scripts/utils.sh logs php
./scripts/utils.sh logs nginx
./scripts/utils.sh logs mysql

# Atau dengan docker-compose
docker-compose logs -f
docker-compose logs -f php
```

## 🔧 Environment Variables

### Development (.env)

```env
# Application
APP_NAME="Laundry API"
APP_ENV=local
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laundry_db
DB_USERNAME=laundry_user
DB_PASSWORD=laundry_password

# Cache (tanpa Redis)
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120
```

### Production (.env.production)

```env
# Application
APP_NAME="Laundry API"
APP_ENV=production
APP_KEY=base64:YOUR_SECURE_APP_KEY
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laundry_db
DB_USERNAME=laundry_user
DB_PASSWORD=SECURE_PASSWORD

# Cache (tanpa Redis)
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120
```

## 🌐 Service URLs

Setelah menjalankan containers, Anda dapat mengakses layanan berikut:

| Service | URL | Description |
|---------|-----|-------------|
| Laravel App | http://localhost:8000 | Aplikasi Laravel utama |
| API Endpoints | http://localhost:8000/api/v1 | REST API endpoints |
| MailHog | http://localhost:8025 | Email testing interface |
| PhpMyAdmin | http://localhost:8080 | Database management |
| MySQL | localhost:3306 | Database connection |

## 🛠️ Common Commands

### Laravel Commands

```bash
# Run artisan commands
./scripts/utils.sh artisan migrate
./scripts/utils.sh artisan db:seed
./scripts/utils.sh artisan tinker
./scripts/utils.sh artisan schedule:run

# Cache management
./scripts/utils.sh cache:clear
./scripts/utils.sh config:cache
./scripts/utils.sh optimize

# Testing
./scripts/utils.sh test
```

### Package Management

```bash
# Composer commands
./scripts/utils.sh composer install
./scripts/utils.sh composer update
./scripts/utils.sh composer require package-name

# NPM commands
./scripts/utils.sh npm install
./scripts/utils.sh npm run build
./scripts/utils.sh npm run dev
```

### Database Management

```bash
# Create backup
./scripts/utils.sh backup

# Restore from backup
./scripts/utils.sh restore path/to/backup.sql

# Access MySQL directly
docker-compose exec mysql mysql -u laundry_user -plau
ndry_password laundry_db

# Check database status
docker-compose exec php php artisan db:show
```

### Container Management

```bash
# Execute bash in container
./scripts/utils.sh exec php
./scripts/utils.sh exec nginx
./scripts/utils.sh exec mysql

# Rebuild containers
./scripts/utils.sh rebuild

# Clean up Docker resources
./scripts/utils.sh clean

# Check container status
./scripts/utils.sh status
```

## 🔄 Development Workflow

### 1. Development Setup

```bash
# Start development environment
./scripts/start.sh

# Install dependencies
./scripts/utils.sh composer install
./scripts/utils.sh npm install

# Run migrations
./scripts/utils.sh artisan migrate

# Seed database
./scripts/utils.sh artisan db:seed
```

### 2. Daily Development

```bash
# Start containers (if not running)
./scripts/start.sh

# View logs
./scripts/utils.sh logs -f

# Run tests
./scripts/utils.sh test

# Clear cache
./scripts/utils.sh cache:clear
```

### 3. Code Changes

```bash
# After changing PHP code, no restart needed
# After changing composer.json:
./scripts/utils.sh composer install

# After changing npm packages:
./scripts/utils.sh npm install
./scripts/utils.sh npm run build

# After changing Dockerfile or docker-compose.yml:
./scripts/utils.sh rebuild
```

## 🚀 Production Deployment

### 1. Server Preparation

```bash
# Install Docker and Docker Compose
curl -fsSL https://get.docker.com -o get-docker.sh
sh get-docker.sh

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
```

### 2. Deploy to Production

```bash
# Clone repository
git clone <repository-url>
cd laravel-laundry-be

# Setup production environment
cp .env.production.example .env
# Edit .env with production values

# Build and start production containers
docker-compose -f docker-compose.prod.yml up -d --build

# Run production optimizations
docker-compose -f docker-compose.prod.yml exec php php artisan config:cache
docker-compose -f docker-compose.prod.yml exec php php artisan route:cache
docker-compose -f docker-compose.prod.yml exec php php artisan view:cache
docker-compose -f docker-compose.prod.yml exec php php artisan optimize
```

### 3. SSL Setup (Optional)

```bash
# Generate SSL certificates
mkdir -p nginx/ssl
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout nginx/ssl/key.pem \
  -out nginx/ssl/cert.pem

# Update nginx configuration for HTTPS
# Edit nginx/default.conf to add SSL configuration
```

### 4. Backup Setup

```bash
# Create backup directory
mkdir -p backups

# Setup cron job for automated backups
crontab -e
# Add: 0 2 * * * /path/to/laravel-laundry-be/scripts/backup.sh
```

## 🔒 Security Considerations

### 1. Environment Security

- Jangan pernah commit `.env` file ke version control
- Gunakan strong passwords untuk database
- Generate secure APP_KEY dengan `php artisan key:generate`
- Update environment variables untuk production

### 2. Network Security

- Jangan expose port database ke public di production
- Gunakan firewall untuk membatasi akses
- Implement rate limiting di Laravel

### 3. Container Security

- Regular update base images
- Gunakan non-root user di containers
- Limit container resources

## 📊 Monitoring

### 1. Health Checks

```bash
# Check container health
docker-compose ps

# Check service health
curl http://localhost:8000/health
```

### 2. Log Monitoring

```bash
# View real-time logs
docker-compose logs -f

# Check error logs
docker-compose logs php | grep ERROR
```

### 3. Resource Monitoring

```bash
# Monitor resource usage
docker stats

# Check disk usage
docker system df
```

## 🔧 Troubleshooting

Untuk troubleshooting lengkap, lihat file [TROUBLESHOOTING.md](TROUBLESHOOTING.md).

### Common Issues

1. **Port conflicts**: Ubah port di docker-compose.yml
2. **Permission issues**: Fix dengan `chown` dan `chmod`
3. **Database connection**: Check MySQL container status
4. **502 errors**: Check PHP-FPM dan Nginx configuration

## 🔄 Update Configuration

Untuk update konfigurasi terbaru (menggunakan path `src` dan tanpa Redis), lihat file [DOCKER-UPDATE.md](DOCKER-UPDATE.md) dan [FILES-TO-UPDATE.md](FILES-TO-UPDATE.md).

### Perubahan Terbaru:

1. **Path Changes**: Semua path volume sekarang menggunakan `./src` instead of `../laravel-app`
2. **No Redis**: Redis service telah dihapus dan cache driver diubah ke `file`
3. **Environment**: Update environment variables untuk mencerminkan perubahan ini

### Cara Update:

1. Backup konfigurasi existing (jika ada)
2. Update semua file yang terdaftar di [FILES-TO-UPDATE.md](FILES-TO-UPDATE.md)
3. Rebuild containers:
   ```bash
   docker-compose down
   docker-compose build --no-cache
   docker-compose up -d
   ```

## 📚 Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [Nginx Documentation](https://nginx.org/en/docs/)
- [MySQL Documentation](https://dev.mysql.com/doc/)

## 🤝 Contributing

1. Fork repository
2. Create feature branch
3. Make changes
4. Test thoroughly
5. Submit pull request

## 📄 License

This project is licensed under the MIT License.

---

**Happy Coding! 🚀**

Jika Anda mengalami masalah atau memiliki pertanyaan, jangan ragu untuk membuka issue di repository atau menghubungi tim development.

## 📝 Catatan Penting

- **Struktur Proyek**: Proyek Laravel berada di folder `src`
- **Tidak Ada Redis**: Konfigurasi ini tidak menggunakan Redis, cache menggunakan file system
- **Path Volume**: Semua path volume sudah disesuaikan dengan struktur baru
- **Environment**: Pastikan untuk menyesuaikan file `.env` dengan konfigurasi yang benar
- **File Update**: Lihat [FILES-TO-UPDATE.md](FILES-TO-UPDATE.md) untuk daftar lengkap file yang perlu diperbarui