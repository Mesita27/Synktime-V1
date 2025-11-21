param(
    [switch]$Force,
    [switch]$SkipBuildTools
)

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "INSTALADOR AUTOMATICO DE INSIGHTFACE" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Verificar Python
Write-Host "Verificando Python..." -ForegroundColor Yellow
try {
    $pythonVersion = python --version 2>&1
    Write-Host "✓ Python encontrado: $pythonVersion" -ForegroundColor Green
} catch {
    Write-Host "✗ ERROR: Python no esta instalado o no esta en el PATH" -ForegroundColor Red
    Write-Host "Por favor instala Python primero desde: https://python.org" -ForegroundColor Red
    Read-Host "Presiona Enter para salir"
    exit 1
}

# Verificar pip
Write-Host "Verificando pip..." -ForegroundColor Yellow
try {
    $pipVersion = pip --version 2>&1
    Write-Host "✓ Pip encontrado: $pipVersion" -ForegroundColor Green
} catch {
    Write-Host "✗ ERROR: pip no esta disponible" -ForegroundColor Red
    Read-Host "Presiona Enter para salir"
    exit 1
}

# Verificar si InsightFace ya esta instalado
Write-Host "" "Verificando si InsightFace ya esta instalado..." -ForegroundColor Yellow
$insightfaceInstalled = $false
try {
    python -c "import insightface; print('InsightFace disponible')" 2>&1 | Out-Null
    if ($LASTEXITCODE -eq 0) {
        $insightfaceInstalled = $true
        Write-Host "✓ InsightFace ya esta instalado correctamente!" -ForegroundColor Green
        if (-not $Force) {
            goto test_insightface
        }
    }
} catch {
    Write-Host "InsightFace no esta instalado" -ForegroundColor Yellow
}

if (-not $insightfaceInstalled -or $Force) {
    Write-Host "" "========================================" -ForegroundColor Cyan
    Write-Host "PASO 1: Verificar Microsoft Visual C++ Build Tools" -ForegroundColor Cyan
    Write-Host "========================================" -ForegroundColor Cyan

    $buildToolsInstalled = $false

    if (-not $SkipBuildTools) {
        # Verificar versiones de Visual Studio Build Tools
        $vsVersions = @("14.0", "15.0", "16.0", "17.0")
        foreach ($version in $vsVersions) {
            try {
                $regPath = "HKLM:\SOFTWARE\Microsoft\VisualStudio\$version\VC"
                if (Test-Path $regPath) {
                    Write-Host "✓ Microsoft Visual C++ Build Tools encontrado (Version $version)" -ForegroundColor Green
                    $buildToolsInstalled = $true
                    break
                }
            } catch {
                # Continuar con la siguiente version
            }
        }

        if (-not $buildToolsInstalled) {
            Write-Host "" "Microsoft Visual C++ Build Tools no encontrado" -ForegroundColor Yellow
            Write-Host "Descargando e instalando automaticamente..." -ForegroundColor Yellow
            Write-Host ""

            try {
                Write-Host "Descargando Visual Studio Build Tools 2022..." -ForegroundColor Yellow
                Invoke-WebRequest -Uri "https://aka.ms/vs/17/release/vs_BuildTools.exe" -OutFile "vs_BuildTools.exe"

                Write-Host "" "Instalando Visual Studio Build Tools..." -ForegroundColor Yellow
                Write-Host "NOTA: La instalacion puede tomar varios minutos..." -ForegroundColor Yellow
                Write-Host ""

                $installArgs = @(
                    "--quiet",
                    "--wait",
                    "--norestart",
                    "--nocache",
                    "--installPath", "`"${env:ProgramFiles(x86)}\Microsoft Visual Studio\2022\BuildTools`"",
                    "--add", "Microsoft.VisualStudio.Workload.VCTools",
                    "--add", "Microsoft.VisualStudio.Component.VC.Tools.x86.x64",
                    "--add", "Microsoft.VisualStudio.Component.Windows10SDK.19041"
                )

                $process = Start-Process -FilePath "vs_BuildTools.exe" -ArgumentList $installArgs -Wait -PassThru
                if ($process.ExitCode -eq 0) {
                    Write-Host "✓ Visual Studio Build Tools instalado correctamente!" -ForegroundColor Green
                    $buildToolsInstalled = $true
                } else {
                    throw "Instalacion fallida con codigo: $($process.ExitCode)"
                }
            } catch {
                Write-Host "✗ ERROR: No se pudo instalar Visual Studio Build Tools automaticamente" -ForegroundColor Red
                Write-Host "Descargalo manualmente desde: https://visualstudio.microsoft.com/visual-cpp-build-tools/" -ForegroundColor Red
                Write-Host "Luego ejecuta este script nuevamente con -SkipBuildTools" -ForegroundColor Yellow
                Read-Host "Presiona Enter para salir"
                exit 1
            }
        }
    } else {
        Write-Host "Omitiendo verificacion de Build Tools (-SkipBuildTools)" -ForegroundColor Yellow
        $buildToolsInstalled = $true
    }

    Write-Host "" "========================================" -ForegroundColor Cyan
    Write-Host "PASO 2: Instalar InsightFace" -ForegroundColor Cyan
    Write-Host "========================================" -ForegroundColor Cyan

    Write-Host "Actualizando pip..." -ForegroundColor Yellow
    pip install --upgrade pip

    Write-Host "" "Instalando InsightFace..." -ForegroundColor Yellow
    Write-Host "NOTA: La instalacion puede tomar varios minutos..." -ForegroundColor Yellow
    Write-Host ""

    try {
        pip install insightface
        if ($LASTEXITCODE -ne 0) {
            throw "pip install fallo con codigo: $LASTEXITCODE"
        }
    } catch {
        Write-Host "" "ERROR: Fallo la instalacion de InsightFace" -ForegroundColor Red
        Write-Host "Intentando con metodo alternativo..." -ForegroundColor Yellow
        Write-Host ""

        pip install insightface --no-cache-dir --force-reinstall
        if ($LASTEXITCODE -ne 0) {
            Write-Host "" "ERROR: No se pudo instalar InsightFace" -ForegroundColor Red
            Write-Host "Posibles soluciones:" -ForegroundColor Yellow
            Write-Host "1. Reinicia la computadora y ejecuta este script nuevamente" -ForegroundColor Yellow
            Write-Host "2. Instala manualmente desde: https://github.com/deepinsight/insightface" -ForegroundColor Yellow
            Write-Host "3. Usa -SkipBuildTools si ya tienes Build Tools instalado" -ForegroundColor Yellow
            Read-Host "Presiona Enter para salir"
            exit 1
        }
    }
}

:test_insightface
Write-Host "" "========================================" -ForegroundColor Cyan
Write-Host "PASO 3: Verificar instalacion" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

Write-Host "Verificando que InsightFace funcione correctamente..." -ForegroundColor Yellow
try {
    $testResult = python -c "
try:
    import insightface
    from insightface.app import FaceAnalysis
    print('SUCCESS: InsightFace importado correctamente')
    print('SUCCESS: FaceAnalysis disponible')
except ImportError as e:
    print('ERROR:', str(e))
    exit(1)
except Exception as e:
    print('ERROR:', str(e))
    exit(1)
" 2>&1

    if ($testResult -match "SUCCESS") {
        Write-Host "✓ Instalacion exitosa!" -ForegroundColor Green
        Write-Host "" "========================================" -ForegroundColor Green
        Write-Host "¡INSTALACION COMPLETADA EXITOSAMENTE!" -ForegroundColor Green
        Write-Host "========================================" -ForegroundColor Green
        Write-Host "" "InsightFace esta listo para usar en tu sistema de reconocimiento facial" -ForegroundColor White
        Write-Host "Ahora puedes ejecutar tu aplicacion Python" -ForegroundColor White
    } else {
        Write-Host "✗ ERROR: InsightFace no funciona correctamente" -ForegroundColor Red
        Write-Host "Detalles: $testResult" -ForegroundColor Red
        exit 1
    }
} catch {
    Write-Host "✗ ERROR al verificar InsightFace: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

Write-Host "" "Presiona Enter para continuar..." -ForegroundColor Yellow
Read-Host
