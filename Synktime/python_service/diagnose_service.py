#!/usr/bin/env python3
"""
SNKTIME Python Service Diagnostic Script
Ayuda a identificar y solucionar problemas comunes al iniciar el servicio
"""

import sys
import os
import subprocess
import importlib.util

def check_python_version():
    """Verificar versión de Python"""
    print("🐍 Verificando versión de Python...")
    version = sys.version_info
    if version.major >= 3 and version.minor >= 8:
        print(f"   ✅ Python {version.major}.{version.minor}.{version.micro} - OK")
        return True
    else:
        print(f"   ❌ Python {version.major}.{version.minor}.{version.micro} - Se requiere Python 3.8+")
        return False

def check_dependencies():
    """Verificar dependencias críticas"""
    print("\n📦 Verificando dependencias críticas...")

    critical_deps = [
        'fastapi',
        'uvicorn',
        'pydantic',
        'opencv-python',
        'numpy',
        'pillow'
    ]

    missing = []
    for dep in critical_deps:
        try:
            if dep == 'opencv-python':
                import cv2
            elif dep == 'pillow':
                import PIL
            else:
                importlib.import_module(dep)
            print(f"   ✅ {dep}")
        except ImportError:
            print(f"   ❌ {dep} - FALTANTE")
            missing.append(dep)

    return missing

def check_insightface():
    """Verificar InsightFace (puede fallar)"""
    print("\n🤖 Verificando InsightFace...")
    try:
        import insightface
        print("   ✅ InsightFace disponible")
        return True
    except ImportError as e:
        print(f"   ⚠️  InsightFace no disponible: {e}")
        print("   💡 Para instalar: pip install insightface")
        return False

def check_port_availability():
    """Verificar si el puerto 8000 está disponible"""
    print("\n🔌 Verificando puerto 8000...")
    try:
        import socket
        sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        result = sock.connect_ex(('127.0.0.1', 8000))
        sock.close()

        if result == 0:
            print("   ❌ Puerto 8000 ocupado")
            return False
        else:
            print("   ✅ Puerto 8000 disponible")
            return True
    except Exception as e:
        print(f"   ⚠️  No se pudo verificar puerto: {e}")
        return True

def check_virtual_environment():
    """Verificar si estamos en un entorno virtual"""
    print("\n🔧 Verificando entorno virtual...")
    in_venv = hasattr(sys, 'real_prefix') or (hasattr(sys, 'base_prefix') and sys.base_prefix != sys.prefix)

    if in_venv:
        print("   ✅ Entorno virtual activado")
        return True
    else:
        print("   ⚠️  No se detecta entorno virtual")
        print("   💡 Recomendado: python -m venv venv && source venv/bin/activate")
        return False

def run_diagnostics():
    """Ejecutar diagnóstico completo"""
    print("🚀 SNKTIME Python Service - Diagnóstico de Problemas")
    print("=" * 60)

    results = {
        'python_version': check_python_version(),
        'dependencies': check_dependencies(),
        'insightface': check_insightface(),
        'port': check_port_availability(),
        'venv': check_virtual_environment()
    }

    print("\n" + "=" * 60)
    print("📋 RESUMEN DEL DIAGNÓSTICO")
    print("=" * 60)

    all_good = True

    if not results['python_version']:
        all_good = False
        print("❌ Problema crítico: Versión de Python incompatible")

    if results['dependencies']:
        all_good = False
        print(f"❌ Dependencias faltantes: {', '.join(results['dependencies'])}")

    if not results['port']:
        all_good = False
        print("❌ Problema: Puerto 8000 ocupado")

    if not results['venv']:
        print("⚠️  Recomendación: Usar entorno virtual")

    if not results['insightface']:
        print("⚠️  InsightFace no disponible (funcionalidad limitada)")

    if all_good:
        print("✅ Todos los checks básicos pasaron")
        print("\n💡 Para iniciar el servicio:")
        print("   python app.py")
        print("   # o")
        print("   uvicorn app:app --host 127.0.0.1 --port 8000 --reload")
    else:
        print("\n🔧 SOLUCIONES RECOMENDADAS:")
        print("1. Instalar dependencias faltantes:")
        print("   pip install -r requirements.txt")
        print()
        print("2. Instalar InsightFace (opcional):")
        print("   pip install insightface")
        print()
        print("3. Liberar puerto 8000 si está ocupado")
        print()
        print("4. Usar entorno virtual:")
        print("   python -m venv venv")
        print("   source venv/bin/activate  # Linux/Mac")
        print("   # o")
        print("   venv\\Scripts\\activate   # Windows")

    return results

def main():
    try:
        run_diagnostics()
    except Exception as e:
        print(f"❌ Error durante el diagnóstico: {e}")
        return 1
    return 0

if __name__ == "__main__":
    sys.exit(main())
