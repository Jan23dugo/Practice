<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<?php
// This file contains the error modal that is shown after failed registration

// Check if this file is being accessed directly
if (!defined('INCLUDE_MODAL')) {
    exit('Direct access not permitted');
}
?>

<!-- Registration Error Modal -->
<div id="registrationErrorModal" class="modal">
    <div class="modal-content error-modal">
        <span class="close-modal" onclick="closeErrorModal()">&times;</span>
        
        <div class="modal-header">
            <div class="error-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <h2>Registration Error</h2>
        </div>
        
        <div class="modal-body">
            <?php if (isset($_SESSION['ocr_error'])): ?>
                <div class="error-message">
                    <p><i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($_SESSION['ocr_error']); ?></p>
                </div>
            <?php elseif (isset($_SESSION['last_error'])): ?>
                <div class="error-message">
                    <p><i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($_SESSION['last_error']); ?></p>
                </div>
            <?php else: ?>
                <div class="error-message" id="js-error-message">
                    <p><i class="fas fa-info-circle"></i> An unexpected error occurred during the registration process.</p>
                </div>
            <?php endif; ?>
            
            <div class="troubleshooting">
                <h3><i class="fas fa-tools"></i> Please check the following:</h3>
                <ul>
                    <li><i class="fas fa-check-circle"></i> Ensure all required fields are filled out correctly</li>
                    <li><i class="fas fa-file-alt"></i> Verify that your document uploads are valid and readable</li>
                    <li><i class="fas fa-graduation-cap"></i> Check that your transcript contains complete grade information</li>
                    <li><i class="fas fa-wifi"></i> Make sure you have a stable internet connection</li>
                </ul>
            </div>
            
            <div class="support-info">
                <p><i class="fas fa-headset"></i> If the problem persists, please contact our support team for assistance.</p>
            </div>
        </div>
        
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="tryAgain()">
                <i class="fas fa-arrow-left"></i> Try Again
            </button>
            <button class="btn btn-primary" onclick="window.location.href='stud_dashboard.php'">
                <i class="fas fa-home"></i> Return to Dashboard
            </button>
        </div>
    </div>
</div>

<!-- Add CSS styles for the error modal -->
<style>
    #registrationErrorModal {
        display: none;
        position: fixed;
        z-index: 10000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.7);
        overflow-y: auto; /* Enable scrolling for the entire modal */
        animation: fadeIn 0.4s ease;
    }
    
    .error-modal {
        background-color: white;
        margin: 5% auto;
        width: 90%;
        max-width: 650px;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        animation: slideDown 0.5s ease;
        overflow: hidden;
        padding: 0;
        transform: translateY(0);
        transition: transform 0.3s ease;
        max-height: 90vh; /* Maximum height relative to viewport */
        display: flex;
        flex-direction: column;
    }
    
    .error-modal .modal-header {
        background: linear-gradient(135deg, #d32f2f 0%, #8b0000 100%);
        color: white;
        padding: 25px 20px;
        text-align: center;
        position: relative;
        flex: 0 0 auto; /* Don't shrink header */
    }
    
    .error-icon {
        background-color: white;
        width: 90px;
        height: 90px;
        border-radius: 50%;
        margin: 0 auto 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        animation: shake 1.5s ease 0.5s;
    }
    
    .error-icon i {
        font-size: 50px;
        color: #d32f2f;
    }
    
    .error-modal .modal-header h2 {
        margin: 0;
        font-size: 2rem;
        font-weight: 600;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .error-modal .modal-body {
        padding: 35px;
        color: var(--text-dark);
        font-size: 1.05rem;
        line-height: 1.6;
        overflow-y: auto; /* Add scroll to the body when needed */
        flex: 1 1 auto; /* Allow body to grow and shrink as needed */
        max-height: calc(90vh - 200px); /* Adjust based on header/footer size */
    }
    
    .error-message {
        background-color: #ffebee;
        padding: 20px;
        border-radius: 10px;
        text-align: center;
        font-size: 1.1rem;
        margin-bottom: 25px;
        border-left: 5px solid #d32f2f;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
    }
    
    .error-message i {
        color: #d32f2f;
        margin-right: 8px;
    }
    
    .troubleshooting {
        background-color: #f5f5f5;
        padding: 22px;
        border-radius: 10px;
        margin-top: 25px;
        border-left: 4px solid #2196f3;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
    }
    
    .troubleshooting h3 {
        margin-top: 0;
        color: #0d47a1;
        font-size: 1.3rem;
        display: flex;
        align-items: center;
        margin-bottom: 15px;
    }
    
    .troubleshooting h3 i {
        margin-right: 10px;
        color: #2196f3;
    }
    
    .troubleshooting ul {
        margin-bottom: 0;
        padding-left: 20px;
        list-style-type: none;
    }
    
    .troubleshooting li {
        margin-bottom: 12px;
        position: relative;
        display: flex;
        align-items: flex-start;
    }
    
    .troubleshooting li:last-child {
        margin-bottom: 0;
    }
    
    .troubleshooting li i {
        color: #2196f3;
        margin-right: 10px;
        min-width: 16px;
    }
    
    .support-info {
        background-color: #e8f5e9;
        border-left: 4px solid #4caf50;
        padding: 18px;
        border-radius: 10px;
        margin-top: 25px;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        text-align: center;
    }
    
    .support-info i {
        color: #4caf50;
        margin-right: 8px;
    }
    
    .error-modal .modal-footer {
        padding: 25px;
        text-align: center;
        border-top: 1px solid #eee;
        background-color: #fafafa;
        display: flex;
        justify-content: center;
        gap: 20px;
        flex: 0 0 auto; /* Don't shrink footer */
    }
    
    .error-modal .btn {
        padding: 12px 28px;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        border-radius: 50px;
        min-width: 180px;
    }
    
    .btn-secondary {
        background-color: #f5f5f5;
        color: #333;
        border: 1px solid #ddd;
    }
    
    .btn-secondary:hover {
        background-color: #e0e0e0;
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .error-modal .btn-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .error-modal .close-modal {
        position: absolute;
        top: 15px;
        right: 20px;
        font-size: 28px;
        color: white;
        opacity: 0.8;
        cursor: pointer;
        transition: all 0.2s;
        z-index: 1; /* Ensure it's above other elements */
    }
    
    .error-modal .close-modal:hover {
        opacity: 1;
        transform: rotate(90deg);
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes slideDown {
        from { transform: translateY(-70px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }
    
    @media (max-width: 768px) {
        .error-modal {
            margin: 20px auto;
            width: 95%;
            max-height: 85vh; /* Slightly smaller on mobile */
        }
        
        .error-modal .modal-body {
            padding: 25px 20px;
            max-height: calc(85vh - 250px); /* Adjust for mobile header/footer */
        }
        
        .error-modal .modal-header h2 {
            font-size: 1.7rem;
        }
        
        .error-icon {
            width: 70px;
            height: 70px;
        }
        
        .error-icon i {
            font-size: 40px;
        }
        
        .error-modal .modal-footer {
            flex-direction: column;
            gap: 15px;
        }
        
        .error-modal .btn {
            width: 100%;
        }
    }
    
    /* Fix for iOS Safari scrolling issues */
    @supports (-webkit-touch-callout: none) {
        .error-modal .modal-body {
            -webkit-overflow-scrolling: touch;
        }
    }
</style>

<!-- Add JavaScript for the error modal -->
<script>
    // Function to show the error modal
    function showErrorModal() {
        const modal = document.getElementById('registrationErrorModal');
        modal.style.display = 'block';
        document.body.classList.add('modal-open');
        
        // Check for error message in sessionStorage (added by JavaScript)
        const sessionError = sessionStorage.getItem('registration_error');
        if (sessionError) {
            const errorMessageEl = document.getElementById('js-error-message');
            if (errorMessageEl && errorMessageEl.querySelector('p')) {
                errorMessageEl.querySelector('p').innerHTML = '<i class="fas fa-info-circle"></i> ' + sessionError;
            }
            // Clear the error message from sessionStorage
            sessionStorage.removeItem('registration_error');
        }
        
        // Add entrance animations for key elements with slight delays
        setTimeout(() => {
            const errorMessage = document.querySelector('.error-message');
            if (errorMessage) {
                errorMessage.style.animation = 'fadeIn 0.5s ease forwards';
            }
        }, 500);
        
        setTimeout(() => {
            const troubleshooting = document.querySelector('.troubleshooting');
            if (troubleshooting) {
                troubleshooting.style.animation = 'fadeIn 0.5s ease forwards';
            }
        }, 700);
        
        setTimeout(() => {
            const supportInfo = document.querySelector('.support-info');
            if (supportInfo) {
                supportInfo.style.animation = 'fadeIn 0.5s ease forwards';
            }
        }, 900);
    }
    
    // Function to close the error modal
    function closeErrorModal() {
        const modal = document.getElementById('registrationErrorModal');
        
        // Add exit animation
        const modalContent = document.querySelector('.error-modal');
        if (modalContent) {
            modalContent.style.transform = 'translateY(20px)';
            modalContent.style.opacity = '0';
        }
        
        // Hide after animation completes
        setTimeout(() => {
            modal.style.display = 'none';
            document.body.classList.remove('modal-open');
            
            // Reset transformations for next time
            if (modalContent) {
                modalContent.style.transform = '';
                modalContent.style.opacity = '';
            }
        }, 300);
    }
    
    // Close modal when clicking outside of it
    document.addEventListener('DOMContentLoaded', function() {
        const errorModal = document.getElementById('registrationErrorModal');
        if (errorModal) {
            errorModal.addEventListener('click', function(event) {
                if (event.target === errorModal) {
                    closeErrorModal();
                }
            });
        }
    });
    
    <?php if (isset($_SESSION['show_error_modal']) && $_SESSION['show_error_modal']): ?>
    // Show the modal automatically if the session flag is set
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(showErrorModal, 200); // Small delay for better user experience
        <?php unset($_SESSION['show_error_modal']); ?>
    });
    <?php endif; ?>

    // Option 2: Try Again closes modal, resets form, and scrolls to it
    function tryAgain() {
        // Reload the page for a guaranteed clean state
        window.location.reload();
    }
=======
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
<?php
// This file contains the error modal that is shown after failed registration

// Check if this file is being accessed directly
if (!defined('INCLUDE_MODAL')) {
    exit('Direct access not permitted');
}
?>

<!-- Registration Error Modal -->
<div id="registrationErrorModal" class="modal">
    <div class="modal-content error-modal">
        <span class="close-modal" onclick="closeErrorModal()">&times;</span>
        
        <div class="modal-header">
            <div class="error-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <h2>Registration Error</h2>
        </div>
        
        <div class="modal-body">
            <?php if (isset($_SESSION['ocr_error'])): ?>
                <div class="error-message">
                    <p><i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($_SESSION['ocr_error']); ?></p>
                </div>
            <?php elseif (isset($_SESSION['last_error'])): ?>
                <div class="error-message">
                    <p><i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($_SESSION['last_error']); ?></p>
                </div>
            <?php else: ?>
                <div class="error-message" id="js-error-message">
                    <p><i class="fas fa-info-circle"></i> An unexpected error occurred during the registration process.</p>
                </div>
            <?php endif; ?>
            
            <div class="troubleshooting">
                <h3><i class="fas fa-tools"></i> Please check the following:</h3>
                <ul>
                    <li><i class="fas fa-check-circle"></i> Ensure all required fields are filled out correctly</li>
                    <li><i class="fas fa-file-alt"></i> Verify that your document uploads are valid and readable</li>
                    <li><i class="fas fa-graduation-cap"></i> Check that your transcript contains complete grade information</li>
                    <li><i class="fas fa-wifi"></i> Make sure you have a stable internet connection</li>
                </ul>
            </div>
            
            <div class="support-info">
                <p><i class="fas fa-headset"></i> If the problem persists, please contact our support team for assistance.</p>
            </div>
        </div>
        
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="tryAgain()">
                <i class="fas fa-arrow-left"></i> Try Again
            </button>
            <button class="btn btn-primary" onclick="window.location.href='stud_dashboard.php'">
                <i class="fas fa-home"></i> Return to Dashboard
            </button>
        </div>
    </div>
</div>

<!-- Add CSS styles for the error modal -->
<style>
    #registrationErrorModal {
        display: none;
        position: fixed;
        z-index: 10000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.7);
        overflow-y: auto; /* Enable scrolling for the entire modal */
        animation: fadeIn 0.4s ease;
    }
    
    .error-modal {
        background-color: white;
        margin: 5% auto;
        width: 90%;
        max-width: 650px;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        animation: slideDown 0.5s ease;
        overflow: hidden;
        padding: 0;
        transform: translateY(0);
        transition: transform 0.3s ease;
        max-height: 90vh; /* Maximum height relative to viewport */
        display: flex;
        flex-direction: column;
    }
    
    .error-modal .modal-header {
        background: linear-gradient(135deg, #d32f2f 0%, #8b0000 100%);
        color: white;
        padding: 25px 20px;
        text-align: center;
        position: relative;
        flex: 0 0 auto; /* Don't shrink header */
    }
    
    .error-icon {
        background-color: white;
        width: 90px;
        height: 90px;
        border-radius: 50%;
        margin: 0 auto 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        animation: shake 1.5s ease 0.5s;
    }
    
    .error-icon i {
        font-size: 50px;
        color: #d32f2f;
    }
    
    .error-modal .modal-header h2 {
        margin: 0;
        font-size: 2rem;
        font-weight: 600;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .error-modal .modal-body {
        padding: 35px;
        color: var(--text-dark);
        font-size: 1.05rem;
        line-height: 1.6;
        overflow-y: auto; /* Add scroll to the body when needed */
        flex: 1 1 auto; /* Allow body to grow and shrink as needed */
        max-height: calc(90vh - 200px); /* Adjust based on header/footer size */
    }
    
    .error-message {
        background-color: #ffebee;
        padding: 20px;
        border-radius: 10px;
        text-align: center;
        font-size: 1.1rem;
        margin-bottom: 25px;
        border-left: 5px solid #d32f2f;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
    }
    
    .error-message i {
        color: #d32f2f;
        margin-right: 8px;
    }
    
    .troubleshooting {
        background-color: #f5f5f5;
        padding: 22px;
        border-radius: 10px;
        margin-top: 25px;
        border-left: 4px solid #2196f3;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
    }
    
    .troubleshooting h3 {
        margin-top: 0;
        color: #0d47a1;
        font-size: 1.3rem;
        display: flex;
        align-items: center;
        margin-bottom: 15px;
    }
    
    .troubleshooting h3 i {
        margin-right: 10px;
        color: #2196f3;
    }
    
    .troubleshooting ul {
        margin-bottom: 0;
        padding-left: 20px;
        list-style-type: none;
    }
    
    .troubleshooting li {
        margin-bottom: 12px;
        position: relative;
        display: flex;
        align-items: flex-start;
    }
    
    .troubleshooting li:last-child {
        margin-bottom: 0;
    }
    
    .troubleshooting li i {
        color: #2196f3;
        margin-right: 10px;
        min-width: 16px;
    }
    
    .support-info {
        background-color: #e8f5e9;
        border-left: 4px solid #4caf50;
        padding: 18px;
        border-radius: 10px;
        margin-top: 25px;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        text-align: center;
    }
    
    .support-info i {
        color: #4caf50;
        margin-right: 8px;
    }
    
    .error-modal .modal-footer {
        padding: 25px;
        text-align: center;
        border-top: 1px solid #eee;
        background-color: #fafafa;
        display: flex;
        justify-content: center;
        gap: 20px;
        flex: 0 0 auto; /* Don't shrink footer */
    }
    
    .error-modal .btn {
        padding: 12px 28px;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        border-radius: 50px;
        min-width: 180px;
    }
    
    .btn-secondary {
        background-color: #f5f5f5;
        color: #333;
        border: 1px solid #ddd;
    }
    
    .btn-secondary:hover {
        background-color: #e0e0e0;
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .error-modal .btn-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .error-modal .close-modal {
        position: absolute;
        top: 15px;
        right: 20px;
        font-size: 28px;
        color: white;
        opacity: 0.8;
        cursor: pointer;
        transition: all 0.2s;
        z-index: 1; /* Ensure it's above other elements */
    }
    
    .error-modal .close-modal:hover {
        opacity: 1;
        transform: rotate(90deg);
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes slideDown {
        from { transform: translateY(-70px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }
    
    @media (max-width: 768px) {
        .error-modal {
            margin: 20px auto;
            width: 95%;
            max-height: 85vh; /* Slightly smaller on mobile */
        }
        
        .error-modal .modal-body {
            padding: 25px 20px;
            max-height: calc(85vh - 250px); /* Adjust for mobile header/footer */
        }
        
        .error-modal .modal-header h2 {
            font-size: 1.7rem;
        }
        
        .error-icon {
            width: 70px;
            height: 70px;
        }
        
        .error-icon i {
            font-size: 40px;
        }
        
        .error-modal .modal-footer {
            flex-direction: column;
            gap: 15px;
        }
        
        .error-modal .btn {
            width: 100%;
        }
    }
    
    /* Fix for iOS Safari scrolling issues */
    @supports (-webkit-touch-callout: none) {
        .error-modal .modal-body {
            -webkit-overflow-scrolling: touch;
        }
    }
</style>

<!-- Add JavaScript for the error modal -->
<script>
    // Function to show the error modal
    function showErrorModal() {
        const modal = document.getElementById('registrationErrorModal');
        modal.style.display = 'block';
        document.body.classList.add('modal-open');
        
        // Check for error message in sessionStorage (added by JavaScript)
        const sessionError = sessionStorage.getItem('registration_error');
        if (sessionError) {
            const errorMessageEl = document.getElementById('js-error-message');
            if (errorMessageEl && errorMessageEl.querySelector('p')) {
                errorMessageEl.querySelector('p').innerHTML = '<i class="fas fa-info-circle"></i> ' + sessionError;
            }
            // Clear the error message from sessionStorage
            sessionStorage.removeItem('registration_error');
        }
        
        // Add entrance animations for key elements with slight delays
        setTimeout(() => {
            const errorMessage = document.querySelector('.error-message');
            if (errorMessage) {
                errorMessage.style.animation = 'fadeIn 0.5s ease forwards';
            }
        }, 500);
        
        setTimeout(() => {
            const troubleshooting = document.querySelector('.troubleshooting');
            if (troubleshooting) {
                troubleshooting.style.animation = 'fadeIn 0.5s ease forwards';
            }
        }, 700);
        
        setTimeout(() => {
            const supportInfo = document.querySelector('.support-info');
            if (supportInfo) {
                supportInfo.style.animation = 'fadeIn 0.5s ease forwards';
            }
        }, 900);
    }
    
    // Function to close the error modal
    function closeErrorModal() {
        const modal = document.getElementById('registrationErrorModal');
        
        // Add exit animation
        const modalContent = document.querySelector('.error-modal');
        if (modalContent) {
            modalContent.style.transform = 'translateY(20px)';
            modalContent.style.opacity = '0';
        }
        
        // Hide after animation completes
        setTimeout(() => {
            modal.style.display = 'none';
            document.body.classList.remove('modal-open');
            
            // Reset transformations for next time
            if (modalContent) {
                modalContent.style.transform = '';
                modalContent.style.opacity = '';
            }
        }, 300);
    }
    
    // Close modal when clicking outside of it
    document.addEventListener('DOMContentLoaded', function() {
        const errorModal = document.getElementById('registrationErrorModal');
        if (errorModal) {
            errorModal.addEventListener('click', function(event) {
                if (event.target === errorModal) {
                    closeErrorModal();
                }
            });
        }
    });
    
    <?php if (isset($_SESSION['show_error_modal']) && $_SESSION['show_error_modal']): ?>
    // Show the modal automatically if the session flag is set
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(showErrorModal, 200); // Small delay for better user experience
        <?php unset($_SESSION['show_error_modal']); ?>
    });
    <?php endif; ?>

    // Option 2: Try Again closes modal, resets form, and scrolls to it
    function tryAgain() {
        // Reload the page for a guaranteed clean state
        window.location.reload();
    }
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
</script> 