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
/**
 * CCIS Qualifying Examination Registration Form Scripts
 * Handles form validation, navigation, and submission
 */

// Form Navigation and Validation Functions
function handleStudentTypeChange() {
    const studentType = document.getElementById('student_type').value;
    const previousProgramSelect = document.getElementById("previous_program");
    const yearLevelField = document.getElementById("year_level");
    // Find the parent form-group of the year_level field
    const yearLevelGroup = yearLevelField ? yearLevelField.closest('.form-group') : null;

    if (studentType === 'ladderized') {
        // For ladderized, hide year level field and its container
        if (yearLevelField) {
            yearLevelField.value = '1'; // Set default value
            yearLevelField.required = false;
            
            // Hide the entire form group containing the year level field
            if (yearLevelGroup) {
                yearLevelGroup.style.display = 'none';
            }
        }
        
        // For ladderized, find and select DICT program
        let dictOptionFound = false;
        
        // First, make all options visible
        Array.from(previousProgramSelect.options).forEach(option => {
            option.style.display = '';
        });
        
        // Find and select DICT option
        Array.from(previousProgramSelect.options).forEach(option => {
            if (option.text.includes('DICT') || 
                option.text.includes('Diploma in Information Communication Technology') ||
                option.value === 'DICT') {
                option.selected = true;
                dictOptionFound = true;
            } else if (option.value !== '') {
                // Hide non-DICT options (except the placeholder)
                option.style.display = 'none';
            }
        });
        
        // If no DICT option found, add one
        if (!dictOptionFound) {
            const dictOption = document.createElement('option');
            dictOption.value = 'DICT';
            dictOption.text = 'Diploma in Information Communication Technology (DICT)';
            dictOption.selected = true;
            previousProgramSelect.appendChild(dictOption);
        }
        
        // Disable the select to prevent changes
        previousProgramSelect.disabled = true;
    } else {
        // For other types, enable all fields
        if (yearLevelField) {
            yearLevelField.required = true;
            
            // Show the year level field and its container
            if (yearLevelGroup) {
                yearLevelGroup.style.display = '';
            }
        }
        
        // Enable and reset program selection
        previousProgramSelect.disabled = false;
        
        // Show all options except DICT for non-ladderized students
        Array.from(previousProgramSelect.options).forEach(option => {
            if (option.text.includes('DICT') || 
                option.text.includes('Diploma in Information Communication Technology')) {
                option.style.display = 'none';
            } else {
                option.style.display = '';
            }
        });
        
        // Reset to placeholder
        previousProgramSelect.value = '';
    }
}

function nextStep() {
    const currentStep = document.querySelector('.step.active');
    const nextStep = currentStep.nextElementSibling;

    if (nextStep) {
        // First hide all steps to ensure no multiple steps are shown
        document.querySelectorAll('.step').forEach(step => {
            step.classList.remove('active');
            step.style.display = 'none';
        });
        
        // Then activate only the next step
        nextStep.classList.add('active');
        nextStep.style.display = 'block';

        // Scroll to top of form
        document.querySelector('.form-section').scrollIntoView({ behavior: 'smooth' });
    }
}

function prevStep() {
    const currentStep = document.querySelector('.step.active');
    const prevStep = currentStep.previousElementSibling;

    if (prevStep) {
        // First hide all steps to ensure no multiple steps are shown
        document.querySelectorAll('.step').forEach(step => {
            step.classList.remove('active');
            step.style.display = 'none';
        });
        
        // Then activate only the previous step
        prevStep.classList.add('active');
        prevStep.style.display = 'block';

        // Scroll to top of form
        document.querySelector('.form-section').scrollIntoView({ behavior: 'smooth' });
    }
}

function showError(element, message) {
    // Remove any existing error messages
    const existingError = element.parentNode.querySelector('.error-message');
    if (existingError) {
        existingError.remove();
    }

    // Create error message element
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.style.color = 'red';
    errorDiv.style.fontSize = '0.8em';
    errorDiv.style.marginTop = '5px';
    errorDiv.textContent = message;

    // Insert error message after the element
    element.parentNode.insertBefore(errorDiv, element.nextSibling);

    // Highlight the input
    element.style.borderColor = 'red';
    element.addEventListener('input', function() {
        // Remove error styling when user starts typing
        this.style.borderColor = '';
        const errorMsg = this.parentNode.querySelector('.error-message');
        if (errorMsg) {
            errorMsg.remove();
        }
    });
}

function validateStep() {
    const activeStep = document.querySelector('.step.active');
    const stepIndex = Array.from(document.querySelectorAll('.step')).indexOf(activeStep);
    
    // Call validateCurrentStep which returns true/false
    const isValid = validateCurrentStep(stepIndex);
    
    // If valid and not already handled by validateCurrentStep, call nextStep
    if (isValid && stepIndex < document.querySelectorAll('.step').length - 1) {
        nextStep();
    }
    
    return isValid;
}

function validateCurrentStep(stepIndex) {
    const steps = document.querySelectorAll('.step');
    if (stepIndex >= steps.length) {
        console.error('Invalid step index:', stepIndex);
        return false;
    }
    
    const activeStep = steps[stepIndex];
    let isValid = true;
    
    // Clear any existing error messages
    const existingErrors = activeStep.querySelectorAll('.error-message');
    existingErrors.forEach(error => error.remove());
    
    // Get all required inputs in the current step
    const requiredInputs = activeStep.querySelectorAll('input[required], select[required]');
    
    // Do basic validation for all required fields
    requiredInputs.forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            showError(input, `${input.previousElementSibling ? input.previousElementSibling.textContent : 'Field'} is required`);
        }
    });
    
    // Special validation for specific steps
    switch(stepIndex) {
        case 0: // Student Type
            const studentType = document.getElementById('student_type');
            if (!studentType.value) {
                isValid = false;
                showError(studentType, 'Please select a student type');
            }
            break;

        case 1: // Personal Information
            // Basic validation already done with required fields
            break;

        case 2: // Contact Information
            const email = activeStep.querySelector('#email');
            const contact = activeStep.querySelector('#contact_number');
            
            if (email && email.value && !email.value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                isValid = false;
                showError(email, 'Please enter a valid email address');
            }
            
            if (contact && contact.value && !contact.value.match(/^[0-9]{11}$/)) {
                isValid = false;
                showError(contact, 'Please enter a valid 11-digit contact number');
            }
            break;

        case 3: // Academic Information
            const previousSchool = document.getElementById('previous_school');
            const previousProgram = document.getElementById('previous_program');
            const desiredProgram = document.getElementById('desired_program');
            const yearLevel = document.getElementById('year_level');

            if (!previousSchool.value) {
                isValid = false;
                showError(previousSchool, 'Please select your previous school');
            }
            if (!previousProgram.value) {
                isValid = false;
                showError(previousProgram, 'Please select your previous program');
            }
            if (!desiredProgram.value) {
                isValid = false;
                showError(desiredProgram, 'Please select your desired program');
            }
            // Only validate year level if not ladderized
            if (document.getElementById('student_type').value !== 'ladderized') {
                if (!yearLevel.value || yearLevel.value < 1 || yearLevel.value > 5) {
                    isValid = false;
                    showError(yearLevel, 'Please enter a valid year level (1-5)');
                }
            }
            break;

        case 4: // Document Upload
            const tor = document.getElementById('tor');
            const schoolId = document.getElementById('school_id');
            const hasCopyGrades = document.getElementById('has_copy_grades');
            const copyGrades = document.getElementById('copy_grades');

            if (!tor.files.length) {
                isValid = false;
                showError(tor, 'Please upload your Transcript of Records');
            }
            if (!schoolId.files.length) {
                isValid = false;
                showError(schoolId, 'Please upload your School ID');
            }
            if (hasCopyGrades.checked && !copyGrades.files.length) {
                isValid = false;
                showError(copyGrades, 'Please upload your Copy of Grades or uncheck the box');
            }
            break;
    }
    
    return isValid;
}

// Form Submission Functions
async function pingServer() {
    try {
        const response = await fetch('qualiexam_registerBack.php?ping=1', {
            method: 'HEAD',
            cache: 'no-store',
            headers: {
                'Cache-Control': 'no-cache'
            }
        });
        return response.ok;
    } catch (error) {
        console.error('Server ping failed:', error);
        return false;
    }
}

function submitForm(event) {
    event.preventDefault();
    
    // Validate the final document upload step (index 4)
    if (!validateCurrentStep(4)) {
        return false; // Don't proceed if validation fails
    }
    
    // Disable submit button to prevent multiple submissions
    const submitButtons = document.querySelectorAll('button[type="submit"]');
    submitButtons.forEach(button => {
        // Store original text for restoration later
        if (!button.hasAttribute('data-original-text')) {
            button.setAttribute('data-original-text', button.textContent.trim());
        }
        
        button.disabled = true;
        button.classList.add('processing');
        button.setAttribute('data-processing', 'true');
        
        // Update button text to show processing
        if (button.classList.contains('btn-submit')) {
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        } else {
            button.textContent = 'Processing...';
        }
    });
    
    // Clear any previous error messages
    const existingErrors = document.querySelectorAll('.error');
    existingErrors.forEach(error => error.remove());
    
    // Add a retry counter in case of failures
    window.submissionAttempts = window.submissionAttempts || 0;
    
    // Add a fallback if we've already tried multiple times
    if (window.submissionAttempts >= 2) {
        // Add a direct form submission as fallback
        console.log("Using direct form submission as fallback");
        const form = document.getElementById('multi-step-form');
        form.action = "qualiexam_registerBack.php";
        form.method = "POST";
        
        // Add a redirect URL as a hidden field
        const redirectField = document.createElement('input');
        redirectField.type = 'hidden';
        redirectField.name = 'redirect_on_success';
        redirectField.value = 'registration_success.php';
        form.appendChild(redirectField);
        
        // Let the form submit directly
        return true;
    }
    
    // Increment attempt counter
    window.submissionAttempts++;

    // Check if the server is accessible first
    pingServer().then(isServerAccessible => {
        if (isServerAccessible) {
            // IMPORTANT: Make sure the OCR processing and preview is shown first 
            // This function handles the OCR process and shows the OCR preview modal
            processOCR();
        } else {
            // If server is not accessible, show an error and enable the submit button
            const formSection = document.querySelector('.form-section');
            if (formSection) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error';
                errorDiv.innerHTML = `
                    <strong>Server Connection Error</strong><br>
                    Cannot connect to the server. Please check your internet connection and try again.<br>
                    <small>If the problem persists, please try the direct submission option below.</small>
                `;
                formSection.insertBefore(errorDiv, formSection.firstChild);
                
                // Add a direct submission button
                const directSubmitContainer = document.createElement('div');
                directSubmitContainer.className = 'direct-submit-container';
                directSubmitContainer.style.marginTop = '20px';
                directSubmitContainer.style.textAlign = 'center';
                directSubmitContainer.style.padding = '15px';
                directSubmitContainer.style.backgroundColor = '#f8f9fa';
                directSubmitContainer.style.borderRadius = '8px';
                
                const directSubmitButton = document.createElement('button');
                directSubmitButton.type = 'button';
                directSubmitButton.className = 'btn btn-next';
                directSubmitButton.textContent = 'Try Direct Submission';
                directSubmitButton.style.backgroundColor = '#5a2930';
                directSubmitButton.style.margin = '0 auto';
                
                directSubmitButton.addEventListener('click', function() {
                    const form = document.getElementById('multi-step-form');
                    form.action = "qualiexam_registerBack.php";
                    form.method = "POST";
                    
                    // Add action and redirect fields
                    let actionField = form.querySelector('input[name="action"]');
                    if (!actionField) {
                        actionField = document.createElement('input');
                        actionField.type = 'hidden';
                        actionField.name = 'action';
                        form.appendChild(actionField);
                    }
                    actionField.value = 'final_submit';
                    
                    const redirectField = document.createElement('input');
                    redirectField.type = 'hidden';
                    redirectField.name = 'redirect_on_success';
                    redirectField.value = 'registration_success.php';
                    form.appendChild(redirectField);
                    
                    // Submit the form directly
                    form.submit();
                });
                
                directSubmitContainer.appendChild(directSubmitButton);
                
                // Add instructions
                const instructions = document.createElement('p');
                instructions.style.marginTop = '10px';
                instructions.style.fontSize = '0.9rem';
                instructions.innerHTML = 'This will bypass the AJAX submission process and submit your form directly.';
                directSubmitContainer.appendChild(instructions);
                
                formSection.querySelector('.buttons').appendChild(directSubmitContainer);
            }
            
            // Re-enable submit buttons
            submitButtons.forEach(button => {
                button.disabled = false;
                button.classList.remove('processing');
                button.removeAttribute('data-processing');
                
                // Restore original text
                const originalText = button.getAttribute('data-original-text');
                if (originalText && button.classList.contains('btn-submit')) {
                    button.innerHTML = '<i class="fas fa-check"></i> ' + originalText;
                } else if (originalText) {
                    button.textContent = originalText;
                } else {
                    button.textContent = 'Submit Application';
                }
            });
        }
    }).catch(() => {
        // Handle network errors
        // Reset button states
        submitButtons.forEach(button => {
            button.disabled = false;
            button.classList.remove('processing');
            button.removeAttribute('data-processing');
            
            // Restore original text
            const originalText = button.getAttribute('data-original-text');
            if (originalText && button.classList.contains('btn-submit')) {
                button.innerHTML = '<i class="fas fa-check"></i> ' + originalText;
            } else if (originalText) {
                button.textContent = originalText;
            } else {
                button.textContent = 'Submit Application';
            }
        });
        
        // Show error message
        const formSection = document.querySelector('.form-section');
        if (formSection) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error';
            errorDiv.innerHTML = `
                <strong>Network Error</strong><br>
                Cannot connect to the server. Please check your internet connection and try again.
            `;
            formSection.insertBefore(errorDiv, formSection.firstChild);
        }
    });
    
    return false; // Prevent default form submission
}

// Utility functions for smooth UI transitions
function showElement(element, duration = 300) {
    if (!element) return;
    
    // First set display to ensure element is in the DOM
    element.style.display = element.tagName.toLowerCase() === 'div' ? 'block' : 'flex';
    
    // Force a reflow to ensure transition will work
    void element.offsetWidth;
    
    // Then transition opacity
    element.style.opacity = '0';
    setTimeout(() => {
        element.style.transition = `opacity ${duration}ms ease`;
        element.style.opacity = '1';
    }, 10);
}

function hideElement(element, duration = 300) {
    if (!element) return;
    
    // Transition to invisible
    element.style.transition = `opacity ${duration}ms ease`;
    element.style.opacity = '0';
    
    // Then remove from DOM after transition completes
    setTimeout(() => {
        element.style.display = 'none';
    }, duration);
}

// Updated processOCR function with better transitions
async function processOCR() {
    try {
        // Get necessary elements
        const form = document.getElementById('multi-step-form');
        const mainContent = document.querySelector('.main-content');
        const formSection = document.querySelector('.form-section');
        const ocrModal = document.getElementById('ocrPreviewModal');
        const ocrContent = document.getElementById('ocrResultsContent');
        const loadingOverlay = document.getElementById('ocr-loading-overlay');
        
        // First, disable submit button to prevent multiple submissions
        const submitButtons = document.querySelectorAll('button[type="submit"]');
        submitButtons.forEach(button => {
            // Store original text for restoration later
            if (!button.hasAttribute('data-original-text')) {
                button.setAttribute('data-original-text', button.textContent.trim());
            }
            
            button.disabled = true;
            button.classList.add('processing');
            button.setAttribute('data-processing', 'true');
            
            // Update button text to show processing
            if (button.classList.contains('btn-submit')) {
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            } else {
                button.textContent = 'Processing...';
            }
        });

        // Store important containers to restore them later
        window.formContainers = {
            mainContent: mainContent,
            formSection: formSection,
            form: form
        };
        
        // Dim the form but keep it visible
        if (formSection) {
            formSection.classList.add('dimmed');
            formSection.style.display = 'block';
            formSection.style.visibility = 'visible';
        }
        
        // Make sure ocr modal is initially hidden
        if (ocrModal) {
            ocrModal.style.display = 'none';
            ocrModal.style.visibility = 'hidden';
            ocrModal.style.opacity = '0';
        }
        
        // First, show only the loading overlay with smooth transition
        if (loadingOverlay) {
            loadingOverlay.style.display = 'flex';
            loadingOverlay.style.visibility = 'visible';
            loadingOverlay.style.opacity = '1';
            loadingOverlay.style.zIndex = '10000'; // Ensure it's on top
        }
        
        // Get form data
        const formData = new FormData(form);
        formData.append('action', 'process_ocr');
        
        // Add a delay to allow the UI to update before sending the request
        await new Promise(resolve => setTimeout(resolve, 100));
        
        // Create an AbortController with a timeout
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 90000); // 90 second timeout for OCR
        
        try {
            // Log to console to show we're attempting the OCR request
            console.log("Sending OCR request to server...");
            
            // Send the request to the server with improved options
            const response = await fetch('qualiexam_registerBack.php', {
                method: 'POST',
                body: formData,
                signal: controller.signal,
                headers: {
                    'Cache-Control': 'no-cache, no-store, must-revalidate',
                    'Pragma': 'no-cache',
                    'Expires': '0'
                },
                cache: 'no-store'
            });
            
            // Clear the timeout since we got a response
            clearTimeout(timeoutId);
            
            console.log("OCR response received, status:", response.status);
            
            // Now that we have a response, hide the loading overlay
            if (loadingOverlay) {
                loadingOverlay.style.opacity = '0';
                
                // Use a callback to ensure the loading overlay is fully hidden before showing the OCR modal
                setTimeout(() => {
                    loadingOverlay.style.display = 'none';
                    loadingOverlay.style.visibility = 'hidden';
                    
                    // IMPORTANT: Make sure the OCR preview modal is shown clearly to the user
                    if (ocrModal) {
                        console.log("Showing OCR preview modal to user");
                        ocrModal.style.display = 'block';
                        ocrModal.style.visibility = 'visible';
                        ocrModal.style.opacity = '1';
                        ocrModal.style.zIndex = '10000'; // Ensure it's on top
                    }
                }, 300);
            }
            
            // Check if response is ok before parsing JSON
            if (!response.ok) {
                throw new Error(`Server responded with status: ${response.status}`);
            }
            
            // Parse the response as JSON
            let result;
            try {
                result = await response.json();
            } catch (jsonError) {
                console.error('Failed to parse JSON response:', jsonError);
                throw new Error('Invalid response from server. Please try again.');
            }
            
            if (result.error) {
                throw new Error(result.error);
            }
            
            // Store subjects in hidden field
            document.getElementById('subjects_data').value = JSON.stringify(result.subjects);
            
            // Display the OCR results in a table
            let tableHtml = `
                <div class="table-controls">
                    <button type="button" class="btn-add-row" onclick="addTableRow()">
                        <i class="fas fa-plus"></i> Add Subject
                    </button>
                </div>
                <table class="grades-table">
                    <thead>
                        <tr>
                            <th>Subject Code</th>
                            <th>Description</th>
                            <th>Units</th>
                            <th>Grades</th>
                            <th width="50">Action</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            result.subjects.forEach(subject => {
                tableHtml += `
                    <tr>
                        <td contenteditable="true" class="editable-cell">${subject.subject_code || ''}</td>
                        <td contenteditable="true" class="editable-cell">${subject.subject_description || ''}</td>
                        <td contenteditable="true" class="editable-cell">${subject.units || ''}</td>
                        <td contenteditable="true" class="editable-cell">${subject.Grades || ''}</td>
                        <td class="action-cell">
                            <button type="button" class="delete-row-btn" onclick="deleteTableRow(this)">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            tableHtml += '</tbody></table>';
            ocrContent.innerHTML = tableHtml;
            
            // Make all cells editable
            const editableCells = ocrContent.querySelectorAll('.editable-cell');
            editableCells.forEach(cell => {
                makeTableCellEditable(cell);
            });
            
            // Update the subjects data immediately
            updateSubjectsData();
            
        } catch (error) {
            // Check if this was a timeout error
            if (error.name === 'AbortError') {
                // This is a timeout, but the OCR process might still be running
                // Hide loading overlay
                if (loadingOverlay) {
                    loadingOverlay.style.opacity = '0';
                    setTimeout(() => {
                        loadingOverlay.style.display = 'none';
                        loadingOverlay.style.visibility = 'hidden';
                        
                        // Show the OCR modal with error message
                        if (ocrModal) {
                            ocrModal.style.display = 'block';
                            ocrModal.style.visibility = 'visible';
                            ocrModal.style.opacity = '1';
                            ocrModal.style.zIndex = '10000';
                        }
                    }, 300);
                }

                // Show the modal with error message
                if (ocrContent) {
                    ocrContent.innerHTML = `
                        <div class="info-message" style="padding:15px; background-color:#d1ecf1; color:#0c5460; border-radius:5px; margin-bottom:20px; border-left:4px solid #0c5460;">
                            <strong>OCR Processing is taking longer than expected</strong><br>
                            Your document is complex and is taking longer to process. You can:
                            <ul>
                                <li>Wait a bit longer for the process to complete</li>
                                <li>Cancel and try again with a clearer document image</li>
                                <li>Continue without OCR and enter your subjects manually</li>
                            </ul>
                        </div>
                        <div style="text-align:center; margin-top:20px;">
                            <button type="button" class="prev-btn" onclick="closeOCRPreview()">Cancel OCR</button>
                        </div>
                    `;
                }
                
                return;
            }
            
            // For other errors, proceed with standard error handling
            console.error('Error during OCR processing:', error);
            
            // Hide loading overlay
            if (loadingOverlay) {
                loadingOverlay.style.opacity = '0';
                setTimeout(() => {
                    loadingOverlay.style.display = 'none';
                    loadingOverlay.style.visibility = 'hidden';
                    
                    // Show the OCR modal with error message
                    if (ocrModal) {
                        ocrModal.style.display = 'block';
                        ocrModal.style.visibility = 'visible';
                        ocrModal.style.opacity = '1';
                        ocrModal.style.zIndex = '10000';
                    }
                }, 300);
            }
            
            const errorMessage = error.message || 'An error occurred during processing';
            
            ocrContent.innerHTML = `
                <div class="error-message" style="padding:15px; background-color:#f8d7da; color:#721c24; border-radius:5px; margin-bottom:20px; border-left:4px solid #dc3545;">
                    <strong>Error during OCR processing:</strong><br>
                    ${errorMessage}<br>
                    <small>If this error persists, please try refreshing the page or contact support.</small>
                </div>
                <div style="text-align:center; margin-top:20px;">
                    <button type="button" class="prev-btn" onclick="closeOCRPreview()">Go Back</button>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error during OCR processing setup:', error);
        
        const ocrContent = document.getElementById('ocrResultsContent');
        const loadingOverlay = document.getElementById('ocr-loading-overlay');
        const ocrModal = document.getElementById('ocrPreviewModal');
        
        // Hide loading overlay
        if (loadingOverlay) {
            loadingOverlay.style.opacity = '0';
            setTimeout(() => {
                loadingOverlay.style.display = 'none';
                loadingOverlay.style.visibility = 'hidden';
                
                // Show the OCR modal with error message
                if (ocrModal) {
                    ocrModal.style.display = 'block';
                    ocrModal.style.visibility = 'visible';
                    ocrModal.style.opacity = '1';
                    ocrModal.style.zIndex = '10000';
                }
            }, 300);
        }
        
        const errorMessage = error.message || 'An error occurred during processing';
        
        ocrContent.innerHTML = `
            <div class="error-message">
                <strong>Error during OCR processing:</strong><br>
                ${errorMessage}<br>
                <small>If this error persists, please try refreshing the page or contact support.</small>
            </div>
            <div style="text-align:center; margin-top:20px;">
                <button type="button" class="prev-btn" onclick="closeOCRPreview()">Cancel</button>
            </div>
        `;
    }
}

// Updated closeOCRPreview function to properly restore the form state
function closeOCRPreview() {
    // Get necessary elements
    const ocrModal = document.getElementById('ocrPreviewModal');
    const formSection = document.querySelector('.form-section');
    const submitButtons = document.querySelectorAll('button[type="submit"]');
    
    console.log("Closing OCR preview modal");
    
    // Hide the OCR preview modal with multiple approaches to ensure it's hidden
    if (ocrModal) {
        // Apply multiple techniques to ensure the modal is completely hidden
        ocrModal.style.display = 'none';
        ocrModal.style.visibility = 'hidden';
        ocrModal.style.opacity = '0';
        ocrModal.style.zIndex = '-1';
        ocrModal.classList.add('force-hidden');
        ocrModal.setAttribute('aria-hidden', 'true');
        
        // Use !important to override any potential conflicting styles
        ocrModal.style.cssText = "display: none !important; visibility: hidden !important; opacity: 0 !important; z-index: -9999 !important;";
    }
    
    // Restore form section
    if (formSection) {
        formSection.classList.remove('dimmed', 'processing');
        formSection.style.display = 'block';
        formSection.style.visibility = 'visible';
        formSection.style.opacity = '1';
    }
    
    // Re-enable submit buttons
    submitButtons.forEach(button => {
        button.disabled = false;
        button.classList.remove('processing');
        button.removeAttribute('data-processing');
        
        // Restore original text
        const originalText = button.getAttribute('data-original-text');
        if (originalText && button.classList.contains('btn-submit')) {
            button.innerHTML = '<i class="fas fa-check"></i> ' + originalText;
        } else if (originalText) {
            button.textContent = originalText;
        } else {
            button.textContent = 'Submit Application';
        }
    });
    
    // Re-enable all inputs
    const inputs = document.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.disabled = false;
    });
    
    console.log("OCR preview modal closed, form state restored");
}

// Updated confirmAndSubmit function to ensure proper modal handling
async function confirmAndSubmit() {
    try {
        // Get necessary elements
        const ocrModal = document.getElementById('ocrPreviewModal');
        const confirmButton = document.querySelector('.modal-footer .nxt-btn');
        const cancelButton = document.querySelector('.modal-footer .prev-btn');
        const loadingOverlay = document.getElementById('submission-loading-overlay');
        const formSection = document.querySelector('.form-section');
        const mainContent = document.querySelector('.main-content');
        const contentWrapper = document.querySelector('.content-wrapper');
        
        // Make sure to update the subjects data with the latest edits
        updateSubjectsData();
        
        // Log that we're starting the final submission process
        console.log("Starting final submission process after OCR preview");
        
        // Disable buttons to prevent double submissions
        if (confirmButton) {
            confirmButton.disabled = true;
            confirmButton.textContent = 'Processing...';
        }
        if (cancelButton) {
            cancelButton.disabled = true;
        }
        
        // Completely hide the OCR modal with all possible hiding techniques
        if (ocrModal) {
            console.log("Hiding OCR preview modal for final submission");
            // Use multiple techniques to ensure the modal is hidden
            ocrModal.style.display = 'none';
            ocrModal.style.visibility = 'hidden';
            ocrModal.style.opacity = '0';
            ocrModal.style.zIndex = '-9999'; // Very low z-index to ensure it's beneath everything
            ocrModal.setAttribute('aria-hidden', 'true');
            ocrModal.classList.add('force-hidden');
            
            // Use !important to override any conflicting styles
            ocrModal.style.cssText = "display: none !important; visibility: hidden !important; opacity: 0 !important; z-index: -9999 !important;";
        }
        
        // Add a class to body to indicate form is being processed
        document.body.classList.add('form-processing');
        
        // Ensure form remains visible but dimmed during submission
        if (formSection) {
            formSection.classList.add('processing');
            formSection.style.display = 'block';
        }
        
        // Show loading overlay for final submission
        if (loadingOverlay) {
            loadingOverlay.style.display = 'flex';
            loadingOverlay.style.visibility = 'visible';
            loadingOverlay.style.opacity = '1';
            loadingOverlay.style.zIndex = '10000'; // Ensure it's on top
        }
        
        // Get the form and form data
        const form = document.getElementById('multi-step-form');
        const formData = new FormData(form);
        
        // Add the action parameter for final submission
        formData.append('action', 'final_submit');
        
        // Get the subjects data from the hidden field
        const subjectsData = document.getElementById('subjects_data').value;
        if (subjectsData) {
            formData.append('subjects', subjectsData);
        }
        
        // Add a short delay to allow UI updates before fetch
        await new Promise(resolve => setTimeout(resolve, 300));
        
        // Submit the form with the final action
        console.log("Sending final submission request to server");
        const response = await fetch('qualiexam_registerBack.php', {
            method: 'POST',
            body: formData
        });
        
        // Hide loading overlay
        if (loadingOverlay) {
            loadingOverlay.style.opacity = '0';
            setTimeout(() => {
                loadingOverlay.style.display = 'none';
                loadingOverlay.style.visibility = 'hidden';
            }, 300);
        }
        
        // Check if response is ok
        if (!response.ok) {
            throw new Error(`Server responded with status: ${response.status}`);
        }
        
        // Parse the response
        const result = await response.json();
        console.log("Received final submission response:", result);
        
        // Handle error
        if (result.error) {
            // Make sure the OCR preview modal is completely hidden
            if (ocrModal) {
                ocrModal.style.cssText = "display: none !important; visibility: hidden !important; opacity: 0 !important; z-index: -9999 !important;";
            }
            
            // Show error message in the form section
            if (formSection) {
                formSection.classList.remove('processing');
                formSection.style.display = 'block';
                
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.innerHTML = `
                    <strong>Registration Error:</strong><br>
                    ${result.error}<br>
                    <small>Please correct the information and try again.</small>
                `;
                
                formSection.insertBefore(errorDiv, formSection.firstChild);
                
                // Re-enable form controls
                const inputs = form.querySelectorAll('input, select, textarea, button');
                inputs.forEach(input => {
                    input.disabled = false;
                });
                
                // Reset the submit button
                const submitButton = form.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.textContent = 'Submit Application';
                    submitButton.disabled = false;
                }
            }
            
            // Also show error modal if it exists
            const errorModal = document.getElementById('registrationErrorModal');
            if (errorModal) {
                console.log("Showing error modal");
                // Ensure the error message is set
                const errorMessageElement = errorModal.querySelector('.error-message');
                if (errorMessageElement) {
                    errorMessageElement.textContent = result.error;
                }
                
                // Show the error modal
                errorModal.style.display = 'block';
                errorModal.style.visibility = 'visible';
                errorModal.style.opacity = '1';
                errorModal.style.zIndex = '10001'; // Higher than OCR modal
            }
            
            return;
        }
        
        // Handle success
        if (result.success) {
            console.log("Registration successful, preparing success UI");
            
            // Make sure the OCR preview modal is completely hidden
            if (ocrModal) {
                ocrModal.style.cssText = "display: none !important; visibility: hidden !important; opacity: 0 !important; z-index: -9999 !important;";
            }
            
            // Show success modal if it exists
            const successModal = document.getElementById('registrationSuccessModal');
            if (successModal) {
                console.log("Showing success modal");
                
                // Ensure reference ID is set in the modal if available
                if (result.reference_id) {
                    const referenceIdElement = successModal.querySelector('.reference-id');
                    if (referenceIdElement) {
                        referenceIdElement.textContent = result.reference_id;
                    }
                }
                
                // Show the success modal
                successModal.style.display = 'block';
                successModal.style.visibility = 'visible';
                successModal.style.opacity = '1';
                successModal.style.zIndex = '10001'; // Higher than OCR modal
            } else {
                console.log("Success modal not found, using redirect");
                // If success modal doesn't exist or redirect is provided, redirect
                if (result.redirect_url) {
                    window.location.href = result.redirect_url;
                }
            }
            
            return;
        }
        
        // If we get here, something unexpected happened
        console.log("Unexpected response:", result);
        throw new Error('Unexpected response from server');
        
    } catch (error) {
        console.error('Error during form submission:', error);
        
        // Get elements again in case they weren't captured in the try block
        const ocrModal = document.getElementById('ocrPreviewModal');
        const loadingOverlay = document.getElementById('submission-loading-overlay');
        const formSection = document.querySelector('.form-section');
        
        // Hide loading overlay
        if (loadingOverlay) {
            loadingOverlay.style.opacity = '0';
            setTimeout(() => {
                loadingOverlay.style.display = 'none';
                loadingOverlay.style.visibility = 'hidden';
            }, 300);
        }
        
        // Hide OCR modal
        if (ocrModal) {
            ocrModal.style.cssText = "display: none !important; visibility: hidden !important; opacity: 0 !important; z-index: -9999 !important;";
        }
        
        // Show error in form section
        if (formSection) {
            formSection.classList.remove('processing');
            formSection.style.display = 'block';
            
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.innerHTML = `
                <strong>Submission Error:</strong><br>
                ${error.message}<br>
                <small>Please try again or contact support if the problem persists.</small>
            `;
            
            formSection.insertBefore(errorDiv, formSection.firstChild);
            
            // Re-enable form controls
            const form = document.getElementById('multi-step-form');
            if (form) {
                const inputs = form.querySelectorAll('input, select, textarea, button');
                inputs.forEach(input => {
                    input.disabled = false;
                });
                
                // Reset the submit button
                const submitButton = form.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.textContent = 'Submit Application';
                    submitButton.disabled = false;
                }
            }
        }
        
        // Also show error modal if it exists
        const errorModal = document.getElementById('registrationErrorModal');
        if (errorModal) {
            // Ensure the error message is set
            const errorMessageElement = errorModal.querySelector('.error-message');
            if (errorMessageElement) {
                errorMessageElement.textContent = error.message;
            }
            
            // Show the error modal
            errorModal.style.display = 'block';
            errorModal.style.visibility = 'visible';
            errorModal.style.opacity = '1';
            errorModal.style.zIndex = '10001'; // Higher than OCR modal
        }
    }
}

// Modal and Document Functions
function toggleCopyGradesUpload() {
    const checkbox = document.getElementById('has_copy_grades');
    const copyGradesField = document.getElementById('copy-grades-field');
    const copyGradesInput = document.getElementById('copy_grades');
    
    if (checkbox.checked) {
        copyGradesField.style.display = 'block';
        copyGradesInput.required = true;
    } else {
        copyGradesField.style.display = 'none';
        copyGradesInput.required = false;
        copyGradesInput.value = ''; // Clear the file input
    }
}

function handleEditableSelect(select) {
    if (select.value === 'Other') {
        const input = document.createElement('input');
        input.type = 'text';
        input.className = select.className;
        input.required = true;
        input.placeholder = 'Enter your previous school';
        input.name = select.name;
        input.id = select.id + '_custom';
        
        // Create a container for the input and a back button
        const container = document.createElement('div');
        container.className = 'editable-select-container';
        
        // Add a back button
        const backBtn = document.createElement('button');
        backBtn.type = 'button';
        backBtn.className = 'back-to-select-btn';
        backBtn.innerHTML = '<i class="fas fa-arrow-left"></i>';
        backBtn.onclick = function() {
            container.parentNode.replaceChild(select, container);
            select.value = '';
        };
        
        container.appendChild(input);
        container.appendChild(backBtn);
        
        // Replace the select with the input container
        select.parentNode.replaceChild(container, select);
        input.focus();
    }
}

// Add these functions for editable OCR table

// Function to make a cell editable
function makeTableCellEditable(cell) {
    cell.setAttribute('contenteditable', 'true');
    cell.classList.add('editable-cell');
    cell.addEventListener('focus', function() {
        // Store original value in case we need to restore it
        if (!this.hasAttribute('data-original')) {
            this.setAttribute('data-original', this.textContent);
        }
    });
    
    cell.addEventListener('blur', function() {
        // Trim whitespace
        this.textContent = this.textContent.trim();
        
        // If empty, restore to original or set to placeholder
        if (!this.textContent) {
            const original = this.getAttribute('data-original');
            this.textContent = original || '';
        }
        
        // Update the hidden subjects data
        updateSubjectsData();
    });
    
    // Add key event listeners
    cell.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault(); // Prevent newline
            this.blur(); // Lose focus
        }
    });
}

// Function to add a new row to the table
function addTableRow() {
    const table = document.querySelector('.grades-table tbody');
    if (!table) return;
    
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
        <td contenteditable="true" class="editable-cell"></td>
        <td contenteditable="true" class="editable-cell"></td>
        <td contenteditable="true" class="editable-cell"></td>
        <td contenteditable="true" class="editable-cell"></td>
        <td class="action-cell">
            <button type="button" class="delete-row-btn" onclick="deleteTableRow(this)">
                <i class="fas fa-trash-alt"></i>
            </button>
        </td>
    `;
    
    table.appendChild(newRow);
    
    // Make all cells editable
    const cells = newRow.querySelectorAll('td[contenteditable="true"]');
    cells.forEach(cell => {
        makeTableCellEditable(cell);
    });
    
    // Focus on the first cell
    cells[0].focus();
    
    // Update the hidden subjects data
    updateSubjectsData();
}

// Function to delete a row from the table
function deleteTableRow(button) {
    const row = button.closest('tr');
    if (row) {
        // Optional: Add confirmation
        if (confirm('Are you sure you want to delete this subject?')) {
            row.remove();
            // Update the hidden subjects data
            updateSubjectsData();
        }
    }
}

// Function to update the hidden subjects data field
function updateSubjectsData() {
    const subjectsDataField = document.getElementById('subjects_data');
    if (!subjectsDataField) return;
    
    const table = document.querySelector('.grades-table tbody');
    if (!table) return;
    
    const rows = table.querySelectorAll('tr');
    const subjects = [];
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        // Skip if it's the action cell or if the row doesn't have enough cells
        if (cells.length < 4) return;
        
        subjects.push({
            subject_code: cells[0].textContent.trim(),
            subject_description: cells[1].textContent.trim(),
            units: cells[2].textContent.trim(),
            Grades: cells[3].textContent.trim()
        });
    });
    
    subjectsDataField.value = JSON.stringify(subjects);
}

// Initialization
document.addEventListener('DOMContentLoaded', function() {
    // Hide all modals and processing overlays on page load
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.style.display = 'none';
    });
    
    const loadingOverlay = document.getElementById('submission-loading-overlay');
    if (loadingOverlay) {
        loadingOverlay.style.display = 'none';
    }
    
    // Make sure all steps are initialized correctly
    const allSteps = document.querySelectorAll('.step');
    
    // Hide all steps initially
    allSteps.forEach(step => {
        step.classList.remove('active');
        step.style.display = 'none';
    });
    
    // Initialize the first step only
    const firstStep = document.querySelector('.step');
    if (firstStep) {
        firstStep.classList.add('active');
        firstStep.style.display = 'block';
    }
    
    // Make sure all next buttons have the validateStep function
    document.querySelectorAll('.btn-next').forEach(button => {
        if (!button.hasAttribute('onclick')) {
            button.setAttribute('onclick', 'validateStep()');
        }
    });
    
    // Add event listener for student type changes
    const studentTypeSelect = document.getElementById('student_type');
    if (studentTypeSelect) {
        studentTypeSelect.addEventListener('change', handleStudentTypeChange);
        // Also handle initial state
        handleStudentTypeChange();
    }
    
    // Add event listener for copy grades checkbox
    const hasCopyGrades = document.getElementById('has_copy_grades');
    if (hasCopyGrades) {
        hasCopyGrades.addEventListener('change', toggleCopyGradesUpload);
    }
});

// Setup on window load
window.onload = function() {
    // Make sure loading overlays are hidden on page load
    const ocrModal = document.getElementById('ocrPreviewModal');
    const loadingOverlay = document.getElementById('submission-loading-overlay');
    
    if (ocrModal) {
        ocrModal.style.display = 'none';
    }
    
    if (loadingOverlay) {
        loadingOverlay.style.display = 'none';
    }
};

// Call handleStudentTypeChange when the DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize student type handler
    const studentTypeSelect = document.getElementById('student_type');
    if (studentTypeSelect) {
        // Add change event listener
        studentTypeSelect.addEventListener('change', handleStudentTypeChange);
        
        // Call it once on page load
        handleStudentTypeChange();
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
/**
 * CCIS Qualifying Examination Registration Form Scripts
 * Handles form validation, navigation, and submission
 */

// Choices.js for #previous_program is now initialized in the main HTML file. Do not re-initialize here.

// Form Navigation and Validation Functions
function handleStudentTypeChange() {
    const studentType = document.getElementById('student_type').value;
    const previousProgramInput = document.getElementById("previous_program");
    const yearLevelField = document.getElementById("year_level");
    const yearLevelGroup = yearLevelField ? yearLevelField.closest('.form-group') : null;

    if (studentType === 'ladderized') {
        if (yearLevelField) {
            yearLevelField.value = '1';
            yearLevelField.required = false;
            if (yearLevelGroup) yearLevelGroup.style.display = 'none';
        }
        if (previousProgramInput) {
            previousProgramInput.value = 'Diploma in Information Communication Technology (DICT)';
            previousProgramInput.readOnly = true;
        }
    } else {
        if (yearLevelField) {
            yearLevelField.required = true;
            if (yearLevelGroup) yearLevelGroup.style.display = '';
        }
        if (previousProgramInput) {
            previousProgramInput.value = '';
            previousProgramInput.readOnly = false;
        }
    }
}

function setStepRequiredAttributes(stepIndex) {
    const steps = document.querySelectorAll('.step');
    steps.forEach((step, idx) => {
        const requiredInputs = step.querySelectorAll('[required]');
        requiredInputs.forEach(input => {
            if (idx === stepIndex) {
                input.setAttribute('required', 'required');
            } else {
                input.removeAttribute('required');
            }
        });
    });
}

function nextStep() {
    const currentStep = document.querySelector('.step.active');
    const nextStep = currentStep.nextElementSibling;

    if (nextStep) {
        // First hide all steps to ensure no multiple steps are shown
        document.querySelectorAll('.step').forEach(step => {
            step.classList.remove('active');
            step.style.display = 'none';
        });
        
        // Then activate only the next step
        nextStep.classList.add('active');
        nextStep.style.display = 'block';

        // Scroll to top of form
        document.querySelector('.form-section').scrollIntoView({ behavior: 'smooth' });
    }
}

function prevStep() {
    const currentStep = document.querySelector('.step.active');
    const prevStep = currentStep.previousElementSibling;

    if (prevStep) {
        // First hide all steps to ensure no multiple steps are shown
        document.querySelectorAll('.step').forEach(step => {
            step.classList.remove('active');
            step.style.display = 'none';
        });
        
        // Then activate only the previous step
        prevStep.classList.add('active');
        prevStep.style.display = 'block';

        // Scroll to top of form
        document.querySelector('.form-section').scrollIntoView({ behavior: 'smooth' });
    }
}

function showError(element, message) {
    // Remove any existing error messages
    const existingError = element.parentNode.querySelector('.error-message');
    if (existingError) {
        existingError.remove();
    }

    // Create error message element
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.style.color = 'red';
    errorDiv.style.fontSize = '0.8em';
    errorDiv.style.marginTop = '5px';
    errorDiv.textContent = message;

    // Insert error message after the element
    element.parentNode.insertBefore(errorDiv, element.nextSibling);

    // Highlight the input
    element.style.borderColor = 'red';
    element.addEventListener('input', function() {
        // Remove error styling when user starts typing
        this.style.borderColor = '';
        const errorMsg = this.parentNode.querySelector('.error-message');
        if (errorMsg) {
            errorMsg.remove();
        }
    });
}

function validateStep() {
    const activeStep = document.querySelector('.step.active');
    const stepIndex = Array.from(document.querySelectorAll('.step')).indexOf(activeStep);
    
    // Call validateCurrentStep which returns true/false
    const isValid = validateCurrentStep(stepIndex);
    
    // If valid and not already handled by validateCurrentStep, call nextStep
    if (isValid && stepIndex < document.querySelectorAll('.step').length - 1) {
        nextStep();
    }
    
    return isValid;
}

function validateCurrentStep(stepIndex) {
    const steps = document.querySelectorAll('.step');
    if (stepIndex >= steps.length) {
        console.error('Invalid step index:', stepIndex);
        return false;
    }
    
    const activeStep = steps[stepIndex];
    let isValid = true;
    
    // Clear any existing error messages
    const existingErrors = activeStep.querySelectorAll('.error-message');
    existingErrors.forEach(error => error.remove());
    
    // Get all required inputs in the current step
    const requiredInputs = activeStep.querySelectorAll('input[required], select[required]');
    
    // Do basic validation for all required fields
    requiredInputs.forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            showError(input, `${input.previousElementSibling ? input.previousElementSibling.textContent : 'Field'} is required`);
        }
    });
    
    // Special validation for specific steps
    switch(stepIndex) {
        case 0: // Student Type
            const studentType = document.getElementById('student_type');
            if (!studentType.value) {
                isValid = false;
                showError(studentType, 'Please select a student type');
            }
            break;

        case 1: // Personal Information
            // Basic validation already done with required fields
            break;

        case 2: // Contact Information
            const email = activeStep.querySelector('#email');
            const contact = activeStep.querySelector('#contact_number');
            
            if (email && email.value && !email.value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                isValid = false;
                showError(email, 'Please enter a valid email address');
            }
            
            if (contact && contact.value && !contact.value.match(/^[0-9]{11}$/)) {
                isValid = false;
                showError(contact, 'Please enter a valid 11-digit contact number');
            }
            break;

        case 3: // Academic Information
            const previousSchool = document.getElementById('previous_school');
            const previousProgram = document.getElementById('previous_program');
            const desiredProgram = document.getElementById('desired_program');
            const yearLevel = document.getElementById('year_level');

            // Previous School validation (aligned with Previous Program logic)
            if (previousSchool && previousSchool.value) {
                if (previousSchool.value === 'Other') {
                    isValid = false;
                    showError(previousSchool, 'Please specify your previous school');
                }
            }
            // Previous Program validation
            if (previousProgram && previousProgram.value) {
                if (previousProgram.value === 'Other') {
                    // Should not happen, as select is replaced, but just in case
                    isValid = false;
                    showError(previousProgram, 'Please specify your previous program');
                }
            } else if (previousProgram && !previousProgram.value) {
                isValid = false;
                showError(previousProgram, 'Please select your previous program');
            }
            if (!desiredProgram.value) {
                isValid = false;
                showError(desiredProgram, 'Please select your desired program');
            }
            // Only validate year level if not ladderized
            if (document.getElementById('student_type').value !== 'ladderized') {
                if (!yearLevel.value || yearLevel.value < 1 || yearLevel.value > 5) {
                    isValid = false;
                    showError(yearLevel, 'Please enter a valid year level (1-5)');
                }
            }
            break;

        case 4: // Document Upload
            const tor = document.getElementById('tor');
            const schoolId = document.getElementById('school_id');
            const hasCopyGrades = document.getElementById('has_copy_grades');
            const copyGrades = document.getElementById('copy_grades');

            // Validate School ID (always required)
            if (!schoolId.files.length) {
                isValid = false;
                showError(schoolId, 'Please upload your School ID');
            }

            // Validate academic document based on checkbox selection
            if (hasCopyGrades.checked) {
                // If Copy of Grades is selected, validate that file
                if (!copyGrades.files.length) {
                    isValid = false;
                    showError(copyGrades, 'Please upload your Copy of Grades');
                }
            } else {
                // If TOR is selected (default), validate TOR file
                if (!tor.files.length) {
                    isValid = false;
                    showError(tor, 'Please upload your Transcript of Records');
                }
            }
            break;
    }
    
    return isValid;
}

// Form Submission Functions
async function pingServer() {
    try {
        const response = await fetch('qualiexam_registerBack.php?ping=1', {
            method: 'HEAD',
            cache: 'no-store',
            headers: {
                'Cache-Control': 'no-cache'
            }
        });
        return response.ok;
    } catch (error) {
        console.error('Server ping failed:', error);
        return false;
    }
}

function submitForm(event) {
    event.preventDefault();
    
    // Validate the final document upload step (index 4)
    if (!validateCurrentStep(4)) {
        return false; // Don't proceed if validation fails
    }
    
    // Disable submit button to prevent multiple submissions
    const submitButtons = document.querySelectorAll('button[type="submit"]');
    submitButtons.forEach(button => {
        // Store original text for restoration later
        if (!button.hasAttribute('data-original-text')) {
            button.setAttribute('data-original-text', button.textContent.trim());
        }
        
        button.disabled = true;
        button.classList.add('processing');
        button.setAttribute('data-processing', 'true');
        
        // Update button text to show processing
        if (button.classList.contains('btn-submit')) {
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        } else {
            button.textContent = 'Processing...';
        }
    });
    
    // Clear any previous error messages
    const existingErrors = document.querySelectorAll('.error');
    existingErrors.forEach(error => error.remove());
    
    // Add a retry counter in case of failures
    window.submissionAttempts = window.submissionAttempts || 0;
    
    // Add a fallback if we've already tried multiple times
    if (window.submissionAttempts >= 2) {
        // Add a direct form submission as fallback
        console.log("Using direct form submission as fallback");
        const form = document.getElementById('multi-step-form');
        form.action = "qualiexam_registerBack.php";
        form.method = "POST";
        
        // Add a redirect URL as a hidden field
        const redirectField = document.createElement('input');
        redirectField.type = 'hidden';
        redirectField.name = 'redirect_on_success';
        redirectField.value = 'registration_success.php';
        form.appendChild(redirectField);
        
        // Let the form submit directly
        return true;
    }
    
    // Increment attempt counter
    window.submissionAttempts++;

    // Check if the server is accessible first
    pingServer().then(isServerAccessible => {
        if (isServerAccessible) {
            // IMPORTANT: Make sure the OCR processing and preview is shown first 
            // This function handles the OCR process and shows the OCR preview modal
            processOCR();
        } else {
            // If server is not accessible, show an error and enable the submit button
            const formSection = document.querySelector('.form-section');
            if (formSection) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error';
                errorDiv.innerHTML = `
                    <strong>Server Connection Error</strong><br>
                    Cannot connect to the server. Please check your internet connection and try again.<br>
                    <small>If the problem persists, please try the direct submission option below.</small>
                `;
                formSection.insertBefore(errorDiv, formSection.firstChild);
                
                // Add a direct submission button
                const directSubmitContainer = document.createElement('div');
                directSubmitContainer.className = 'direct-submit-container';
                directSubmitContainer.style.marginTop = '20px';
                directSubmitContainer.style.textAlign = 'center';
                directSubmitContainer.style.padding = '15px';
                directSubmitContainer.style.backgroundColor = '#f8f9fa';
                directSubmitContainer.style.borderRadius = '8px';
                
                const directSubmitButton = document.createElement('button');
                directSubmitButton.type = 'button';
                directSubmitButton.className = 'btn btn-next';
                directSubmitButton.textContent = 'Try Direct Submission';
                directSubmitButton.style.backgroundColor = '#5a2930';
                directSubmitButton.style.margin = '0 auto';
                
                directSubmitButton.addEventListener('click', function() {
                    const form = document.getElementById('multi-step-form');
                    form.action = "qualiexam_registerBack.php";
                    form.method = "POST";
                    
                    // Add action and redirect fields
                    let actionField = form.querySelector('input[name="action"]');
                    if (!actionField) {
                        actionField = document.createElement('input');
                        actionField.type = 'hidden';
                        actionField.name = 'action';
                        form.appendChild(actionField);
                    }
                    actionField.value = 'final_submit';
                    
                    const redirectField = document.createElement('input');
                    redirectField.type = 'hidden';
                    redirectField.name = 'redirect_on_success';
                    redirectField.value = 'registration_success.php';
                    form.appendChild(redirectField);
                    
                    // Submit the form directly
                    form.submit();
                });
                
                directSubmitContainer.appendChild(directSubmitButton);
                
                // Add instructions
                const instructions = document.createElement('p');
                instructions.style.marginTop = '10px';
                instructions.style.fontSize = '0.9rem';
                instructions.innerHTML = 'This will bypass the AJAX submission process and submit your form directly.';
                directSubmitContainer.appendChild(instructions);
                
                formSection.querySelector('.buttons').appendChild(directSubmitContainer);
            }
            
            // Re-enable submit buttons
            submitButtons.forEach(button => {
                button.disabled = false;
                button.classList.remove('processing');
                button.removeAttribute('data-processing');
                
                // Restore original text
                const originalText = button.getAttribute('data-original-text');
                if (originalText && button.classList.contains('btn-submit')) {
                    button.innerHTML = '<i class="fas fa-check"></i> ' + originalText;
                } else if (originalText) {
                    button.textContent = originalText;
                } else {
                    button.textContent = 'Submit Application';
                }
            });
        }
    }).catch(() => {
        // Handle network errors
        // Reset button states
        submitButtons.forEach(button => {
            button.disabled = false;
            button.classList.remove('processing');
            button.removeAttribute('data-processing');
            
            // Restore original text
            const originalText = button.getAttribute('data-original-text');
            if (originalText && button.classList.contains('btn-submit')) {
                button.innerHTML = '<i class="fas fa-check"></i> ' + originalText;
            } else if (originalText) {
                button.textContent = originalText;
            } else {
                button.textContent = 'Submit Application';
            }
        });
        
        // Show error message
        const formSection = document.querySelector('.form-section');
        if (formSection) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error';
            errorDiv.innerHTML = `
                <strong>Network Error</strong><br>
                Cannot connect to the server. Please check your internet connection and try again.
            `;
            formSection.insertBefore(errorDiv, formSection.firstChild);
        }
    });
    
    return false; // Prevent default form submission
}

// Utility functions for smooth UI transitions
function showElement(element, duration = 300) {
    if (!element) return;
    
    // First set display to ensure element is in the DOM
    element.style.display = element.tagName.toLowerCase() === 'div' ? 'block' : 'flex';
    
    // Force a reflow to ensure transition will work
    void element.offsetWidth;
    
    // Then transition opacity
    element.style.opacity = '0';
    setTimeout(() => {
        element.style.transition = `opacity ${duration}ms ease`;
        element.style.opacity = '1';
    }, 10);
}

function hideElement(element, duration = 300) {
    if (!element) return;
    
    // Transition to invisible
    element.style.transition = `opacity ${duration}ms ease`;
    element.style.opacity = '0';
    
    // Then remove from DOM after transition completes
    setTimeout(() => {
        element.style.display = 'none';
    }, duration);
}

// Updated processOCR function with better transitions
async function processOCR() {
    try {
        // Get necessary elements
        const form = document.getElementById('multi-step-form');
        const mainContent = document.querySelector('.main-content');
        const formSection = document.querySelector('.form-section');
        const ocrModal = document.getElementById('ocrPreviewModal');
        const ocrContent = document.getElementById('ocrResultsContent');
        const loadingOverlay = document.getElementById('ocr-loading-overlay');
        
        // First, disable submit button to prevent multiple submissions
        const submitButtons = document.querySelectorAll('button[type="submit"]');
        submitButtons.forEach(button => {
            // Store original text for restoration later
            if (!button.hasAttribute('data-original-text')) {
                button.setAttribute('data-original-text', button.textContent.trim());
            }
            
            button.disabled = true;
            button.classList.add('processing');
            button.setAttribute('data-processing', 'true');
            
            // Update button text to show processing
            if (button.classList.contains('btn-submit')) {
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            } else {
                button.textContent = 'Processing...';
            }
        });

        // Store important containers to restore them later
        window.formContainers = {
            mainContent: mainContent,
            formSection: formSection,
            form: form
        };
        
        // Dim the form but keep it visible
        if (formSection) {
            formSection.classList.add('dimmed');
            formSection.style.display = 'block';
            formSection.style.visibility = 'visible';
        }
        
        // Make sure ocr modal is initially hidden
        if (ocrModal) {
            ocrModal.style.display = 'none';
            ocrModal.style.visibility = 'hidden';
            ocrModal.style.opacity = '0';
        }
        
        // First, show only the loading overlay with smooth transition
        if (loadingOverlay) {
            loadingOverlay.style.display = 'flex';
            loadingOverlay.style.visibility = 'visible';
            loadingOverlay.style.opacity = '1';
            loadingOverlay.style.zIndex = '10000'; // Ensure it's on top
        }
        
        // Get form data
        const formData = new FormData(form);
        formData.append('action', 'process_ocr');
        
        // Add a delay to allow the UI to update before sending the request
        await new Promise(resolve => setTimeout(resolve, 100));
        
        // Create an AbortController with a timeout
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 90000); // 90 second timeout for OCR
        
        try {
            // Log to console to show we're attempting the OCR request
            console.log("Sending OCR request to server...");
            
            // Send the request to the server with improved options
            const response = await fetch('qualiexam_registerBack.php', {
                method: 'POST',
                body: formData,
                signal: controller.signal,
                headers: {
                    'Cache-Control': 'no-cache, no-store, must-revalidate',
                    'Pragma': 'no-cache',
                    'Expires': '0'
                },
                cache: 'no-store'
            });
            
            // Clear the timeout since we got a response
            clearTimeout(timeoutId);
            
            console.log("OCR response received, status:", response.status);
            
            // Now that we have a response, hide the loading overlay
            if (loadingOverlay) {
                loadingOverlay.style.opacity = '0';
                
                // Use a callback to ensure the loading overlay is fully hidden before showing the OCR modal
                setTimeout(() => {
                    loadingOverlay.style.display = 'none';
                    loadingOverlay.style.visibility = 'hidden';
                    
                    // IMPORTANT: Make sure the OCR preview modal is shown clearly to the user
                    if (ocrModal) {
                        console.log("Showing OCR preview modal to user");
                        ocrModal.style.display = 'block';
                        ocrModal.style.visibility = 'visible';
                        ocrModal.style.opacity = '1';
                        ocrModal.style.zIndex = '10000'; // Ensure it's on top
                    }
                }, 300);
            }
            
            // Check if response is ok before parsing JSON
            if (!response.ok) {
                throw new Error(`Server responded with status: ${response.status}`);
            }
            
            // Parse the response as JSON
            let result;
            try {
                result = await response.json();
            } catch (jsonError) {
                console.error('Failed to parse JSON response:', jsonError);
                throw new Error('Invalid response from server. Please try again.');
            }
            
            if (result.error) {
                throw new Error(result.error);
            }
            
            // Store subjects in hidden field
            document.getElementById('subjects_data').value = JSON.stringify(result.subjects);
            
            // Display the OCR results in a table
            let tableHtml = `
                <div class="table-controls">
                    <button type="button" class="btn-add-row" onclick="addTableRow()">
                        <i class="fas fa-plus"></i> Add Subject
                    </button>
                </div>
                <table class="grades-table">
                    <thead>
                        <tr>
                            <th>Subject Code</th>
                            <th>Description</th>
                            <th>Units</th>
                            <th>Grades</th>
                            <th width="50">Action</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            result.subjects.forEach(subject => {
                tableHtml += `
                    <tr>
                        <td contenteditable="true" class="editable-cell">${subject.subject_code || ''}</td>
                        <td contenteditable="true" class="editable-cell">${subject.subject_description || ''}</td>
                        <td contenteditable="true" class="editable-cell">${subject.units || ''}</td>
                        <td contenteditable="true" class="editable-cell">${subject.Grades || ''}</td>
                        <td class="action-cell">
                            <button type="button" class="delete-row-btn" onclick="deleteTableRow(this)">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            tableHtml += '</tbody></table>';
            ocrContent.innerHTML = tableHtml;
            
            // Make all cells editable
            const editableCells = ocrContent.querySelectorAll('.editable-cell');
            editableCells.forEach(cell => {
                makeTableCellEditable(cell);
            });
            
            // Update the subjects data immediately
            updateSubjectsData();
            
        } catch (error) {
            // Check if this was a timeout error
            if (error.name === 'AbortError') {
                // This is a timeout, but the OCR process might still be running
                // Hide loading overlay
                if (loadingOverlay) {
                    loadingOverlay.style.opacity = '0';
                    setTimeout(() => {
                        loadingOverlay.style.display = 'none';
                        loadingOverlay.style.visibility = 'hidden';
                        
                        // Show the OCR modal with error message
                        if (ocrModal) {
                            ocrModal.style.display = 'block';
                            ocrModal.style.visibility = 'visible';
                            ocrModal.style.opacity = '1';
                            ocrModal.style.zIndex = '10000';
                        }
                    }, 300);
                }

                // Show the modal with error message
                if (ocrContent) {
                    ocrContent.innerHTML = `
                        <div class="info-message" style="padding:15px; background-color:#d1ecf1; color:#0c5460; border-radius:5px; margin-bottom:20px; border-left:4px solid #0c5460;">
                            <strong>OCR Processing is taking longer than expected</strong><br>
                            Your document is complex and is taking longer to process. You can:
                            <ul>
                                <li>Wait a bit longer for the process to complete</li>
                                <li>Cancel and try again with a clearer document image</li>
                                <li>Continue without OCR and enter your subjects manually</li>
                            </ul>
                        </div>
                        <div style="text-align:center; margin-top:20px;">
                            <button type="button" class="prev-btn" onclick="closeOCRPreview()">Cancel OCR</button>
                        </div>
                    `;
                }
                
                return;
            }
            
            // For other errors, proceed with standard error handling
            console.error('Error during OCR processing:', error);
            
            // Hide loading overlay
            if (loadingOverlay) {
                loadingOverlay.style.opacity = '0';
                setTimeout(() => {
                    loadingOverlay.style.display = 'none';
                    loadingOverlay.style.visibility = 'hidden';
                    
                    // Show the OCR modal with error message
                    if (ocrModal) {
                        ocrModal.style.display = 'block';
                        ocrModal.style.visibility = 'visible';
                        ocrModal.style.opacity = '1';
                        ocrModal.style.zIndex = '10000';
                    }
                }, 300);
            }
            
            const errorMessage = error.message || 'An error occurred during processing';
            
            ocrContent.innerHTML = `
                <div class="error-message" style="padding:15px; background-color:#f8d7da; color:#721c24; border-radius:5px; margin-bottom:20px; border-left:4px solid #dc3545;">
                    <strong>Error during OCR processing:</strong><br>
                    ${errorMessage}<br>
                    <small>If this error persists, please try refreshing the page or contact support.</small>
                </div>
                <div style="text-align:center; margin-top:20px;">
                    <button type="button" class="prev-btn" onclick="closeOCRPreview()">Go Back</button>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error during OCR processing setup:', error);
        
        const ocrContent = document.getElementById('ocrResultsContent');
        const loadingOverlay = document.getElementById('ocr-loading-overlay');
        const ocrModal = document.getElementById('ocrPreviewModal');
        
        // Hide loading overlay
        if (loadingOverlay) {
            loadingOverlay.style.opacity = '0';
            setTimeout(() => {
                loadingOverlay.style.display = 'none';
                loadingOverlay.style.visibility = 'hidden';
                
                // Show the OCR modal with error message
                if (ocrModal) {
                    ocrModal.style.display = 'block';
                    ocrModal.style.visibility = 'visible';
                    ocrModal.style.opacity = '1';
                    ocrModal.style.zIndex = '10000';
                }
            }, 300);
        }
        
        const errorMessage = error.message || 'An error occurred during processing';
        
        ocrContent.innerHTML = `
            <div class="error-message">
                <strong>Error during OCR processing:</strong><br>
                ${errorMessage}<br>
                <small>If this error persists, please try refreshing the page or contact support.</small>
            </div>
            <div style="text-align:center; margin-top:20px;">
                <button type="button" class="prev-btn" onclick="closeOCRPreview()">Cancel</button>
            </div>
        `;
    }
}

// Updated closeOCRPreview function to properly restore the form state
function closeOCRPreview() {
    // Get necessary elements
    const ocrModal = document.getElementById('ocrPreviewModal');
    const formSection = document.querySelector('.form-section');
    const submitButtons = document.querySelectorAll('button[type="submit"]');
    
    console.log("Closing OCR preview modal");
    
    // Hide the OCR preview modal with multiple approaches to ensure it's hidden
    if (ocrModal) {
        // Apply multiple techniques to ensure the modal is completely hidden
        ocrModal.style.display = 'none';
        ocrModal.style.visibility = 'hidden';
        ocrModal.style.opacity = '0';
        ocrModal.style.zIndex = '-1';
        ocrModal.setAttribute('aria-hidden', 'true');
        
        // Use !important to override any potential conflicting styles
        ocrModal.style.cssText = "display: none !important; visibility: hidden !important; opacity: 0 !important; z-index: -9999 !important;";
    }
    
    // Restore form section
    if (formSection) {
        formSection.classList.remove('dimmed', 'processing');
        formSection.style.display = 'block';
        formSection.style.visibility = 'visible';
        formSection.style.opacity = '1';
    }
    
    // Re-enable submit buttons
    submitButtons.forEach(button => {
        button.disabled = false;
        button.classList.remove('processing');
        button.removeAttribute('data-processing');
        
        // Restore original text
        const originalText = button.getAttribute('data-original-text');
        if (originalText && button.classList.contains('btn-submit')) {
            button.innerHTML = '<i class="fas fa-check"></i> ' + originalText;
        } else if (originalText) {
            button.textContent = originalText;
        } else {
            button.textContent = 'Submit Application';
        }
    });
    
    // Re-enable all inputs
    const inputs = document.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.disabled = false;
    });
    
    console.log("OCR preview modal closed, form state restored");
}

// Updated confirmAndSubmit function to ensure proper modal handling
async function confirmAndSubmit() {
    try {
        // Get necessary elements
        const ocrModal = document.getElementById('ocrPreviewModal');
        const confirmButton = document.querySelector('.modal-footer .nxt-btn');
        const cancelButton = document.querySelector('.modal-footer .prev-btn');
        const loadingOverlay = document.getElementById('submission-loading-overlay');
        const formSection = document.querySelector('.form-section');
        const mainContent = document.querySelector('.main-content');
        const contentWrapper = document.querySelector('.content-wrapper');
        
        // Make sure to update the subjects data with the latest edits
        updateSubjectsData();
        
        // Log that we're starting the final submission process
        console.log("Starting final submission process after OCR preview");
        
        // Disable buttons to prevent double submissions
        if (confirmButton) {
            confirmButton.disabled = true;
            confirmButton.textContent = 'Processing...';
        }
        if (cancelButton) {
            cancelButton.disabled = true;
        }
        
        // Completely hide the OCR modal with all possible hiding techniques
        if (ocrModal) {
            console.log("Hiding OCR preview modal for final submission");
            // Use multiple techniques to ensure the modal is hidden
            ocrModal.style.display = 'none';
            ocrModal.style.visibility = 'hidden';
            ocrModal.style.opacity = '0';
            ocrModal.style.zIndex = '-9999'; // Very low z-index to ensure it's beneath everything
            ocrModal.setAttribute('aria-hidden', 'true');
            ocrModal.classList.add('force-hidden');
            
            // Use !important to override any conflicting styles
            ocrModal.style.cssText = "display: none !important; visibility: hidden !important; opacity: 0 !important; z-index: -9999 !important;";
        }
        
        // Add a class to body to indicate form is being processed
        document.body.classList.add('form-processing');
        
        // Ensure form remains visible but dimmed during submission
        if (formSection) {
            formSection.classList.add('processing');
            formSection.style.display = 'block';
        }
        
        // Show loading overlay for final submission
        if (loadingOverlay) {
            loadingOverlay.style.display = 'flex';
            loadingOverlay.style.visibility = 'visible';
            loadingOverlay.style.opacity = '1';
            loadingOverlay.style.zIndex = '10000'; // Ensure it's on top
        }
        
        // Get the form and form data
        const form = document.getElementById('multi-step-form');
        const formData = new FormData(form);
        
        // Add the action parameter for final submission
        formData.append('action', 'final_submit');
        
        // Get the subjects data from the hidden field
        const subjectsData = document.getElementById('subjects_data').value;
        if (subjectsData) {
            formData.append('subjects', subjectsData);
        }
        
        // Add a short delay to allow UI updates before fetch
        await new Promise(resolve => setTimeout(resolve, 300));
        
        // Submit the form with the final action
        console.log("Sending final submission request to server");
        const response = await fetch('qualiexam_registerBack.php', {
            method: 'POST',
            body: formData
        });
        
        // Hide loading overlay
        if (loadingOverlay) {
            loadingOverlay.style.opacity = '0';
            setTimeout(() => {
                loadingOverlay.style.display = 'none';
                loadingOverlay.style.visibility = 'hidden';
            }, 300);
        }
        
        // Check if response is ok
        if (!response.ok) {
            throw new Error(`Server responded with status: ${response.status}`);
        }
        
        // Parse the response
        const result = await response.json();
        console.log("Received final submission response:", result);
        
        // Handle error
        if (result.error) {
            // Make sure the OCR preview modal is completely hidden
            if (ocrModal) {
                ocrModal.style.cssText = "display: none !important; visibility: hidden !important; opacity: 0 !important; z-index: -9999 !important;";
            }
            
            // Show error message in the form section
            if (formSection) {
                formSection.classList.remove('processing');
                formSection.style.display = 'block';
                
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.innerHTML = `
                    <strong>Registration Error:</strong><br>
                    ${result.error}<br>
                    <small>Please correct the information and try again.</small>
                `;
                
                formSection.insertBefore(errorDiv, formSection.firstChild);
                
                // Re-enable form controls
                const inputs = form.querySelectorAll('input, select, textarea, button');
                inputs.forEach(input => {
                    input.disabled = false;
                });
                
                // Reset the submit button
                const submitButton = form.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.textContent = 'Submit Application';
                    submitButton.disabled = false;
                }
            }
            
            // Also show error modal if it exists
            const errorModal = document.getElementById('registrationErrorModal');
            if (errorModal) {
                console.log("Showing error modal");
                // Ensure the error message is set
                const errorMessageElement = errorModal.querySelector('.error-message');
                if (errorMessageElement) {
                    errorMessageElement.textContent = result.error;
                }
                
                // Show the error modal
                errorModal.style.display = 'block';
                errorModal.style.visibility = 'visible';
                errorModal.style.opacity = '1';
                errorModal.style.zIndex = '10001'; // Higher than OCR modal
            }
            
            return;
        }
        
        // Handle success
        if (result.success) {
            console.log("Registration successful, preparing success UI");
            
            // Make sure the OCR preview modal is completely hidden
            if (ocrModal) {
                ocrModal.style.cssText = "display: none !important; visibility: hidden !important; opacity: 0 !important; z-index: -9999 !important;";
            }
            
            // Show success modal if it exists
            const successModal = document.getElementById('registrationSuccessModal');
            if (successModal) {
                console.log("Showing success modal");
                
                // Ensure reference ID is set in the modal if available
                if (result.reference_id) {
                    const referenceIdElement = successModal.querySelector('.reference-id');
                    if (referenceIdElement) {
                        referenceIdElement.textContent = result.reference_id;
                    }
                }
                
                // Show the success modal
                successModal.style.display = 'block';
                successModal.style.visibility = 'visible';
                successModal.style.opacity = '1';
                successModal.style.zIndex = '10001'; // Higher than OCR modal
            } else {
                console.log("Success modal not found, using redirect");
                // If success modal doesn't exist or redirect is provided, redirect
                if (result.redirect_url) {
                    window.location.href = result.redirect_url;
                }
            }
            
            return;
        }
        
        // If we get here, something unexpected happened
        console.log("Unexpected response:", result);
        throw new Error('Unexpected response from server');
        
    } catch (error) {
        console.error('Error during form submission:', error);
        
        // Get elements again in case they weren't captured in the try block
        const ocrModal = document.getElementById('ocrPreviewModal');
        const loadingOverlay = document.getElementById('submission-loading-overlay');
        const formSection = document.querySelector('.form-section');
        
        // Hide loading overlay
        if (loadingOverlay) {
            loadingOverlay.style.opacity = '0';
            setTimeout(() => {
                loadingOverlay.style.display = 'none';
                loadingOverlay.style.visibility = 'hidden';
            }, 300);
        }
        
        // Hide OCR modal
        if (ocrModal) {
            ocrModal.style.cssText = "display: none !important; visibility: hidden !important; opacity: 0 !important; z-index: -9999 !important;";
        }
        
        // Show error in form section
        if (formSection) {
            formSection.classList.remove('processing');
            formSection.style.display = 'block';
            
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.innerHTML = `
                <strong>Submission Error:</strong><br>
                ${error.message}<br>
                <small>Please try again or contact support if the problem persists.</small>
            `;
            
            formSection.insertBefore(errorDiv, formSection.firstChild);
            
            // Re-enable form controls
            const form = document.getElementById('multi-step-form');
            if (form) {
                const inputs = form.querySelectorAll('input, select, textarea, button');
                inputs.forEach(input => {
                    input.disabled = false;
                });
                
                // Reset the submit button
                const submitButton = form.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.textContent = 'Submit Application';
                    submitButton.disabled = false;
                }
            }
        }
        
        // Also show error modal if it exists
        const errorModal = document.getElementById('registrationErrorModal');
        if (errorModal) {
            // Ensure the error message is set
            const errorMessageElement = errorModal.querySelector('.error-message');
            if (errorMessageElement) {
                errorMessageElement.textContent = error.message;
            }
            
            // Show the error modal
            errorModal.style.display = 'block';
            errorModal.style.visibility = 'visible';
            errorModal.style.opacity = '1';
            errorModal.style.zIndex = '10001'; // Higher than OCR modal
        }
    }
}

// Modal and Document Functions
function toggleCopyGradesUpload() {
    const checkbox = document.getElementById('has_copy_grades');
    const copyGradesField = document.getElementById('copy-grades-field');
    const copyGradesInput = document.getElementById('copy_grades');
    const torField = document.getElementById('tor-field');
    const torInput = document.getElementById('tor');
    
    if (checkbox.checked) {
        // Show Copy of Grades field and make it required
        copyGradesField.style.display = 'block';
        copyGradesInput.required = true;
        
        // Disable TOR field and make it not required
        torInput.disabled = true;
        torInput.required = false;
        torField.style.opacity = '0.5';
        torField.style.pointerEvents = 'none';
        
        // Clear TOR input value
        torInput.value = '';
        
        // Add visual indication that TOR is disabled
        const torLabel = torField.querySelector('label');
        if (torLabel && !torLabel.querySelector('.disabled-text')) {
            torLabel.innerHTML = torLabel.innerHTML + ' <span class="disabled-text" style="color: #999; font-style: italic;">(Disabled - using Copy of Grades instead)</span>';
        }
    } else {
        // Hide Copy of Grades field and make it not required
        copyGradesField.style.display = 'none';
        copyGradesInput.required = false;
        copyGradesInput.value = ''; // Clear the file input
        
        // Re-enable TOR field and make it required
        torInput.disabled = false;
        torInput.required = true;
        torField.style.opacity = '1';
        torField.style.pointerEvents = 'auto';
        
        // Remove visual indication
        const torLabel = torField.querySelector('label');
        const disabledText = torLabel?.querySelector('.disabled-text');
        if (disabledText) {
            disabledText.remove();
        }
    }
}

function handleEditableSelect(select) {
    if (select.value === 'Other') {
        const input = document.createElement('input');
        input.type = 'text';
        input.className = select.className;
        input.required = true;
        input.placeholder = 'Enter your previous school';
        input.name = select.name;
        input.id = select.id + '_custom'; // Ensures 'previous_school_custom'
        
        // Create a container for the input and a back button
        const parent = select.parentNode;
        const container = document.createElement('div');
        container.className = 'editable-select-container';
        
        // Add a back button
        const backBtn = document.createElement('button');
        backBtn.type = 'button';
        backBtn.className = 'back-to-select-btn';
        backBtn.innerHTML = '<i class="fas fa-arrow-left"></i>';
        backBtn.onclick = function() {
            if (container.parentNode) {
                container.parentNode.replaceChild(select, container);
                select.value = '';
            } else if (parent) {
                parent.appendChild(select);
                container.remove();
                select.value = '';
            }
        };
        
        container.appendChild(input);
        container.appendChild(backBtn);
        
        // Replace the select with the input container
        if (parent) {
            parent.replaceChild(container, select);
        }
        input.focus();
    }
}

// Add similar handler for previous_program
function handleEditableProgramSelect(select) {
    if (select.value === 'Other') {
        const input = document.createElement('input');
        input.type = 'text';
        input.className = select.className;
        input.required = true;
        input.placeholder = 'Enter your previous program';
        input.name = select.name;
        input.id = select.id + '_custom';

        // Save parent node reference
        const parent = select.parentNode;

        // Create a container for the input and a back button
        const container = document.createElement('div');
        container.className = 'editable-select-container';

        // Add a back button
        const backBtn = document.createElement('button');
        backBtn.type = 'button';
        backBtn.className = 'back-to-select-btn';
        backBtn.innerHTML = '<i class="fas fa-arrow-left"></i>';
        backBtn.onclick = function() {
            if (container.parentNode) {
                container.parentNode.replaceChild(select, container);
                select.value = '';
            } else if (parent) {
                parent.appendChild(select);
                container.remove();
                select.value = '';
            }
        };

        container.appendChild(input);
        container.appendChild(backBtn);

        // Replace the select with the input container
        if (parent) {
            parent.replaceChild(container, select);
        }
        input.focus();
    }
}

// Add these functions for editable OCR table

// Function to make a cell editable
function makeTableCellEditable(cell) {
    cell.setAttribute('contenteditable', 'true');
    cell.classList.add('editable-cell');
    cell.addEventListener('focus', function() {
        // Store original value in case we need to restore it
        if (!this.hasAttribute('data-original')) {
            this.setAttribute('data-original', this.textContent);
        }
    });
    
    cell.addEventListener('blur', function() {
        // Trim whitespace
        this.textContent = this.textContent.trim();
        
        // If empty, restore to original or set to placeholder
        if (!this.textContent) {
            const original = this.getAttribute('data-original');
            this.textContent = original || '';
        }
        
        // Update the hidden subjects data
        updateSubjectsData();
    });
    
    // Add key event listeners
    cell.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault(); // Prevent newline
            this.blur(); // Lose focus
        }
    });
}

// Function to add a new row to the table
function addTableRow() {
    const table = document.querySelector('.grades-table tbody');
    if (!table) return;
    
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
        <td contenteditable="true" class="editable-cell"></td>
        <td contenteditable="true" class="editable-cell"></td>
        <td contenteditable="true" class="editable-cell"></td>
        <td contenteditable="true" class="editable-cell"></td>
        <td class="action-cell">
            <button type="button" class="delete-row-btn" onclick="deleteTableRow(this)">
                <i class="fas fa-trash-alt"></i>
            </button>
        </td>
    `;
    
    table.appendChild(newRow);
    
    // Make all cells editable
    const cells = newRow.querySelectorAll('td[contenteditable="true"]');
    cells.forEach(cell => {
        makeTableCellEditable(cell);
    });
    
    // Focus on the first cell
    cells[0].focus();
    
    // Update the hidden subjects data
    updateSubjectsData();
}

// Function to delete a row from the table
function deleteTableRow(button) {
    const row = button.closest('tr');
    if (row) {
        // Optional: Add confirmation
        if (confirm('Are you sure you want to delete this subject?')) {
            row.remove();
            // Update the hidden subjects data
            updateSubjectsData();
        }
    }
}

// Function to update the hidden subjects data field
function updateSubjectsData() {
    const subjectsDataField = document.getElementById('subjects_data');
    if (!subjectsDataField) return;
    
    const table = document.querySelector('.grades-table tbody');
    if (!table) return;
    
    const rows = table.querySelectorAll('tr');
    const subjects = [];
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        // Skip if it's the action cell or if the row doesn't have enough cells
        if (cells.length < 4) return;
        
        subjects.push({
            subject_code: cells[0].textContent.trim(),
            subject_description: cells[1].textContent.trim(),
            units: cells[2].textContent.trim(),
            Grades: cells[3].textContent.trim()
        });
    });
    
    subjectsDataField.value = JSON.stringify(subjects);
}

// Initialization
document.addEventListener('DOMContentLoaded', function() {
    // Hide all modals and processing overlays on page load
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.style.display = 'none';
    });
    
    const loadingOverlay = document.getElementById('submission-loading-overlay');
    if (loadingOverlay) {
        loadingOverlay.style.display = 'none';
    }
    
    // Make sure all steps are initialized correctly
    const allSteps = document.querySelectorAll('.step');
    
    // Hide all steps initially
    allSteps.forEach(step => {
        step.classList.remove('active');
        step.style.display = 'none';
    });
    
    // Initialize the first step only
    const firstStep = document.querySelector('.step');
    if (firstStep) {
        firstStep.classList.add('active');
        firstStep.style.display = 'block';
    }
    
    // Make sure all next buttons have the validateStep function
    document.querySelectorAll('.btn-next').forEach(button => {
        if (!button.hasAttribute('onclick')) {
            button.setAttribute('onclick', 'validateStep()');
        }
    });
    
    // Add event listener for student type changes
    const studentTypeSelect = document.getElementById('student_type');
    if (studentTypeSelect) {
        studentTypeSelect.addEventListener('change', handleStudentTypeChange);
        // Also handle initial state
        handleStudentTypeChange();
    }
    
    // Add event listener for copy grades checkbox
    const hasCopyGrades = document.getElementById('has_copy_grades');
    if (hasCopyGrades) {
        hasCopyGrades.addEventListener('change', toggleCopyGradesUpload);
    }
    
    // Add event listener for previous_program editable select
    const previousProgramSelect = document.getElementById('previous_program');
    if (previousProgramSelect) {
        previousProgramSelect.addEventListener('change', function() {
            handleEditableProgramSelect(this);
        });
    }
});

// Setup on window load
window.onload = function() {
    // Make sure loading overlays are hidden on page load
    const ocrModal = document.getElementById('ocrPreviewModal');
    const loadingOverlay = document.getElementById('submission-loading-overlay');
    
    if (ocrModal) {
        ocrModal.style.display = 'none';
    }
    
    if (loadingOverlay) {
        loadingOverlay.style.display = 'none';
    }
};

// Call handleStudentTypeChange when the DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize student type handler
    const studentTypeSelect = document.getElementById('student_type');
    if (studentTypeSelect) {
        // Add change event listener
        studentTypeSelect.addEventListener('change', handleStudentTypeChange);
        
        // Call it once on page load
        handleStudentTypeChange();
    }
});

// --- Awesomplete integration for Previous Program ---
document.addEventListener('DOMContentLoaded', function() {
    var prevProgInput = document.getElementById('previous_program');
    var prevProgOther = document.getElementById('previous_program_other');
    if (prevProgInput && prevProgOther) {
        function toggleOtherField() {
            if (prevProgInput.value.trim().toLowerCase() === 'other (please specify)') {
                prevProgOther.style.display = '';
                prevProgOther.required = true;
            } else {
                prevProgOther.style.display = 'none';
                prevProgOther.required = false;
                prevProgOther.value = '';
            }
        }
        prevProgInput.addEventListener('awesomplete-selectcomplete', toggleOtherField);
        prevProgInput.addEventListener('input', toggleOtherField);
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
}); 