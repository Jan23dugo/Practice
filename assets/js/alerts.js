/**
 * Alert Modal System for the application
 */
class AlertSystem {
    constructor() {
        this.createModalContainer();
        this.setupEventListeners();
    }
    
    createModalContainer() {
        // Check if the container already exists
        if (document.getElementById('alert-modal-container')) {
            return;
        }
        
        // Create modal container
        const container = document.createElement('div');
        container.id = 'alert-modal-container';
        container.style.display = 'none';
        container.innerHTML = `
            <div class="alert-modal-overlay">
                <div class="alert-modal">
                    <div class="alert-modal-header">
                        <h4 class="alert-title"></h4>
                        <button type="button" class="close-alert">&times;</button>
                    </div>
                    <div class="alert-modal-body">
                        <p class="alert-message"></p>
                    </div>
                    <div class="alert-modal-footer">
                        <button type="button" class="btn-confirm">OK</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(container);
        
        // Add styles
        const style = document.createElement('style');
        style.textContent = `
            .alert-modal-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 1050;
            }
            
            .alert-modal {
                background-color: #fff;
                border-radius: 8px;
                width: 100%;
                max-width: 450px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
                animation: alertModalFadeIn 0.3s ease;
            }
            
            @keyframes alertModalFadeIn {
                from { opacity: 0; transform: translateY(-20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            .alert-modal-header {
                padding: 15px 20px;
                border-bottom: 1px solid #e0e0e0;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            
            .alert-title {
                margin: 0;
                font-size: 18px;
                font-weight: 500;
            }
            
            .close-alert {
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                color: #666;
            }
            
            .alert-modal-body {
                padding: 20px;
            }
            
            .alert-message {
                margin: 0;
                font-size: 16px;
                line-height: 1.5;
            }
            
            .alert-modal-footer {
                padding: 15px 20px;
                border-top: 1px solid #e0e0e0;
                display: flex;
                justify-content: flex-end;
            }
            
            .btn-confirm {
                padding: 8px 20px;
                border-radius: 4px;
                background-color: #8e68cc;
                color: white;
                border: none;
                font-size: 14px;
                cursor: pointer;
                transition: background-color 0.2s;
            }
            
            .btn-confirm:hover {
                background-color: #7d5bb9;
            }
            
            .alert-success .alert-title {
                color: #155724;
            }
            
            .alert-danger .alert-title {
                color: #721c24;
            }
            
            .alert-warning .alert-title {
                color: #856404;
            }
            
            .alert-info .alert-title {
                color: #0c5460;
            }
        `;
        document.head.appendChild(style);
    }
    
    setupEventListeners() {
        document.addEventListener('click', (e) => {
            if (e.target.matches('.close-alert') || 
                e.target.matches('.btn-confirm') || 
                e.target.matches('.alert-modal-overlay')) {
                this.hideAlert();
            }
        });
    }
    
    /**
     * Show an alert with the given type, title and message
     * @param {string} type - success, error, warning, info
     * @param {string} title - The alert title
     * @param {string} message - The alert message
     */
    showAlert(type, title, message) {
        const container = document.getElementById('alert-modal-container');
        const modal = container.querySelector('.alert-modal');
        const titleEl = container.querySelector('.alert-title');
        const messageEl = container.querySelector('.alert-message');
        
        // Remove any existing type classes
        modal.classList.remove('alert-success', 'alert-danger', 'alert-warning', 'alert-info');
        
        // Map type to class
        const typeClasses = {
            'success': 'alert-success',
            'error': 'alert-danger',
            'warning': 'alert-warning',
            'info': 'alert-info'
        };
        
        // Add the appropriate class
        if (typeClasses[type]) {
            modal.classList.add(typeClasses[type]);
        }
        
        // Set title and message
        titleEl.textContent = title;
        messageEl.textContent = message;
        
        // Show the alert
        container.style.display = 'block';
        
        // Prevent background scrolling
        document.body.style.overflow = 'hidden';
        
        // Return promise that resolves when alert is closed
        return new Promise(resolve => {
            this.resolvePromise = resolve;
        });
    }
    
    hideAlert() {
        const container = document.getElementById('alert-modal-container');
        container.style.display = 'none';
        
        // Re-enable scrolling
        document.body.style.overflow = '';
        
        // Resolve the promise if it exists
        if (this.resolvePromise) {
            this.resolvePromise();
            this.resolvePromise = null;
        }
    }
    
    /**
     * Convenience method for success alerts
     */
    success(title, message) {
        return this.showAlert('success', title, message);
    }
    
    /**
     * Convenience method for error alerts
     */
    error(title, message) {
        return this.showAlert('error', title, message);
    }
    
    /**
     * Convenience method for warning alerts
     */
    warning(title, message) {
        return this.showAlert('warning', title, message);
    }
    
    /**
     * Convenience method for info alerts
     */
    info(title, message) {
        return this.showAlert('info', title, message);
    }
    
    /**
     * Ask for confirmation with yes/no buttons
     */
    confirm(title, message) {
        const container = document.getElementById('alert-modal-container');
        const modal = container.querySelector('.alert-modal');
        const titleEl = container.querySelector('.alert-title');
        const messageEl = container.querySelector('.alert-message');
        const footer = container.querySelector('.alert-modal-footer');
        
        // Store original footer content
        const originalFooter = footer.innerHTML;
        
        // Remove any existing type classes
        modal.classList.remove('alert-success', 'alert-danger', 'alert-warning', 'alert-info');
        modal.classList.add('alert-warning');
        
        // Set title and message
        titleEl.textContent = title;
        messageEl.textContent = message;
        
        // Replace footer with confirm/cancel buttons
        footer.innerHTML = `
            <button type="button" class="btn-cancel" style="margin-right: 10px; padding: 8px 20px; border-radius: 4px; background-color: #f0f0f0; color: #333; border: none;">Cancel</button>
            <button type="button" class="btn-confirm-yes" style="padding: 8px 20px; border-radius: 4px; background-color: #8e68cc; color: white; border: none;">Yes</button>
        `;
        
        // Show the alert
        container.style.display = 'block';
        
        // Prevent background scrolling
        document.body.style.overflow = 'hidden';
        
        // Return promise that resolves with true or false
        return new Promise(resolve => {
            // Handle yes button
            footer.querySelector('.btn-confirm-yes').addEventListener('click', () => {
                footer.innerHTML = originalFooter;
                this.hideAlert();
                resolve(true);
            });
            
            // Handle cancel button and other closing methods
            footer.querySelector('.btn-cancel').addEventListener('click', () => {
                footer.innerHTML = originalFooter;
                this.hideAlert();
                resolve(false);
            });
            
            // Handle close button and background click
            const closeHandler = () => {
                footer.innerHTML = originalFooter;
                resolve(false);
            };
            
            document.querySelector('.close-alert').addEventListener('click', closeHandler);
            document.querySelector('.alert-modal-overlay').addEventListener('click', (e) => {
                if (e.target === document.querySelector('.alert-modal-overlay')) {
                    footer.innerHTML = originalFooter;
                    this.hideAlert();
                    resolve(false);
                }
            });
        });
    }
}

// Create a global instance
const alerts = new AlertSystem();
