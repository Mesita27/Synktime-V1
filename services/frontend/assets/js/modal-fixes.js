/**
 * Soluciones específicas para el modal de enrolamiento biométrico
 * Este script asegura que el modal funcione correctamente
 */

document.addEventListener('DOMContentLoaded', function() {
    // Fix para problema con modal-backdrop
    const fixModalBackdrop = () => {
        // Aplicar estilo directamente a cualquier backdrop existente
        document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
            backdrop.style.pointerEvents = 'none';
            backdrop.style.opacity = '0.5';
        });
        
        // Observamos cuando se añada el modal-backdrop
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length) {
                    // Buscar si se ha añadido un backdrop
                    mutation.addedNodes.forEach(function(node) {
                        if (node.classList && node.classList.contains('modal-backdrop')) {
                            // Aplicar estilos para que permita interacción
                            node.style.pointerEvents = 'none';
                            node.style.opacity = '0.5';
                        }
                    });
                }
            });
        });

        // Configurar observador para el cuerpo del documento
        observer.observe(document.body, { childList: true });
    };

    // Función para centrar correctamente el contenido del modal
    const fixModalContentAlignment = () => {
        // Verificar que el modal existe antes de modificarlo
        const modalElement = document.getElementById('biometricEnrollmentModal');
        if (!modalElement) return;
        
        // Ajustar el contenido al centro
        const modalContent = modalElement.querySelector('.modal-content');
        if (modalContent) {
            modalContent.style.margin = '0 auto';
            modalContent.style.backgroundColor = '#ffffff';
        }
        
        // Ajustar el diálogo modal para asegurar centrado
        const modalDialog = modalElement.querySelector('.modal-dialog');
        if (modalDialog) {
            modalDialog.style.display = 'flex';
            modalDialog.style.alignItems = 'center';
            modalDialog.style.justifyContent = 'center';
            modalDialog.style.margin = '1.75rem auto';
        }

        // Asegurar que las filas estén centradas
        const rows = modalElement.querySelectorAll('.row');
        rows.forEach(row => {
            row.classList.add('justify-content-center');
            row.style.width = '100%';
            row.style.marginLeft = '0';
            row.style.marginRight = '0';
        });

        // Corregir posicionamiento de video/canvas
        const videoElement = document.getElementById('faceVideo');
        const canvasElement = document.getElementById('faceCanvas');
        
        if (videoElement && canvasElement) {
            // Establecer dimensiones correctas
            const resizeVideoCanvas = () => {
                const width = videoElement.offsetWidth;
                const height = videoElement.offsetHeight;
                
                if (width && height) {
                    canvasElement.width = width;
                    canvasElement.height = height;
                    canvasElement.style.width = width + 'px';
                    canvasElement.style.height = height + 'px';
                }
            };
            
            // Aplicar al cargar y cuando cambie el tamaño
            resizeVideoCanvas();
            window.addEventListener('resize', resizeVideoCanvas);
            
            // También aplicar cuando se muestre el modal
            modalElement.addEventListener('shown.bs.modal', resizeVideoCanvas);
        }
    };

    // Función para corregir los problemas de interacción modal
    const fixModalInteraction = () => {
        const modalElement = document.getElementById('biometricEnrollmentModal');
        if (!modalElement) return;
        
        // Evento para cuando el modal se muestre
        modalElement.addEventListener('shown.bs.modal', function() {
            // Eliminar cualquier backdrop adicional
            const backdrops = document.querySelectorAll('.modal-backdrop');
            if (backdrops.length > 1) {
                // Dejar solo uno
                for (let i = 1; i < backdrops.length; i++) {
                    backdrops[i].remove();
                }
            }
            
            // Configurar el backdrop restante
            if (backdrops.length > 0) {
                backdrops[0].style.pointerEvents = 'none';
            }
            
            // Asegurar que el modal se puede interactuar
            modalElement.style.pointerEvents = 'auto';
        });
        
        // Evento para cuando el modal se oculte
        modalElement.addEventListener('hidden.bs.modal', function() {
            // Limpiar cualquier backdrop residual
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => backdrop.remove());
            
            // Eliminar clase modal-open del body
            document.body.classList.remove('modal-open');
            document.body.style.paddingRight = '';
            document.body.style.overflow = '';
        });
    };

    // Aplicar todas las soluciones
    fixModalBackdrop();
    fixModalContentAlignment();
    fixModalInteraction();
    
    console.log('✅ Modal fixes aplicados correctamente');
});
