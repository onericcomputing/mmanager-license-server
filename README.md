# M'Manager License Server

Server for license verification and PDF generation service.

## Requirements

- PHP 8.4+
- MySQL 8.0+
- Composer

## Local Development

```bash
# Start the containers
docker compose up -d

# Access the server
http://localhost:8881
```

## Deployment

Configure GitHub Secrets:
- `SSH_PRIVATE_KEY` - SSH private key for deployment
- `SERVER_HOST` - Production server hostname
- `SSH_USER` - SSH username
- `DEPLOY_PATH` - Path on server (e.g., `/var/www/license`)

## API Endpoints

- `POST /api/license/verify` - Verify a license
- `POST /api/license/activate` - Activate a license
- `POST /api/license/deactivate` - Deactivate a license
- `POST /api/pdf/generate` - Generate PDF (invoice/quote)
