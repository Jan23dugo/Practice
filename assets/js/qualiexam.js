// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('multi-step-form');
    const stepIndicators = document.querySelectorAll('.step-indicator');
    const previousProgramSelect = document.getElementById("previous_program");
    const yearLevelField = document.getElementById("year-level-field");
    const modal = document.getElementById('ocrPreviewModal');
    
    // Add OCR Loading UI
    const ocrLoadingStyles = document.createElement('style');
    ocrLoadingStyles.textContent = `
        .ocr-loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.95);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 10000;
        }

        .ocr-loading-container {
            text-align: center;
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 90%;
        }

        .ocr-spinner {
            width: 70px;
            height: 70px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #800000;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        .ocr-loading-text {
            color: #333;
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }

        .ocr-loading-steps {
            text-align: left;
            margin: 1rem auto;
            max-width: 300px;
        }

        .ocr-step {
            display: flex;
            align-items: center;
            margin: 0.5rem 0;
            padding: 0.5rem;
            border-radius: 4px;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }

        .ocr-step.active {
            background: #fff3cd;
        }

        .ocr-step.completed {
            background: #d4edda;
        }

        .ocr-step-icon {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .ocr-step-text {
            flex-grow: 1;
            font-size: 0.9rem;
        }
    `;
    document.head.appendChild(ocrLoadingStyles);

    // Create and append OCR loading overlay
    const ocrLoadingOverlay = document.createElement('div');
    ocrLoadingOverlay.className = 'ocr-loading-overlay';
    ocrLoadingOverlay.innerHTML = `
        <div class="ocr-loading-container">
            <div class="ocr-spinner"></div>
            <div class="ocr-loading-text">Processing Your Documents</div>
            <div class="ocr-loading-steps">
                <div class="ocr-step" data-step="upload">
                    <div class="ocr-step-icon">📤</div>
                    <div class="ocr-step-text">Uploading documents...</div>
                </div>
                <div class="ocr-step" data-step="process">
                    <div class="ocr-step-icon">🔍</div>
                    <div class="ocr-step-text">Extracting information...</div>
                </div>
                <div class="ocr-step" data-step="analyze">
                    <div class="ocr-step-icon">📊</div>
                    <div class="ocr-step-text">Analyzing content...</div>
                </div>
                <div class="ocr-step" data-step="complete">
                    <div class="ocr-step-icon">✅</div>
                    <div class="ocr-step-text">Preparing results...</div>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(ocrLoadingOverlay);

    // Function to safely handle step transitions
    function handleStepTransition(fromStep, toStep) {
        try {
            const steps = document.querySelectorAll('.step');
            const currentIndex = Array.from(steps).indexOf(fromStep);
            const targetIndex = Array.from(steps).indexOf(toStep);
            
            // Update indicators
            stepIndicators[currentIndex].classList.remove('active');
            if (targetIndex > currentIndex) {
                stepIndicators[currentIndex].classList.add('completed');
            } else {
                stepIndicators[currentIndex].classList.remove('completed');
            }
            stepIndicators[targetIndex].classList.add('active');
            
            // Update step visibility
            fromStep.classList.remove('active');
            toStep.classList.add('active');
            
            // Scroll to top
            document.querySelector('.form-section').scrollIntoView({ behavior: 'smooth' });
            
            return true;
        } catch (error) {
            console.error('Step transition error:', error);
            return false;
        }
    }

    // Function to populate previous program select
    async function populatePreviousProgramSelect() {
        const select = document.getElementById('previous_program');
        if (!select) {
            console.error('Previous program select not found');
            return;
        }

        try {
            // Show loading state
            select.disabled = true;
            const defaultOption = select.querySelector('option[value=""]');
            if (defaultOption) {
                defaultOption.textContent = 'Loading programs...';
            }

            // Fetch programs from the server
            const response = await fetch('qualiexam_registerBack.php?action=get_programs');
            if (!response.ok) {
                throw new Error('Failed to fetch programs');
            }

            const programs = await response.json();
            
            // Clear existing options except the first one (placeholder)
            while (select.options.length > 1) {
                select.remove(1);
            }

            // Reset default option text
            if (defaultOption) {
                defaultOption.textContent = 'Select Previous Program';
            }

            // Add programs to select
            programs.forEach(program => {
                const option = document.createElement('option');
                option.value = program.program_id;
                option.textContent = program.program_name;
                select.appendChild(option);
            });
        } catch (error) {
            console.error('Error loading programs:', error);
            // Show retry button
            const formGroup = select.closest('.form-group');
            if (formGroup) {
                const retryButton = document.createElement('button');
                retryButton.type = 'button';
                retryButton.className = 'retry-btn';
                retryButton.innerHTML = '<i class="fas fa-sync-alt"></i> Retry';
                retryButton.onclick = populatePreviousProgramSelect;
                
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.textContent = 'Failed to load programs. ';
                errorDiv.appendChild(retryButton);
                
                // Remove any existing error message
                const existingError = formGroup.querySelector('.error-message');
                if (existingError) {
                    existingError.remove();
                }
                
                formGroup.appendChild(errorDiv);
            }
        } finally {
            // Re-enable select
            select.disabled = false;
        }
    }
    
    // Handle student type changes
    window.handleStudentTypeChange = function() {
        const studentType = document.getElementById('student_type').value;
        
        if (studentType === 'ladderized') {
            yearLevelField.style.display = 'none';
            previousProgramSelect.innerHTML = `
                <option value="Diploma in Information Communication Technology (DICT)" selected>
                    Diploma in Information Communication Technology (DICT)
                </option>
            `;
            previousProgramSelect.disabled = true;
        } else {
            yearLevelField.style.display = 'block';
            previousProgramSelect.disabled = false;
            
            // Only repopulate if it's not already populated
            if (previousProgramSelect.options.length <= 1) {
                populatePreviousProgramSelect();
            }
        }
    };

    // Function to validate each step
    window.validateStep = function() {
        const activeStep = document.querySelector('.step.active');
        const inputs = activeStep.querySelectorAll('input, select');
        let isValid = true;

        inputs.forEach(input => {
            // Skip year_level validation for ladderized students
            if (input.id === 'year_level' && 
                document.getElementById('student_type').value === 'ladderized') {
                return;
            }
            
            if (input.hasAttribute('required') && !input.checkValidity()) {
                isValid = false;
                input.reportValidity();
            }
        });

        if (isValid) {
            nextStep();
        }
    };

    // Next step navigation
    window.nextStep = function() {
        const currentStep = document.querySelector('.step.active');
        const nextStep = currentStep.nextElementSibling;

        if (nextStep && nextStep.classList.contains('step')) {
            handleStepTransition(currentStep, nextStep);
        }
    };

    // Previous step navigation
    window.prevStep = function() {
        const currentStep = document.querySelector('.step.active');
        const prevStep = currentStep.previousElementSibling;

        if (prevStep && prevStep.classList.contains('step')) {
            handleStepTransition(currentStep, prevStep);
        }
    };

    // Update the form submission handler
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        logToServer('form_submission_started');
        
        const formData = new FormData(form);
        const useGrades = document.getElementById('useGrades').checked;
        const academicFileInput = useGrades ? document.getElementById('academic_document_cog') : document.getElementById('academic_document_tor');
        const schoolIdFileInput = document.getElementById('school_id');
        
        // Validate file presence
        if (!academicFileInput.files[0] || !schoolIdFileInput.files[0]) {
            logToServer('document_validation_failed', {
                academic_doc: !!academicFileInput.files[0],
                school_id: !!schoolIdFileInput.files[0]
            });
            showError('Please select all required documents');
            return;
        }

        // Validate file types
        const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        const academicFile = academicFileInput.files[0];
        const schoolIdFile = schoolIdFileInput.files[0];

        if (!allowedTypes.includes(academicFile.type)) {
            showError(useGrades ? 'Copy of Grades must be a PDF, JPG, or PNG file' : 'Academic document must be a PDF, JPG, or PNG file');
            return;
        }

        if (!allowedTypes.includes(schoolIdFile.type)) {
            showError('School ID must be a PDF, JPG, or PNG file');
            return;
        }

        // Validate file sizes (5MB limit)
        const maxSize = 5 * 1024 * 1024; // 5MB in bytes
        if (academicFile.size > maxSize) {
            showError(useGrades ? 'Copy of Grades must be less than 5MB' : 'Academic document must be less than 5MB');
            return;
        }

        if (schoolIdFile.size > maxSize) {
            showError('School ID must be less than 5MB');
            return;
        }
        
        showLoading(true); // Show OCR loading
        updateOCRStep('upload');
        logToServer('ocr_process_started');
        
        // Add document type to formData
        formData.append('document_type', useGrades ? 'cog' : 'tor');
        formData.append('process_ocr', '1');
        
        try {
            const response = await fetch('qualiexam_registerBack.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error(`Server error: ${response.status}`);
            }
            
            const contentType = response.headers.get("content-type");
            if (!contentType || !contentType.includes("application/json")) {
                throw new Error("Invalid response format from server");
            }
            
            const jsonData = await response.json();
            logToServer('backend_response_received', {
                success: jsonData.success,
                has_subjects: !!jsonData.data?.subjects
            });
            
            if (!jsonData.success) {
                throw new Error(jsonData.error || 'OCR processing failed');
            }

            // Get student name and email from form data
            const firstName = formData.get('first_name') || '';
            const lastName = formData.get('last_name') || '';
            const fullName = `${firstName} ${lastName}`.trim();
            const email = formData.get('email') || '';

            // Store registration data in session storage for success page
            sessionStorage.setItem('registration_success', 'true');
            sessionStorage.setItem('reference_id', jsonData.reference_id);
            sessionStorage.setItem('student_name', fullName);
            sessionStorage.setItem('email', email);

            // Default redirect URL if none provided
            const defaultRedirect = '/Practice/registration_success.php';
            const redirectPath = jsonData.redirect || defaultRedirect;

            // Safely handle the redirect
            if (redirectPath) {
                window.location.href = redirectPath.startsWith('/') 
                    ? redirectPath 
                    : window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1) + redirectPath;
            } else {
                console.error('No redirect URL provided');
                window.location.href = defaultRedirect;
            }
        } catch (error) {
            hideLoading(true);
            logToServer('error_occurred', {
                error_message: error.message
            });
            console.error('Error:', error);
            showError(error.message || 'An error occurred while processing your documents.');
        }
    });

    // Function to display OCR results in the preview modal
    function displayOCRResults(subjects) {
        showOCRPreview(); // Show the modal first
        
        const loadingDiv = document.getElementById('loadingGrades');
        const gradesTable = document.getElementById('gradesTable');
        const tableBody = document.getElementById('gradesPreviewBody');
        
        if (!loadingDiv || !gradesTable || !tableBody) {
            console.error('Required elements not found');
            return;
        }
        
        // Show loading first
        loadingDiv.style.display = 'flex';
        gradesTable.style.display = 'none';
        
        // Simulate processing time (remove this in production)
        setTimeout(() => {
            // Hide loading and show table
            loadingDiv.style.display = 'none';
            gradesTable.style.display = 'table';
            
            // Clear existing rows
            tableBody.innerHTML = '';
            
            // Add each subject to the table
            subjects.forEach((subject, index) => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${subject.code || ''}</td>
                    <td>${subject.name || ''}</td>
                    <td>${subject.grade || ''}</td>
                    <td>${subject.units || ''}</td>
                `;
                tableBody.appendChild(row);
            });
        }, 1000); // Simulate 1 second loading time
    }

    // Function to create a new grade row
    function createGradeRow(subject) {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td contenteditable="true">${subject.subject_code}</td>
            <td contenteditable="true">${subject.subject_description}</td>
            <td contenteditable="true">${subject.units}</td>
            <td contenteditable="true">${subject.grade}</td>
            <td class="action-cell">
                <button type="button" class="delete-row-btn" onclick="deleteGradeRow(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        
        // Add input validation for units and grade
        const unitsCell = row.cells[2];
        const gradeCell = row.cells[3];
        
        unitsCell.addEventListener('input', function() {
            this.textContent = this.textContent.replace(/[^0-9.]/g, '');
            if (this.textContent.length > 4) {
                this.textContent = this.textContent.slice(0, 4);
            }
            const value = parseFloat(this.textContent);
            if (value && (value < 1 || value > 6)) {
                this.style.color = '#dc3545';
            } else {
                this.style.color = '';
            }
        });
        
        gradeCell.addEventListener('input', function() {
            this.textContent = this.textContent.replace(/[^0-9.]/g, '');
            if (this.textContent.length > 4) {
                this.textContent = this.textContent.slice(0, 4);
            }
            const value = parseFloat(this.textContent);
            if (value && (value < 1.00 || value > 5.00)) {
                this.style.color = '#dc3545';
            } else {
                this.style.color = '';
            }
        });
        
        return row;
    }

    // Make addNewGradeRow function globally accessible
    window.addNewGradeRow = function() {
        const tableBody = document.querySelector('#gradesPreviewBody');
        if (tableBody) {
            const row = createGradeRow();
            tableBody.appendChild(row);
            // Add fade-in animation
            row.style.opacity = '0';
            row.style.transition = 'opacity 0.3s ease';
            setTimeout(() => {
                row.style.opacity = '1';
            }, 50);
            
            // Focus on the first cell of the new row
            const firstCell = row.querySelector('td[contenteditable="true"]');
            if (firstCell) {
                firstCell.focus();
            }
        }
    };

    // Make deleteGradeRow function globally accessible
    window.deleteGradeRow = function(button) {
        const row = button.closest('tr');
        if (row) {
            // Add fade-out animation
            row.style.transition = 'opacity 0.3s ease';
            row.style.opacity = '0';
            setTimeout(() => {
                row.remove();
            }, 300);
        }
    };

    // Update the collectGradesData function to use the table in the OCR preview modal
    function collectGradesData() {
        const gradesTable = document.getElementById('gradesPreviewBody');
        const grades = [];
        let isValid = true;
        let error = '';

        if (!gradesTable) {
            return { isValid: false, error: 'Grades table not found' };
        }

        const rows = gradesTable.getElementsByTagName('tr');
        for (const row of rows) {
            const cells = row.getElementsByTagName('td');
            if (cells.length < 4) continue;

            const subject_code = cells[0].textContent.trim();
            const description = cells[1].textContent.trim();
            const units = cells[2].textContent.trim();
            const grade = cells[3].textContent.trim();

            // Validate required fields
            if (!subject_code || !description || !units || !grade) {
                isValid = false;
                error = 'All fields are required for each subject';
                break;
            }

            // Validate units (should be between 1 and 6)
            const unitsNum = parseFloat(units);
            if (isNaN(unitsNum) || unitsNum < 1 || unitsNum > 6) {
                isValid = false;
                error = `Invalid units value for ${subject_code}. Must be between 1 and 6`;
                break;
            }

            // Validate grade (should be between 1.00 and 5.00)
            const gradeNum = parseFloat(grade);
            if (isNaN(gradeNum) || gradeNum < 1.00 || gradeNum > 5.00) {
                isValid = false;
                error = `Invalid grade value for ${subject_code}. Must be between 1.00 and 5.00`;
                break;
            }

            grades.push({
                subject_code: subject_code,
                description: description,
                units: unitsNum,
                grade: gradeNum
            });
        }

        if (grades.length === 0) {
            return { isValid: false, error: 'No grades have been entered' };
        }

        return { isValid, grades, error };
    }

    // Update the confirmGrades function
    const confirmGradesBtn = document.getElementById('confirmGradesBtn');
    if (confirmGradesBtn) {
        // Remove all existing event listeners
        const newConfirmBtn = confirmGradesBtn.cloneNode(true);
        confirmGradesBtn.parentNode.replaceChild(newConfirmBtn, confirmGradesBtn);
        
        // Add single event listener
        newConfirmBtn.addEventListener('click', async function() {
            try {
                const gradesData = collectGradesData();
                if (!gradesData.isValid) {
                    throw new Error(gradesData.error);
                }

                // Show loading
                showLoading();
                closeOCRPreview();

                // Get form data
                const formData = new FormData(document.getElementById('multi-step-form'));
                
                // Add registration data
                formData.append('confirm_registration', '1');
                formData.append('grades', JSON.stringify(gradesData.grades));
                formData.append('academic_document_path', sessionStorage.getItem('academic_document_path'));
                formData.append('school_id_path', sessionStorage.getItem('school_id_path'));
                formData.append('document_type', sessionStorage.getItem('document_type'));

                // Send to backend
                const response = await fetch('qualiexam_registerBack.php', {
                    method: 'POST',
                    body: formData
                });

                const jsonData = await response.json();
                
                if (!jsonData.success) {
                    throw new Error(jsonData.error || 'Registration failed. Please try again.');
                }

                // Get student name and email from form data
                const firstName = formData.get('first_name') || '';
                const lastName = formData.get('last_name') || '';
                const fullName = `${firstName} ${lastName}`.trim();
                const email = formData.get('email') || '';

                // Store registration data in session storage for success page
                sessionStorage.setItem('registration_success', 'true');
                sessionStorage.setItem('reference_id', jsonData.reference_id);
                sessionStorage.setItem('student_name', fullName);
                sessionStorage.setItem('email', email);

                // Default redirect URL if none provided
                const defaultRedirect = '/Practice/registration_success.php';
                const redirectPath = jsonData.redirect || defaultRedirect;

                // Safely handle the redirect
                if (redirectPath) {
                    window.location.href = redirectPath.startsWith('/') 
                        ? redirectPath 
                        : window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1) + redirectPath;
                } else {
                    console.error('No redirect URL provided');
                    window.location.href = defaultRedirect;
                }
            } catch (error) {
                console.error('Registration error:', error);
                await Swal.fire({
                    icon: 'error',
                    title: 'Registration Failed',
                    text: error.message || 'An unexpected error occurred during registration.',
                    confirmButtonText: 'OK'
                });
            } finally {
                hideLoading();
            }
        });
    }

    // Initialize the form
    populatePreviousProgramSelect();
    document.getElementById('student_type').addEventListener('change', handleStudentTypeChange);

    // Modal handling
    if (modal) {
        window.onclick = function(event) {
            if (event.target === modal) {
                closeOCRPreview();
            }
        }
    }

    // Add event listener for the checkbox
    const useGradesCheckbox = document.getElementById('useGrades');
    const torSection = document.getElementById('tor-upload-section');
    const cogSection = document.getElementById('cog-upload-section');
    const torInput = document.getElementById('academic_document_tor');
    const cogInput = document.getElementById('academic_document_cog');

    if (useGradesCheckbox) {
        useGradesCheckbox.addEventListener('change', function() {
            if (this.checked) {
                // Hide TOR section and show COG section
                torSection.style.display = 'none';
                cogSection.style.display = 'block';
                torInput.disabled = true;
                cogInput.disabled = false;
                // Clear TOR input
                torInput.value = '';
                // Add fade-in animation
                cogSection.classList.add('fade-in');
            } else {
                // Show TOR section and hide COG section
                cogSection.style.display = 'none';
                torSection.style.display = 'block';
                cogInput.disabled = true;
                torInput.disabled = false;
                // Clear COG input
                cogInput.value = '';
                // Add fade-in animation
                torSection.classList.add('fade-in');
            }
        });
    }

    // Remove fade-in class after animation completes
    [torSection, cogSection].forEach(section => {
        if (section) {
            section.addEventListener('animationend', function() {
                this.classList.remove('fade-in');
            });
        }
    });

    // Add event listeners for the buttons
    const cancelBtn = document.querySelector('.cancel-btn');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            closeOCRPreview();
        });
    }

    // Function to handle school selection changes
    window.handleSchoolChange = function(value) {
        const customField = document.getElementById('custom-university-field');
        const customInput = document.getElementById('custom_university');
        
        if (value === 'Other') {
            customField.style.display = 'block';
            customInput.required = true;
        } else {
            customField.style.display = 'none';
            customInput.required = false;
            customInput.value = ''; // Clear the input when hidden
        }
    };

    // Function to handle program selection changes
    window.handleProgramChange = function(value) {
        const customField = document.getElementById('custom-program-field');
        const customInput = document.getElementById('custom_program');
        
        if (value === 'Other') {
            customField.style.display = 'block';
            customInput.required = true;
        } else {
            customField.style.display = 'none';
            customInput.required = false;
            customInput.value = ''; // Clear the input when hidden
        }
    };

    // Show info modal on page load
    showModal();
    
    // Initialize student type handler
    const studentType = document.getElementById('student_type');
    if (studentType) {
        handleStudentTypeChange();
        studentType.addEventListener('change', handleStudentTypeChange);
    }

    // Initialize modals
    const infoModal = document.getElementById('infoModal');
    const ocrModal = document.getElementById('ocrPreviewModal');
    
    // Close modals when clicking outside
    window.onclick = function(event) {
        if (event.target === infoModal) {
            closeModal();
        } else if (event.target === ocrModal) {
            closeOCRPreview();
        }
    };

    // Close modals on escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            if (infoModal && infoModal.style.display === 'block') {
                closeModal();
            }
            if (ocrModal && ocrModal.style.display === 'block') {
                closeOCRPreview();
            }
        }
    });

    // Add the missing handleEditableSelect function
    function handleEditableSelect(select) {
        if (select.value === 'Other') {
            makeSelectEditable(select);
        }
    }

    // Add the missing makeSelectEditable function
    function makeSelectEditable(select) {
        // Create and insert a new custom option if it doesn't exist
        let customOption = select.querySelector('option[value="custom"]');
        if (!customOption) {
            customOption = document.createElement('option');
            customOption.value = 'custom';
            select.insertBefore(customOption, select.firstChild);
        }

        // Make the select editable
        select.classList.add('editing');
        
        // Create a temporary input field
        const input = document.createElement('input');
        input.type = 'text';
        input.className = select.className;
        input.classList.remove('editing');
        input.placeholder = 'Type your answer here';
        input.style.width = '100%';
        
        // Position the input over the select
        const selectRect = select.getBoundingClientRect();
        select.parentNode.style.position = 'relative';
        Object.assign(input.style, {
            position: 'absolute',
            top: '0',
            left: '0',
            height: `${selectRect.height}px`,
            zIndex: '1'
        });

        // Handle input changes
        input.addEventListener('input', function() {
            customOption.text = this.value;
            customOption.value = 'custom:' + this.value;
            select.value = customOption.value;
        });

        // Handle input blur
        input.addEventListener('blur', function() {
            if (!this.value.trim()) {
                select.value = '';
                select.classList.remove('editing');
                this.remove();
                return;
            }
            customOption.text = this.value;
            customOption.value = 'custom:' + this.value;
            select.value = customOption.value;
            this.remove();
            select.classList.remove('editing');
        });

        // Insert the input and focus it
        select.parentNode.insertBefore(input, select.nextSibling);
        input.focus();
    }

    // Add the missing submitForm function
    async function submitForm(e) {
        e.preventDefault();
        
        // First validate all fields
        if (!validateAllFields()) {
            return false;
        }
        
        try {
            await processOCR();
            return false; // Prevent form submission
        } catch (error) {
            console.error('Error:', error);
            showError('Error: ' + error.message);
            return false;
        }
    }

    // Add the missing logToServer function
    function logToServer(action, data = {}) {
        try {
            const logData = {
                action: action,
                timestamp: new Date().toISOString(),
                ...data
            };
            
            // Send log data to server
            fetch('log_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(logData)
            }).catch(error => console.error('Logging error:', error));
            
        } catch (error) {
            console.error('Error logging to server:', error);
        }
    }

    // Add event listener for form submission
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('multi-step-form');
        if (form) {
            form.addEventListener('submit', submitForm);
        }
    });
});

// Function to close the info modal
function closeModal() {
    const infoModal = document.getElementById('infoModal');
    if (infoModal) {
        infoModal.style.display = 'none';
    }
}

// Function to show the info modal
function showModal() {
    const infoModal = document.getElementById('infoModal');
    if (infoModal) {
        infoModal.style.display = 'block';
    }
}

// Function to show OCR preview modal
function showOCRPreview() {
    const modal = document.getElementById('ocrPreviewModal');
    if (modal) {
        modal.style.display = 'flex';
        setTimeout(() => {
            modal.classList.add('show');
        }, 10);
    }
}

// Function to close OCR preview modal
function closeOCRPreview() {
    const modal = document.getElementById('ocrPreviewModal');
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300); // Match the CSS transition duration
    }
}

// Function to handle student type change
function handleStudentTypeChange() {
    const studentType = document.getElementById('student_type');
    if (!studentType) {
        console.error('Student type select not found');
        return;
    }

    const value = studentType.value;
    const yearLevelField = document.getElementById('year_level_group');
    const previousProgramField = document.getElementById('previous_program_group');

    if (yearLevelField) {
        yearLevelField.style.display = value === 'ladderized' ? 'none' : 'block';
    }

    if (previousProgramField) {
        previousProgramField.style.display = value === 'shiftee' ? 'block' : 'none';
        if (value === 'shiftee') {
            populatePreviousProgramSelect();
        }
    }
}

// Function to process OCR response and show preview
function processOCRResponse(response) {
    if (!response || !response.subjects) {
        showError('Invalid OCR response format');
        return;
    }

    // Store the subjects data
    const subjectsInput = document.getElementById('subjects_data');
    if (subjectsInput) {
        subjectsInput.value = JSON.stringify(response.subjects);
    }
    
    // Show the preview modal with loading state
    showOCRPreview();
    
    const loadingDiv = document.getElementById('loadingGrades');
    const gradesTable = document.getElementById('gradesTable');
    const tableBody = document.getElementById('gradesPreviewBody');
    
    if (!loadingDiv || !gradesTable || !tableBody) {
        console.error('Required elements not found');
        return;
    }
    
    // Show loading first
    loadingDiv.style.display = 'flex';
    gradesTable.style.display = 'none';
    
    // Process and display the results
    setTimeout(() => {
        // Hide loading and show table
        loadingDiv.style.display = 'none';
        gradesTable.style.display = 'table';
        
        // Clear existing rows
        tableBody.innerHTML = '';
        
        // Add each subject to the table
        response.subjects.forEach(subject => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${subject.subject_code || ''}</td>
                <td>${subject.subject_description || ''}</td>
                <td>${subject.grade || ''}</td>
                <td>${subject.units || ''}</td>
            `;
            tableBody.appendChild(row);
        });
    }, 500);
}

// Function to show error messages
function showError(message) {
    console.error(message);
    alert(message);
}

// Loading indicator styles
const loadingStyles = document.createElement('style');
loadingStyles.textContent = `
    #loading {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.8);
        z-index: 9999;
        justify-content: center;
        align-items: center;
    }
    
    .loading-spinner {
        width: 50px;
        height: 50px;
        border: 5px solid #f3f3f3;
        border-top: 5px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .error-message {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        background-color: #ff4444;
        color: white;
        border-radius: 4px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        z-index: 9999;
        display: none;
        animation: slideIn 0.3s ease-out;
    }
    
    @keyframes slideIn {
        from { transform: translateX(100%); }
        to { transform: translateX(0); }
    }
`;
document.head.appendChild(loadingStyles);

// Create loading indicator
const loadingDiv = document.createElement('div');
loadingDiv.id = 'loading';
loadingDiv.innerHTML = '<div class="loading-spinner"></div>';
document.body.appendChild(loadingDiv);

// Create error message container
const errorDiv = document.createElement('div');
errorDiv.className = 'error-message';
document.body.appendChild(errorDiv);

// Function to show loading indicator
function showLoading(isOCR = false) {
    if (isOCR) {
        const overlay = document.querySelector('.ocr-loading-overlay');
        if (overlay) {
            overlay.style.display = 'flex';
            updateOCRStep('upload');
            
            // Simulate progress through steps
            setTimeout(() => updateOCRStep('process'), 2000);
            setTimeout(() => updateOCRStep('analyze'), 4000);
            setTimeout(() => updateOCRStep('complete'), 6000);
        }
    } else {
        const loadingDiv = document.getElementById('loading');
        if (loadingDiv) {
            loadingDiv.style.display = 'flex';
        }
    }
}

// Function to hide loading indicator
function hideLoading(isOCR = false) {
    if (isOCR) {
        const overlay = document.querySelector('.ocr-loading-overlay');
        if (overlay) {
            overlay.style.display = 'none';
            // Reset steps
            document.querySelectorAll('.ocr-step').forEach(step => {
                step.classList.remove('active', 'completed');
            });
        }
    } else {
        const loadingDiv = document.getElementById('loading');
        if (loadingDiv) {
            loadingDiv.style.display = 'none';
        }
    }
}

function updateOCRStep(currentStep) {
    const steps = ['upload', 'process', 'analyze', 'complete'];
    const currentIndex = steps.indexOf(currentStep);
    
    steps.forEach((step, index) => {
        const stepElement = document.querySelector(`.ocr-step[data-step="${step}"]`);
        if (stepElement) {
            if (index < currentIndex) {
                stepElement.classList.remove('active');
                stepElement.classList.add('completed');
            } else if (index === currentIndex) {
                stepElement.classList.add('active');
                stepElement.classList.remove('completed');
            } else {
                stepElement.classList.remove('active', 'completed');
            }
        }
    });
}

// Function to show error message
function showError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-alert';
    errorDiv.innerHTML = `
        <div class="error-content">
            <i class="fas fa-exclamation-circle"></i>
            <span>${message.split('\n').join('<br>')}</span>
            <button onclick="this.parentElement.parentElement.remove()">×</button>
        </div>
    `;
    document.body.appendChild(errorDiv);
    
    // Auto-remove after 10 seconds
    setTimeout(() => {
        if (errorDiv.parentElement) {
            errorDiv.remove();
        }
    }, 10000);
}

// Add these styles for better error visibility
const errorStyles = `
    .error-alert {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10000;
        animation: slideIn 0.5s ease-out;
        max-width: 400px;
        width: 90%;
    }
    
    .error-content {
        background-color: #fff;
        border-left: 4px solid #dc3545;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        padding: 1rem;
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        border-radius: 4px;
    }
    
    .error-content i {
        color: #dc3545;
        font-size: 1.2rem;
        margin-top: 0.2rem;
    }
    
    .error-content span {
        flex: 1;
        line-height: 1.4;
    }
    
    .error-content button {
        background: none;
        border: none;
        color: #666;
        cursor: pointer;
        font-size: 1.2rem;
        padding: 0;
        margin-left: 0.5rem;
    }
    
    .error-content button:hover {
        color: #333;
    }
    
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
`;

const styleSheet = document.createElement('style');
styleSheet.textContent = errorStyles;
document.head.appendChild(styleSheet);

async function validateGrades(grades) {
    const gradingSystem = document.getElementById('grading_system').value;
    if (!gradingSystem) {
        throw new Error('Please select a grading system first');
    }

    try {
        // Fetch grading rules from backend
        const response = await fetch(`get_grading_system.php?name=${encodeURIComponent(gradingSystem)}`);
        if (!response.ok) {
            throw new Error('Failed to fetch grading system rules');
        }
        
        const rules = await response.json();
        const invalidGrades = [];

        // Validate each grade
        for (const subject of grades) {
            const gradeValue = parseFloat(subject.grade);
            
            // Check if grade is a valid number
            if (isNaN(gradeValue)) {
                invalidGrades.push(`${subject.subject_code}: Grade must be a number`);
                continue;
            }
            
            // Check if units is a valid number between 1 and 6
            const units = parseFloat(subject.units);
            if (isNaN(units) || units < 1 || units > 6) {
                invalidGrades.push(`${subject.subject_code}: Units must be between 1 and 6`);
                continue;
            }
            
            // Check if grade is within valid range (1.00 to 5.00)
            if (gradeValue < 1.00 || gradeValue > 5.00) {
                invalidGrades.push(`${subject.subject_code}: Grade must be between 1.00 and 5.00`);
            }
        }

        if (invalidGrades.length > 0) {
            throw new Error('Invalid grades found:\n' + invalidGrades.join('\n'));
        }

        return true;
    } catch (error) {
        throw new Error(`Grade validation failed: ${error.message}`);
    }
}

// Add modal styles
const modalStyles = `
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.4);
    }

    .modal-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
        max-width: 800px;
        border-radius: 8px;
        position: relative;
    }

    .close-btn {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        position: absolute;
        right: 20px;
        top: 10px;
    }

    .close-btn:hover,
    .close-btn:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }

    .modal h2 {
        color: #800000;
        margin-bottom: 20px;
    }

    .modal p {
        margin-bottom: 15px;
        line-height: 1.5;
    }

    .modal ul {
        margin-left: 20px;
        margin-bottom: 20px;
    }

    .modal li {
        margin-bottom: 10px;
        line-height: 1.5;
    }

    .modal strong {
        color: #800000;
    }
`;

const modalStyleSheet = document.createElement('style');
modalStyleSheet.textContent = modalStyles;
document.head.appendChild(modalStyleSheet);

// Add this function to handle form data collection
function collectFormData() {
    const formData = {
        first_name: document.getElementById('first_name').value,
        middle_name: document.getElementById('middle_name').value,
        last_name: document.getElementById('last_name').value,
        gender: document.querySelector('input[name="gender"]:checked').value,
        dob: document.getElementById('dob').value,
        email: document.getElementById('email').value,
        contact_number: document.getElementById('contact_number').value,
        street: document.getElementById('street').value,
        student_type: document.getElementById('student_type').value,
        previous_school: document.getElementById('previous_school').value,
        year_level: document.getElementById('year_level').value,
        previous_program: document.getElementById('previous_program').value,
        desired_program: document.getElementById('desired_program').value
    };

    // Add file paths if they exist in the session
    const torPath = document.getElementById('tor_path')?.value;
    const schoolIdPath = document.getElementById('school_id_path')?.value;
    
    if (torPath) formData.tor_path = torPath;
    if (schoolIdPath) formData.school_id_path = schoolIdPath;

    return formData;
}

// Update the confirmRegistration function
async function confirmRegistration(formData) {
    try {
        showLoading('Confirming registration...');

        const response = await fetch('qualiexam_registerBack.php', {
            method: 'POST',
            body: formData
        });

        const jsonData = await response.json();

        if (!jsonData.success) {
            throw new Error(jsonData.error || 'Registration failed. Please try again.');
        }

        // Get student name and email from form data
        const firstName = formData.get('first_name') || '';
        const lastName = formData.get('last_name') || '';
        const fullName = `${firstName} ${lastName}`.trim();
        const email = formData.get('email') || '';

        // Store registration data in session storage for success page
        sessionStorage.setItem('registration_success', 'true');
        sessionStorage.setItem('reference_id', jsonData.reference_id);
        sessionStorage.setItem('student_name', fullName);
        sessionStorage.setItem('email', email);

        // Default redirect URL if none provided
        const defaultRedirect = '/Practice/registration_success.php';
        const redirectPath = jsonData.redirect || defaultRedirect;

        // Safely handle the redirect
        if (redirectPath) {
            window.location.href = redirectPath.startsWith('/') 
                ? redirectPath 
                : window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1) + redirectPath;
        } else {
            console.error('No redirect URL provided');
            window.location.href = defaultRedirect;
        }

    } catch (error) {
        console.error('Registration error:', error);
        hideLoading(true);
        await Swal.fire({
            icon: 'error',
            title: 'Registration Failed',
            text: error.message
        });
    }
}

// Add validation functions
function validateStudentData(data) {
    // Check if all required fields are present and not empty
    const requiredFields = [
        'first_name', 'last_name', 'gender', 'dob', 'email',
        'contact_number', 'street', 'student_type', 'previous_school',
        'year_level', 'previous_program', 'desired_program'
    ];

    return requiredFields.every(field => {
        const value = data[field]?.trim();
        return value !== undefined && value !== '';
    });
}

function validateGradesData(grades) {
    if (!Array.isArray(grades) || grades.length === 0) {
        return false;
    }

    return grades.every(grade => {
        return grade.code && grade.grade && grade.remarks;
    });
}

// Add helper functions for UI feedback
function showSuccess(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success';
    alertDiv.textContent = message;
    document.querySelector('.container').insertBefore(alertDiv, document.querySelector('.container').firstChild);
}

function showError(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-danger';
    alertDiv.textContent = message;
    document.querySelector('.container').insertBefore(alertDiv, document.querySelector('.container').firstChild);
}