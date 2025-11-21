# Synktime ML Service

Machine Learning and Biometric Recognition Service for Synktime.

## Features

- Facial recognition using InsightFace
- Fingerprint verification
- RFID card identification
- Biometric enrollment and verification

## Structure

```
/app/              # Main application code
/model/            # ML model files (not in git)
/python_service/   # Service implementation
  /config/        # Configuration
  /models/        # Data models
  /services/      # Business logic services
```

## Setup

### Prerequisites

- Python 3.8+
- CUDA-capable GPU (optional, for better performance)

### Install Dependencies

```bash
pip install -r requirements.txt
```

### Run Service

```bash
python python_service/app.py
```

Or using Docker:

```bash
docker build -t synktime-ml .
docker run -p 8000:8000 synktime-ml
```

## API Endpoints

- `POST /enroll` - Enroll biometric data
- `POST /verify/facial` - Verify facial recognition
- `POST /verify/fingerprint` - Verify fingerprint
- `POST /verify/rfid` - Verify RFID card
- `GET /health` - Health check

## Environment Variables

- `DB_HOST` - Database host
- `DB_PORT` - Database port
- `DB_NAME` - Database name
- `DB_USER` - Database user
- `DB_PASSWORD` - Database password
- `INSIGHTFACE_MODEL_PATH` - Path to InsightFace models
- `INSIGHTFACE_MODEL_NAME` - Model name (e.g., 'buffalo_l')
- `PY_SERVICE_HOST` - Service host (default: 0.0.0.0)
- `PY_SERVICE_PORT` - Service port (default: 8000)

## GPU Support

The service can leverage GPU acceleration for faster facial recognition. Ensure CUDA is properly configured if using GPU.

### Dockerfile (GPU-ready)

See `Dockerfile` for GPU-enabled container configuration.

## Security Notes

- This service should only be accessible within the internal network
- Do not expose publicly without proper authentication
- Use internal Docker network for service-to-service communication

## Development

### Run Tests

```bash
pytest tests/
```

### Diagnostics

```bash
python python_service/diagnose_service.py
```
