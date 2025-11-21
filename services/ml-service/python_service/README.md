# SNKTIME Python Biometric Service

Servicio de reconocimiento biométrico para SNKTIME con soporte para facial, huellas dactilares y RFID.

## 🚀 Inicio Rápido

### Windows

```batch
# 1. Instalar dependencias
install_service.bat

# 2. Verificar instalación
python diagnose_service.py

# 3. Iniciar servicio
start_service.bat
# o manualmente:
# venv\Scripts\activate.bat
# python app.py
```

### Linux/Mac

```bash
# 1. Instalar dependencias
./install_service.sh

# 2. Verificar instalación
python diagnose_service.py

# 3. Iniciar servicio
python app.py
# o con uvicorn:
# uvicorn app:app --host 127.0.0.1 --port 8000 --reload
```

## 🔧 Solución de Problemas Comunes

### Windows Específicos

#### ❌ "python no se reconoce como comando"

**Solución:**
- Instalar Python desde https://python.org
- Asegurarse de marcar "Add Python to PATH" durante instalación
- Reiniciar terminal después de instalación

#### ❌ "Scripts\activate.bat no encontrado"

**Solución:**
```batch
# Recrear entorno virtual
python -m venv venv
venv\Scripts\activate.bat
```

#### ❌ "Permission denied" en Windows

**Solución:**
- Ejecutar como Administrador
- O cambiar permisos de carpeta

### Problemas Generales

#### ❌ "ImportError: No module named 'fastapi'"

**Solución:**
```bash
# Windows
venv\Scripts\activate.bat
pip install fastapi uvicorn

# Linux/Mac
source venv/bin/activate
pip install fastapi uvicorn
```

#### ❌ "ImportError: No module named 'insightface'"

**Solución:**
```bash
# Instalar InsightFace
pip install insightface

# Si falla, instalar dependencias por separado
pip install onnx onnxruntime
pip install --no-deps insightface
```

#### ❌ "Port 8000 already in use"

**Solución:**
```bash
# Windows - Cambiar puerto
uvicorn app:app --host 127.0.0.1 --port 8001 --reload

# Encontrar qué proceso usa el puerto
netstat -ano | findstr :8000

# Linux/Mac
lsof -i :8000
```

#### ❌ "Python version too old"

**Solución:**
- Instalar Python 3.8 o superior
- Verificar versión: `python --version`

## 📋 Verificación de Componentes

### Servicios Disponibles

- ✅ **Facial Recognition**: InsightFace con modelos Buffalo
- ✅ **Fingerprint**: Soporte para lectores fprintd
- ✅ **RFID**: Lectura de tarjetas RFID/NFC
- ✅ **Device Scanner**: Detección automática de dispositivos

### Endpoints API

- `GET /health` - Estado del servicio
- `GET /devices/scan` - Escanear dispositivos conectados
- `POST /facial/enroll` - Registrar rostro
- `POST /facial/verify` - Verificar rostro
- `POST /facial/extract` - Extraer características faciales
- `POST /fingerprint/enroll` - Registrar huella
- `POST /fingerprint/verify` - Verificar huella
- `POST /rfid/enroll` - Registrar RFID
- `POST /rfid/verify` - Verificar RFID

## ⚙️ Configuración

### Archivo .env

```env
# Servicio
HOST=127.0.0.1
PORT=8000
DEBUG=true

# Base de datos
DB_HOST=localhost
DB_PORT=3306
DB_NAME=synktime
DB_USER=root
DB_PASSWORD=

# InsightFace
INSIGHTFACE_MODEL_PATH=models
INSIGHTFACE_MODEL_NAME=buffalo_l
FACE_DETECTION_THRESHOLD=0.5
FACE_RECOGNITION_THRESHOLD=0.85
```

### Variables de Entorno

```bash
export PYTHONPATH="$PYTHONPATH:/path/to/service"
export INSIGHTFACE_MODEL_PATH="models"
```

## 🐛 Diagnóstico Avanzado

### Ejecutar Diagnóstico Completo

```bash
python diagnose_service.py
```

### Verificar Logs

```bash
# Con debug activado
uvicorn app:app --log-level debug
```

### Verificar Conectividad

```bash
# Probar endpoint de salud
curl http://127.0.0.1:8000/health

# Ver documentación API
open http://127.0.0.1:8000/docs
```

## 📦 Dependencias Críticas

### Requeridas
- Python 3.8+
- FastAPI
- Uvicorn
- OpenCV
- NumPy
- Pillow

### Opcionales (pero recomendadas)
- InsightFace (reconocimiento facial avanzado)
- PySerial (comunicación serial)
- PyUSB (dispositivos USB)

## 🚀 Despliegue en Producción

### Con Gunicorn

```bash
pip install gunicorn
gunicorn app:app -w 4 -k uvicorn.workers.UvicornWorker --bind 0.0.0.0:8000
```

### Con Docker

```dockerfile
FROM python:3.9-slim

WORKDIR /app
COPY requirements.txt .
RUN pip install -r requirements.txt

COPY . .
EXPOSE 8000

CMD ["uvicorn", "app:app", "--host", "0.0.0.0", "--port", "8000"]
```

## 📞 Soporte

Si encuentras problemas:

1. Ejecuta `python diagnose_service.py`
2. Revisa los logs del servicio
3. Verifica la configuración en `.env`
4. Consulta la documentación en `/docs`

### Logs Útiles

```bash
# Ver procesos en puerto 8000
netstat -tulpn | grep :8000

# Ver logs detallados
uvicorn app:app --log-level debug --access-log
```
