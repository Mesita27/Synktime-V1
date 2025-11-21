# syntax=docker/dockerfile:1
FROM python:3.11-slim

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1

# Dependencias del sistema para paquetes científicos / biométricos
RUN apt-get update && apt-get install -y --no-install-recommends \
        build-essential \
        libgl1 \
        libglib2.0-0 \
        libssl-dev \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Copia requirements y los instala
COPY python_service/requirements.txt ./
RUN pip install --no-cache-dir -r requirements.txt

# Copia el servicio completo
COPY python_service/ .

# Puerto interno del servicio biométrico
EXPOSE 8000

# Healthcheck
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s CMD curl -f http://localhost:8000/healthz || exit 1

CMD ["uvicorn", "app:app", "--host", "0.0.0.0", "--port", "8000"]
