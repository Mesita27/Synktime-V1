/**
 * Sistema de Notificaciones Unificado
 * Reemplaza alertas tradicionales con notificaciones visuales elegantes
 */

// Sistema inicializado
let notificationSystemInitialized = false;

// Configuración global
const notificationConfig = {
  duration: 5000,          // Duración predeterminada en ms
  maxNotifications: 5,     // Número máximo de notificaciones simultáneas
  position: 'top-right',   // Posición: 'top-right', 'top-left', 'bottom-right', 'bottom-left'
  container: null,         // Contenedor DOM (se creará si no existe)
  animations: true         // Habilitar animaciones
};

/**
 * Inicializa el sistema de notificaciones
 */
function initNotificationSystem() {
  if (notificationSystemInitialized) return;
  
  console.log('🔔 Inicializando sistema de notificaciones...');
  
  // Crear contenedor si no existe
  if (!notificationConfig.container) {
    const container = document.createElement('div');
    container.className = 'notification-container';
    document.body.appendChild(container);
    notificationConfig.container = container;
  }
  
  // Sobrescribir la función alert global
  const originalAlert = window.alert;
  window.alert = function(message) {
    console.log('⚠️ Alert interceptado:', message);
    showNotification({
      message: message,
      type: 'info'
    });
    
    // Llamar al alert original solo en modo desarrollo si es necesario
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
      console.info('Original alert message (modo desarrollo):', message);
    }
  };
  
  notificationSystemInitialized = true;
  console.log('✅ Sistema de notificaciones inicializado correctamente');
}

/**
 * Muestra una notificación
 * @param {Object} options - Opciones de la notificación
 * @param {string} options.message - Mensaje a mostrar
 * @param {string} [options.title] - Título opcional
 * @param {string} [options.type] - Tipo: 'info', 'success', 'warning', 'error'
 * @param {number} [options.duration] - Duración en milisegundos, 0 para persistente
 */
function showNotification(options) {
  // Asegurarse que el sistema esté inicializado
  if (!notificationSystemInitialized) {
    initNotificationSystem();
  }
  
  // Valores predeterminados
  const settings = {
    message: options.message || 'Notificación del sistema',
    title: options.title || getDefaultTitle(options.type),
    type: options.type || 'info',
    duration: options.duration !== undefined ? options.duration : notificationConfig.duration,
    icon: getIconForType(options.type)
  };
  
  // Limitar número de notificaciones
  const currentNotifications = notificationConfig.container.querySelectorAll('.notification');
  if (currentNotifications.length >= notificationConfig.maxNotifications) {
    // Eliminar la notificación más antigua
    if (currentNotifications.length > 0) {
      currentNotifications[0].remove();
    }
  }
  
  // Crear elemento de notificación
  const notification = document.createElement('div');
  notification.className = `notification notification-${settings.type}`;
  
  notification.innerHTML = `
    <div class="notification-icon">
      <i class="${settings.icon}"></i>
    </div>
    <div class="notification-content">
      <h4 class="notification-title">${settings.title}</h4>
      <p class="notification-message">${settings.message}</p>
    </div>
    <button class="notification-close">&times;</button>
  `;
  
  // Agregar al contenedor
  notificationConfig.container.appendChild(notification);
  
  // Mostrar con animación
  setTimeout(() => notification.classList.add('show'), 10);
  
  // Configurar cierre
  const closeButton = notification.querySelector('.notification-close');
  closeButton.addEventListener('click', () => {
    closeNotification(notification);
  });
  
  // Auto-cerrar después de la duración
  if (settings.duration > 0) {
    setTimeout(() => {
      closeNotification(notification);
    }, settings.duration);
  }
  
  return notification;
}

/**
 * Cierra una notificación
 */
function closeNotification(notification) {
  notification.classList.remove('show');
  
  // Eliminar después de la animación
  setTimeout(() => {
    if (notification.parentNode) {
      notification.parentNode.removeChild(notification);
    }
  }, 300);
}

/**
 * Devuelve un título predeterminado según el tipo de notificación
 */
function getDefaultTitle(type) {
  switch (type) {
    case 'success': return 'Éxito';
    case 'error': return 'Error';
    case 'warning': return 'Advertencia';
    case 'info':
    default:
      return 'Información';
  }
}

/**
 * Devuelve el icono según el tipo de notificación
 */
function getIconForType(type) {
  switch (type) {
    case 'success': return 'fas fa-check-circle';
    case 'error': return 'fas fa-exclamation-circle';
    case 'warning': return 'fas fa-exclamation-triangle';
    case 'info':
    default:
      return 'fas fa-info-circle';
  }
}

/**
 * Muestra indicador de búsqueda
 */
function showSearchIndicator(tableContainer) {
  const container = tableContainer || document.querySelector('.table-responsive') || document.querySelector('.table-container');
  
  if (!container) return false;
  
  let indicator = container.querySelector('.search-indicator');
  
  if (!indicator) {
    indicator = document.createElement('div');
    indicator.className = 'search-indicator';
    indicator.innerHTML = `
      <i class="fas fa-sync-alt search-indicator-icon"></i>
      <span class="search-indicator-text">Aplicando filtros de búsqueda...</span>
    `;
    container.prepend(indicator);
  }
  
  indicator.classList.add('active');
  return true;
}

/**
 * Oculta indicador de búsqueda
 */
function hideSearchIndicator() {
  const indicators = document.querySelectorAll('.search-indicator');
  indicators.forEach(indicator => {
    indicator.classList.remove('active');
  });
}

// Auto-inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', initNotificationSystem);

// Exponer las funciones globalmente
window.showNotification = showNotification;
window.showSearchIndicator = showSearchIndicator;
window.hideSearchIndicator = hideSearchIndicator;
