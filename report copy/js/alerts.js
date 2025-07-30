// Alert types configuration
const AlertTypes = {
    SUCCESS: 'success',
    ERROR: 'error',
    WARNING: 'warning',
    INFO: 'info'
};

const AlertIcons = {
    [AlertTypes.SUCCESS]: 'fa-circle-check',
    [AlertTypes.ERROR]: 'fa-circle-xmark',
    [AlertTypes.WARNING]: 'fa-triangle-exclamation',
    [AlertTypes.INFO]: 'fa-circle-info'
};

const AlertColors = {
    [AlertTypes.SUCCESS]: '#28a745',
    [AlertTypes.ERROR]: '#dc3545',
    [AlertTypes.WARNING]: '#ffc107',
    [AlertTypes.INFO]: '#17a2b8'
};

/**
 * Display an alert message
 * @param {string} message - The message to display
 * @param {string} type - Alert type (success, error, warning, info)
 * @param {number} duration - Duration in milliseconds before alert disappears
 * @param {string} position - Position of the alert (center, top-right, top-left, bottom-right, bottom-left)
 */
function displayAlert(message, type = AlertTypes.INFO, duration = 3000, position = 'center') {
    let alertContainer = document.getElementById('alert-container');
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.id = 'alert-container';
        document.body.appendChild(alertContainer);
    }

    const alertElement = document.createElement('div');
    alertElement.className = 'custom-alert';
    alertElement.classList.add(`alert-${position}`);

    alertElement.innerHTML = `
        <div class="alert-icon">
            <i class="fas ${AlertIcons[type]}"></i>
        </div>
        <div class="alert-message">${message}</div>
        <div class="alert-close">
            <i class="fas fa-times"></i>
        </div>
    `;

    // Different initial transform based on position
    const initialTransform = position === 'center' ? 'translateY(-20px)' : 'translateX(100%)';
    const finalTransform = 'translate(0, 0)';

    alertElement.style.cssText = `
        display: flex;
        align-items: center;
        padding: 12px 20px;
        background: white;
        border-left: 4px solid ${AlertColors[type]};
        border-radius: 4px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        margin: 10px;
        max-width: 400px;
        opacity: 0;
        transform: ${initialTransform};
        transition: all 0.3s ease;
    `;

    if (!document.getElementById('alert-container-styles')) {
        const styleSheet = document.createElement('style');
        styleSheet.id = 'alert-container-styles';
        styleSheet.textContent = `
            #alert-container {
                position: fixed;
                z-index: 9999;
                pointer-events: none;
            }
            #alert-container .custom-alert {
                pointer-events: auto;
            }
            .alert-center {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%) !important;
            }
            .alert-top-right {
                top: 20px;
                right: 20px;
            }
            .alert-top-left {
                top: 20px;
                left: 20px;
            }
            .alert-bottom-right {
                bottom: 20px;
                right: 20px;
            }
            .alert-bottom-left {
                bottom: 20px;
                left: 20px;
            }
            .alert-icon {
                margin-right: 12px;
                color: ${AlertColors[type]};
            }
            .alert-message {
                flex-grow: 1;
                color: #333;
                font-size: 14px;
            }
            .alert-close {
                margin-left: 12px;
                cursor: pointer;
                color: #999;
            }
            .alert-close:hover {
                color: #666;
            }
        `;
        document.head.appendChild(styleSheet);
    }

    alertContainer.appendChild(alertElement);

    // For center position, we need different animation
    if (position === 'center') {
        alertElement.style.transform = 'translate(-50%, -50%)';
    }

    setTimeout(() => {
        alertElement.style.opacity = '1';
        if (position !== 'center') {
            alertElement.style.transform = finalTransform;
        }
    }, 50);

    const closeButton = alertElement.querySelector('.alert-close');
    closeButton.addEventListener('click', () => {
        removeAlert(alertElement, position);
    });

    if (duration > 0) {
        setTimeout(() => {
            removeAlert(alertElement, position);
        }, duration);
    }
}

/**
 * Remove alert with animation
 * @param {HTMLElement} alertElement - The alert element to remove
 * @param {string} position - Position of the alert
 */
function removeAlert(alertElement, position) {
    alertElement.style.opacity = '0';
    if (position !== 'center') {
        alertElement.style.transform = 'translateX(100%)';
    } else {
        alertElement.style.transform = 'translate(-50%, -60%)';
    }
    setTimeout(() => {
        alertElement.remove();
    }, 300);
}

// Convenience methods
const showSuccess = (message, duration, position = 'center') =>
    displayAlert(message, AlertTypes.SUCCESS, duration, position);

const showError = (message, duration, position = 'center') =>
    displayAlert(message, AlertTypes.ERROR, duration, position);

const showWarning = (message, duration, position = 'center') =>
    displayAlert(message, AlertTypes.WARNING, duration, position);

const showInfo = (message, duration, position = 'center') =>
    displayAlert(message, AlertTypes.INFO, duration, position);

// Usage examples:
// showSuccess('Operation completed successfully!');
// showError('An error occurred!', 5000, 'center');
// showWarning('Please backup your data.', 3000, 'top-right');
// showInfo('Your session will expire soon.', 4000, 'center');