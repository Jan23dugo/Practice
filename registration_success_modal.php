<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<?php
// This file contains the success modal that is shown after successful registration

// Check if this file is being accessed directly
if (!defined('INCLUDE_MODAL')) {
    exit('Direct access not permitted');
}
?>

<!-- Registration Success Modal -->
<div id="registrationSuccessModal" class="modal">
    <div class="modal-content success-modal">
        <span class="close-modal" onclick="closeSuccessModal()">&times;</span>
        
        <div class="modal-header">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2>Registration Successful!</h2>
        </div>
        
        <div class="modal-body">
            <?php if (isset($_SESSION['reference_id'])): ?>
                <p class="reference-id"><span>Your Reference ID:</span> <strong><?php echo htmlspecialchars($_SESSION['reference_id']); ?></strong></p>
            <?php endif; ?>
            
            <div class="success-message">
                <p>Your application has been submitted successfully.</p>
                
                <?php if (isset($_SESSION['registration_status']) && $_SESSION['registration_status'] === 'needs_review'): ?>
                    <div class="review-notice">
                        <p><i class="fas fa-info-circle"></i> Your application requires manual review by an administrator.</p>
                        <p>This is usually due to your institution's grading system not being registered in our database.</p>
                    </div>
                <?php else: ?>
                    <div class="status-info">
                        <p><i class="fas fa-clipboard-check"></i> Your application will be reviewed by our admin team.</p>
                        <p><i class="fas fa-calendar-alt"></i> Please allow 2-3 business days for verification.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="next-steps">
                <h3><i class="fas fa-tasks"></i> Next Steps:</h3>
                <ul>
                    <li>Keep your Reference ID for future inquiries</li>
                    <li>Check your email for confirmation</li>
                    <li>Track your application status on the dashboard</li>
                </ul>
            </div>
        </div>
        
        <div class="modal-footer">
            <button class="btn btn-primary" onclick="window.location.href='stud_dashboard.php'">
                <i class="fas fa-home"></i> Return to Dashboard
            </button>
        </div>
    </div>
</div>

<!-- Add CSS styles for the success modal -->
<style>
    #registrationSuccessModal {
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
    
    .success-modal {
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
    
    .modal-header {
        background: linear-gradient(135deg, var(--primary) 0%, #3e1c23 100%);
        color: white;
        padding: 25px 20px;
        text-align: center;
        position: relative;
        flex: 0 0 auto; /* Don't shrink header */
    }
    
    .success-icon {
        background-color: white;
        width: 90px;
        height: 90px;
        border-radius: 50%;
        margin: 0 auto 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        animation: pulse 2s infinite;
    }
    
    .success-icon i {
        font-size: 50px;
        color: var(--primary);
    }
    
    .modal-header h2 {
        margin: 0;
        font-size: 2rem;
        font-weight: 600;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .modal-body {
        padding: 35px;
        color: var(--text-dark);
        font-size: 1.05rem;
        line-height: 1.6;
        overflow-y: auto; /* Add scroll to the body when needed */
        flex: 1 1 auto; /* Allow body to grow and shrink as needed */
        max-height: calc(90vh - 200px); /* Adjust based on header/footer size */
    }
    
    .reference-id {
        background-color: #f5f5f5;
        padding: 18px;
        border-radius: 10px;
        text-align: center;
        font-size: 1.3rem;
        margin-bottom: 25px;
        border-left: 5px solid var(--primary);
        display: flex;
        flex-direction: column;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        transition: transform 0.3s ease;
    }
    
    .reference-id:hover {
        transform: translateY(-3px);
    }
    
    .reference-id span {
        font-size: 0.9rem;
        color: #666;
        margin-bottom: 5px;
    }
    
    .reference-id strong {
        color: var(--primary);
        letter-spacing: 1px;
    }
    
    .success-message {
        margin-bottom: 25px;
        line-height: 1.7;
    }
    
    .review-notice {
        background-color: #fff8e1;
        border-left: 4px solid #ffc107;
        padding: 18px;
        border-radius: 10px;
        margin: 20px 0;
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.05);
    }
    
    .review-notice i, 
    .status-info i {
        color: #ffc107;
        margin-right: 8px;
    }
    
    .status-info {
        background-color: #e3f2fd;
        border-left: 4px solid #2196f3;
        padding: 18px;
        border-radius: 10px;
        margin: 20px 0;
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.05);
    }
    
    .status-info i {
        color: #2196f3;
    }
    
    .next-steps {
        background-color: #f8f9fa;
        padding: 22px;
        border-radius: 10px;
        margin-top: 25px;
        border-left: 4px solid #4caf50;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
    }
    
    .next-steps h3 {
        margin-top: 0;
        color: #2e7d32;
        font-size: 1.3rem;
        display: flex;
        align-items: center;
        margin-bottom: 15px;
    }
    
    .next-steps h3 i {
        margin-right: 10px;
    }
    
    .next-steps ul {
        margin-bottom: 0;
        padding-left: 30px;
    }
    
    .next-steps li {
        margin-bottom: 10px;
        position: relative;
    }
    
    .next-steps li:last-child {
        margin-bottom: 0;
    }
    
    .modal-footer {
        padding: 25px;
        text-align: center;
        border-top: 1px solid #eee;
        background-color: #fafafa;
        flex: 0 0 auto; /* Don't shrink footer */
    }
    
    .modal-footer .btn {
        padding: 12px 28px;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        border-radius: 50px;
    }
    
    .modal-footer .btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .close-modal {
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
    
    .close-modal:hover {
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
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    
    @media (max-width: 768px) {
        .success-modal {
            margin: 20px auto;
            width: 95%;
            max-height: 85vh; /* Slightly smaller on mobile */
        }
        
        .modal-body {
            padding: 25px 20px;
            max-height: calc(85vh - 250px); /* Adjust for mobile header/footer */
        }
        
        .modal-header h2 {
            font-size: 1.7rem;
        }
        
        .success-icon {
            width: 70px;
            height: 70px;
        }
        
        .success-icon i {
            font-size: 40px;
        }
    }
    
    /* Fix for iOS Safari scrolling issues */
    @supports (-webkit-touch-callout: none) {
        .modal-body {
            -webkit-overflow-scrolling: touch;
        }
    }
</style>

<!-- Add JavaScript for the success modal -->
<script>
    // Function to show the success modal
    function showSuccessModal() {
        const modal = document.getElementById('registrationSuccessModal');
        modal.style.display = 'block';
        document.body.classList.add('modal-open');
        
        // Add entrance animations for key elements with slight delays
        setTimeout(() => {
            const referenceId = document.querySelector('.reference-id');
            if (referenceId) {
                referenceId.style.animation = 'fadeIn 0.5s ease forwards';
            }
        }, 500);
        
        setTimeout(() => {
            const successMessage = document.querySelector('.success-message');
            if (successMessage) {
                successMessage.style.animation = 'fadeIn 0.5s ease forwards';
            }
        }, 700);
        
        setTimeout(() => {
            const nextSteps = document.querySelector('.next-steps');
            if (nextSteps) {
                nextSteps.style.animation = 'fadeIn 0.5s ease forwards';
            }
        }, 900);
    }
    
    // Function to close the success modal
    function closeSuccessModal() {
        const modal = document.getElementById('registrationSuccessModal');
        
        // Add exit animation
        const modalContent = document.querySelector('.success-modal');
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
            
            // Redirect to dashboard
            window.location.href = 'stud_dashboard.php';
        }, 300);
    }
    
    // Close modal when clicking outside of it
    document.addEventListener('DOMContentLoaded', function() {
        const successModal = document.getElementById('registrationSuccessModal');
        if (successModal) {
            successModal.addEventListener('click', function(event) {
                if (event.target === successModal) {
                    closeSuccessModal();
                }
            });
        }
    });
    
    <?php if (isset($_SESSION['show_success_modal']) && $_SESSION['show_success_modal']): ?>
    // Show the modal automatically if the session flag is set
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(showSuccessModal, 200); // Small delay for better user experience
        <?php unset($_SESSION['show_success_modal']); ?>
    });
    <?php endif; ?>
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
<?php
// This file contains the success modal that is shown after successful registration

// Check if this file is being accessed directly
if (!defined('INCLUDE_MODAL')) {
    exit('Direct access not permitted');
}
?>

<!-- Registration Success Modal -->
<div id="registrationSuccessModal" class="modal">
    <div class="modal-content success-modal">
        <span class="close-modal" onclick="closeSuccessModal()">&times;</span>
        
        <div class="modal-header">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2>Registration Successful!</h2>
        </div>
        
        <div class="modal-body">
            <?php if (isset($_SESSION['reference_id'])): ?>
                <p class="reference-id"><span>Your Reference ID:</span> <strong><?php echo htmlspecialchars($_SESSION['reference_id']); ?></strong></p>
            <?php endif; ?>
            
            <div class="success-message">
                <p>Your application has been submitted successfully.</p>
                
                <?php if (isset($_SESSION['registration_status']) && $_SESSION['registration_status'] === 'needs_review'): ?>
                    <div class="review-notice">
                        <p><i class="fas fa-info-circle"></i> Your application requires manual review by an administrator.</p>
                        <p>This is usually due to your institution's grading system not being registered in our database.</p>
                    </div>
                <?php else: ?>
                    <div class="status-info">
                        <p><i class="fas fa-clipboard-check"></i> Your application will be reviewed by our admin team.</p>
                        <p><i class="fas fa-calendar-alt"></i> Please allow 2-3 business days for verification.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="next-steps">
                <h3><i class="fas fa-tasks"></i> Next Steps:</h3>
                <ul>
                    <li>Keep your Reference ID for future inquiries</li>
                    <li>Check your email for confirmation</li>
                    <li>Track your application status on the dashboard</li>
                </ul>
            </div>
        </div>
        
        <div class="modal-footer">
            <button class="btn btn-primary" onclick="window.location.href='stud_dashboard.php'">
                <i class="fas fa-home"></i> Return to Dashboard
            </button>
        </div>
    </div>
</div>

<!-- Add CSS styles for the success modal -->
<style>
    #registrationSuccessModal {
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
    
    .success-modal {
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
    
    .modal-header {
        background: linear-gradient(135deg, var(--primary) 0%, #3e1c23 100%);
        color: white;
        padding: 25px 20px;
        text-align: center;
        position: relative;
        flex: 0 0 auto; /* Don't shrink header */
    }
    
    .success-icon {
        background-color: white;
        width: 90px;
        height: 90px;
        border-radius: 50%;
        margin: 0 auto 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        animation: pulse 2s infinite;
    }
    
    .success-icon i {
        font-size: 50px;
        color: var(--primary);
    }
    
    .modal-header h2 {
        margin: 0;
        font-size: 2rem;
        font-weight: 600;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .modal-body {
        padding: 35px;
        color: var(--text-dark);
        font-size: 1.05rem;
        line-height: 1.6;
        overflow-y: auto; /* Add scroll to the body when needed */
        flex: 1 1 auto; /* Allow body to grow and shrink as needed */
        max-height: calc(90vh - 200px); /* Adjust based on header/footer size */
    }
    
    .reference-id {
        background-color: #f5f5f5;
        padding: 18px;
        border-radius: 10px;
        text-align: center;
        font-size: 1.3rem;
        margin-bottom: 25px;
        border-left: 5px solid var(--primary);
        display: flex;
        flex-direction: column;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        transition: transform 0.3s ease;
    }
    
    .reference-id:hover {
        transform: translateY(-3px);
    }
    
    .reference-id span {
        font-size: 0.9rem;
        color: #666;
        margin-bottom: 5px;
    }
    
    .reference-id strong {
        color: var(--primary);
        letter-spacing: 1px;
    }
    
    .success-message {
        margin-bottom: 25px;
        line-height: 1.7;
    }
    
    .review-notice {
        background-color: #fff8e1;
        border-left: 4px solid #ffc107;
        padding: 18px;
        border-radius: 10px;
        margin: 20px 0;
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.05);
    }
    
    .review-notice i, 
    .status-info i {
        color: #ffc107;
        margin-right: 8px;
    }
    
    .status-info {
        background-color: #e3f2fd;
        border-left: 4px solid #2196f3;
        padding: 18px;
        border-radius: 10px;
        margin: 20px 0;
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.05);
    }
    
    .status-info i {
        color: #2196f3;
    }
    
    .next-steps {
        background-color: #f8f9fa;
        padding: 22px;
        border-radius: 10px;
        margin-top: 25px;
        border-left: 4px solid #4caf50;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
    }
    
    .next-steps h3 {
        margin-top: 0;
        color: #2e7d32;
        font-size: 1.3rem;
        display: flex;
        align-items: center;
        margin-bottom: 15px;
    }
    
    .next-steps h3 i {
        margin-right: 10px;
    }
    
    .next-steps ul {
        margin-bottom: 0;
        padding-left: 30px;
    }
    
    .next-steps li {
        margin-bottom: 10px;
        position: relative;
    }
    
    .next-steps li:last-child {
        margin-bottom: 0;
    }
    
    .modal-footer {
        padding: 25px;
        text-align: center;
        border-top: 1px solid #eee;
        background-color: #fafafa;
        flex: 0 0 auto; /* Don't shrink footer */
    }
    
    .modal-footer .btn {
        padding: 12px 28px;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        border-radius: 50px;
    }
    
    .modal-footer .btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .close-modal {
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
    
    .close-modal:hover {
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
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    
    @media (max-width: 768px) {
        .success-modal {
            margin: 20px auto;
            width: 95%;
            max-height: 85vh; /* Slightly smaller on mobile */
        }
        
        .modal-body {
            padding: 25px 20px;
            max-height: calc(85vh - 250px); /* Adjust for mobile header/footer */
        }
        
        .modal-header h2 {
            font-size: 1.7rem;
        }
        
        .success-icon {
            width: 70px;
            height: 70px;
        }
        
        .success-icon i {
            font-size: 40px;
        }
    }
    
    /* Fix for iOS Safari scrolling issues */
    @supports (-webkit-touch-callout: none) {
        .modal-body {
            -webkit-overflow-scrolling: touch;
        }
    }
</style>

<!-- Add JavaScript for the success modal -->
<script>
    // Function to show the success modal
    function showSuccessModal() {
        const modal = document.getElementById('registrationSuccessModal');
        modal.style.display = 'block';
        document.body.classList.add('modal-open');
        
        // Add entrance animations for key elements with slight delays
        setTimeout(() => {
            const referenceId = document.querySelector('.reference-id');
            if (referenceId) {
                referenceId.style.animation = 'fadeIn 0.5s ease forwards';
            }
        }, 500);
        
        setTimeout(() => {
            const successMessage = document.querySelector('.success-message');
            if (successMessage) {
                successMessage.style.animation = 'fadeIn 0.5s ease forwards';
            }
        }, 700);
        
        setTimeout(() => {
            const nextSteps = document.querySelector('.next-steps');
            if (nextSteps) {
                nextSteps.style.animation = 'fadeIn 0.5s ease forwards';
            }
        }, 900);
    }
    
    // Function to close the success modal
    function closeSuccessModal() {
        const modal = document.getElementById('registrationSuccessModal');
        
        // Add exit animation
        const modalContent = document.querySelector('.success-modal');
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
            
            // Redirect to dashboard
            window.location.href = 'stud_dashboard.php';
        }, 300);
    }
    
    // Close modal when clicking outside of it
    document.addEventListener('DOMContentLoaded', function() {
        const successModal = document.getElementById('registrationSuccessModal');
        if (successModal) {
            successModal.addEventListener('click', function(event) {
                if (event.target === successModal) {
                    closeSuccessModal();
                }
            });
        }
    });
    
    <?php if (isset($_SESSION['show_success_modal']) && $_SESSION['show_success_modal']): ?>
    // Show the modal automatically if the session flag is set
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(showSuccessModal, 200); // Small delay for better user experience
        <?php unset($_SESSION['show_success_modal']); ?>
    });
    <?php endif; ?>
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
</script> 