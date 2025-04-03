// Define selectedQuestions in the global scope
let selectedQuestions = new Set();

// Alert modal functionality
function showAlert(message, type = 'info', callback = null) {
    const alertModal = document.getElementById('alert-modal');
    const alertMessage = document.getElementById('alert-message');
    const alertIcon = document.querySelector('.alert-icon');
    const alertIconSymbol = document.getElementById('alert-icon-symbol');
    const confirmBtn = document.getElementById('alert-confirm-btn');
    
    // Set the message
    alertMessage.textContent = message;
    
    // Set the appropriate icon and styles based on type
    alertIcon.className = 'alert-icon';
    
    switch(type) {
        case 'success':
            alertIcon.classList.add('success');
            alertIconSymbol.textContent = 'check_circle';
            break;
        case 'error':
            alertIcon.classList.add('error');
            alertIconSymbol.textContent = 'error';
            break;
        case 'warning':
            alertIcon.classList.add('warning');
            alertIconSymbol.textContent = 'warning';
            break;
        default: // info
            alertIconSymbol.textContent = 'info';
            break;
    }
    
    // Show the modal with animation
    alertModal.style.display = 'flex';
    setTimeout(() => {
        alertModal.classList.add('show');
    }, 10);
    
    // Handle the confirm button
    confirmBtn.onclick = function() {
        // Hide the modal with animation
        alertModal.classList.remove('show');
        setTimeout(() => {
            alertModal.style.display = 'none';
            // Call the callback if provided
            if (callback && typeof callback === 'function') {
                callback();
            }
        }, 300);
    };
    
    // Close when clicking outside
    alertModal.onclick = function(e) {
        if (e.target === alertModal) {
            confirmBtn.click();
        }
    };
}

// Define renderAnswers in the global scope
function renderAnswers(question) {
    if (question.question_type === 'programming') {
        return `
            <div class="programming-info">
                <div class="language">Language: ${question.language || 'Not specified'}</div>
                <div class="test-cases">Test Cases: ${question.test_case_count || 0}</div>
            </div>
        `;
    }

    if (!question.formatted_answers) return '';

    return `
        <div class="answer-choices">
            ${question.formatted_answers.map(answer => `
                <div class="answer-choice ${answer.is_correct ? 'correct' : 'incorrect'}">
                    <span class="choice-icon material-symbols-rounded">
                        ${answer.is_correct ? 'check_circle' : 'cancel'}
                    </span>
                    <span>${answer.text}</span>
                </div>
            `).join('')}
        </div>
    `;
}

// Define loadExamSettings function before it's used
function loadExamSettings(examId) {
    if (!examId) return;
    
    fetch(`api/get_exam_settings.php?exam_id=${examId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const settings = data.settings;
                console.log('Loaded settings:', settings);
                
                // Populate basic fields
                document.getElementById('quiz-name').value = settings.title || 'Untitled Quiz';
                document.getElementById('quiz-description').value = settings.description || '';
                if (settings.exam_type) {
                    document.getElementById('exam-type').value = settings.exam_type;
                }
                
                // Handle scheduling data
                const scheduleCheckbox = document.getElementById('schedule-exam');
                const scheduleContainer = document.getElementById('schedule-container');
                
                if (settings.is_scheduled == 1) {
                    // Check the scheduling checkbox
                    scheduleCheckbox.checked = true;
                    scheduleContainer.style.display = 'block';
                    
                    // Set the date and time fields if they exist
                    if (settings.scheduled_date) {
                        document.getElementById('scheduled_date').value = settings.scheduled_date;
                    }
                    
                    if (settings.scheduled_time) {
                        document.getElementById('scheduled_time').value = settings.scheduled_time;
                    }
                    
                    console.log('Scheduled date loaded:', settings.scheduled_date);
                    console.log('Scheduled time loaded:', settings.scheduled_time);
                } else {
                    scheduleCheckbox.checked = false;
                    scheduleContainer.style.display = 'none';
                }
                
                // Populate form fields with existing settings
                if ('randomize_questions' in settings) {
                    document.getElementById('randomize-questions').checked = settings.randomize_questions == 1;
                }
                if ('randomize_choices' in settings) {
                    document.getElementById('randomize-choices').checked = settings.randomize_choices == 1;
                }
                
                // Populate passing score settings
                if (settings.passing_score_type) {
                    document.getElementById('passing-score-type').value = settings.passing_score_type;
                    document.getElementById('passing-score').value = settings.passing_score;
                    
                    // Trigger change event to update UI
                    const event = new Event('change');
                    document.getElementById('passing-score-type').dispatchEvent(event);
                }
                
                // Handle cover image with simplified path
                if (settings.cover_image) {
                    console.log('Cover image path:', settings.cover_image);
                    
                    // Set the image source directly without path manipulation
                    const coverImagePreview = document.getElementById('cover-image-preview');
                    coverImagePreview.src = settings.cover_image;
                    coverImagePreview.style.display = 'block';
                    
                    document.getElementById('cover-image-text').textContent = 'Change cover image';
                    document.getElementById('remove-image-btn').style.display = 'inline-block';
                } else {
                    console.log('No cover image found');
                    document.getElementById('cover-image-preview').style.display = 'none';
                    document.getElementById('cover-image-text').textContent = 'Add cover image';
                    document.getElementById('remove-image-btn').style.display = 'none';
                }
                
                console.log('Loaded exam settings:', settings);
            }
        })
        .catch(error => {
            console.error('Error loading exam settings:', error);
        });
}

// Function to fetch questions from question bank
function fetchQuestions() {
    const questionBankList = document.getElementById('question-bank-list');
    const bankSearchInput = document.getElementById('bank-search-input');
    const questionTypeFilter = document.getElementById('question-type-filter');
    const categoryFilter = document.getElementById('category-filter');

    questionBankList.innerHTML = '<div class="loading-indicator">Loading questions...</div>';
    
    const searchParams = new URLSearchParams({
        search: bankSearchInput ? bankSearchInput.value : '',
        type: questionTypeFilter ? questionTypeFilter.value : '',
        category: categoryFilter ? categoryFilter.value : ''
    });

    fetch(`fetch_question_bank.php?${searchParams.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.questions) {
                displayQuestions(data.questions);
            } else {
                questionBankList.innerHTML = '<div class="no-questions">No questions found.</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            questionBankList.innerHTML = '<div class="error-message">Error loading questions. Please try again.</div>';
        });
}

// Function to update import button state
function updateImportButton() {
    const importQuestionsBtn = document.getElementById('import-questions-btn');
    if (importQuestionsBtn) {
        importQuestionsBtn.disabled = selectedQuestions.size === 0;
    }
}

// Function to import questions to exam
function importQuestionsToExam(examId, questionIds) {
    // Show loading state
    const importButton = document.getElementById('import-questions-btn');
    importButton.textContent = 'Importing...';
    importButton.disabled = true;

    // Make the API call to import questions
    fetch('import_questions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            exam_id: examId,
            question_ids: questionIds
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            showAlert('Questions imported successfully!', 'success', function() {
                // Reload the page to show the new questions
                window.location.reload();
            });
        } else {
            showAlert(data.message || 'Failed to import questions.', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred while importing questions.', 'error');
    })
    .finally(() => {
        // Reset button state
        importButton.textContent = 'Import Selected';
        importButton.disabled = false;
        // Close the modal
        const questionBankModal = document.getElementById('question-bank-modal');
        if (questionBankModal) {
            questionBankModal.style.display = 'none';
        }
    });
}

// Function to display questions in the question bank modal
function displayQuestions(questions) {
    const questionBankList = document.getElementById('question-bank-list');
    
    if (!questions.length) {
        questionBankList.innerHTML = '<div class="no-questions">No questions found matching your criteria.</div>';
        return;
    }

    questionBankList.innerHTML = questions.map(question => `
        <div class="question-card" data-question-id="${question.question_id}">
            <div class="question-header">
                <div class="checkbox-wrapper">
                    <input type="checkbox" 
                           id="question-${question.question_id}" 
                           value="${question.question_id}"
                           ${selectedQuestions.has(question.question_id) ? 'checked' : ''}>
                    <label for="question-${question.question_id}">
                        ${question.question_type.toUpperCase()}
                    </label>
                </div>
                <div class="points-setting">
                    <span class="material-symbols-rounded">star</span>
                    ${question.points} point${question.points > 1 ? 's' : ''}
                </div>
            </div>
            <div class="question-body">
                <div class="question-text">${question.question_text}</div>
                ${renderAnswers(question)}
            </div>
        </div>
    `).join('');

    // Add checkbox event listeners
    questionBankList.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const questionId = this.closest('.question-card').dataset.questionId;
            if (this.checked) {
                selectedQuestions.add(questionId);
            } else {
                selectedQuestions.delete(questionId);
            }
            updateImportButton();
        });
    });
}

function showQuestionBankModal(examId) {
    const questionBankModal = document.getElementById('question-bank-modal');
    const questionBankList = document.getElementById('question-bank-list');
    
    if (!questionBankModal || !questionBankList) {
        console.error('Question bank modal elements not found');
        return;
    }

    // Clear any previously selected questions
    selectedQuestions.clear();
    
    // Reset filters
    const bankSearchInput = document.getElementById('bank-search-input');
    const questionTypeFilter = document.getElementById('question-type-filter');
    const categoryFilter = document.getElementById('category-filter');
    
    if (bankSearchInput) bankSearchInput.value = '';
    if (questionTypeFilter) questionTypeFilter.value = '';
    if (categoryFilter) categoryFilter.value = '';

    // Show the modal
    questionBankModal.style.display = 'flex';
    
    // Initialize the question list with loading state
    questionBankList.innerHTML = '<div class="loading-indicator">Loading questions...</div>';
    
    // Update import button state
    const importButton = document.getElementById('import-questions-btn');
    if (importButton) {
        importButton.disabled = true;
    }
    
    // Fetch initial questions
    fetchQuestions();
}

document.addEventListener('DOMContentLoaded', function() {
    // Get modal elements
    const settingsModal = document.getElementById('settings-modal');
    const closeSettingsModal = document.getElementById('close-settings-modal');
    const publishBtn = document.querySelector('.btn-publish');
    const settingsBtn = document.querySelector('.btn-settings');
    const quizTitle = document.querySelector('.quiz-title');
    const confirmPublishBtn = document.getElementById('confirm-publish');
    
    // Question type modal elements
    const questionTypeModal = document.getElementById('question-type-modal');
    const closeQuestionTypeModal = document.getElementById('close-question-type-modal');
    const questionTypeCards = document.querySelectorAll('.question-type-card');
    const addQuestionBtn = document.getElementById('add-question-btn');
    
    // Form elements
    const quizNameInput = document.getElementById('quiz-name');
    const quizDescriptionInput = document.getElementById('quiz-description');
    const examTypeSelect = document.getElementById('exam-type');
    const nameError = document.getElementById('name-error');
    const examTypeError = document.getElementById('exam-type-error');
    const scheduleExamCheckbox = document.getElementById('schedule-exam');
    const scheduleContainer = document.getElementById('schedule-container');
    const scheduledDate = document.getElementById('scheduled_date');
    const scheduledTime = document.getElementById('scheduled_time');
    
    // Initially hide error messages
    nameError.style.display = 'none';
    examTypeError.style.display = 'none';
    
    // Show question type modal when Add question button is clicked
    if (addQuestionBtn) {
        addQuestionBtn.addEventListener('click', function() {
            // Get the exam ID from URL
            const urlParams = new URLSearchParams(window.location.search);
            const examId = urlParams.get('exam_id');
            
            if (examId) {
                // If we have an exam ID, just show the question type modal
                if (questionTypeModal) {
                    questionTypeModal.style.display = 'flex';
                }
            } else {
                // If no exam ID, create a temporary exam first, then show question type modal
                createTemporaryExam();
            }
        });
    }
    
    // Close question type modal when close button is clicked
    if (closeQuestionTypeModal) {
        closeQuestionTypeModal.addEventListener('click', function() {
            if (questionTypeModal) {
                questionTypeModal.style.display = 'none';
            }
        });
    }
    
    // Close question type modal when clicking outside
    if (questionTypeModal) {
        questionTypeModal.addEventListener('click', function(e) {
            if (e.target === questionTypeModal) {
                questionTypeModal.style.display = 'none';
            }
        });
    }
    
    // Handle question type selection
    if (questionTypeCards) {
        questionTypeCards.forEach(card => {
            card.addEventListener('click', function() {
                const questionType = this.getAttribute('data-type');
                const urlParams = new URLSearchParams(window.location.search);
                const examId = urlParams.get('exam_id');
                
                // Redirect to the appropriate question editor
                window.location.href = `${questionType}.php?exam_id=${examId}`;
            });
        });
    }
    
    // Edit button functionality
    const editButtons = document.querySelectorAll('.action-btn.edit');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const questionId = this.getAttribute('data-question-id');
            const questionCard = this.closest('.question-card');
            const questionType = questionCard.querySelector('.question-type').textContent.trim().toLowerCase();
            const examId = new URLSearchParams(window.location.search).get('exam_id');
            
            // Determine which editor to use based on question type
            let editorPage;
            switch (questionType) {
                case 'multiple choice':
                    editorPage = 'multiple_choice.php';
                    break;
                case 'true false':
                case 'true-false':
                    editorPage = 'true_false.php';
                    break;
                case 'programming':
                    editorPage = 'programming.php';
                    break;
                default:
                    editorPage = 'multiple_choice.php';
            }
            
            // Redirect to the appropriate editor with question ID
            window.location.href = `${editorPage}?exam_id=${examId}&question_id=${questionId}`;
        });
    });
    
    // Duplicate button functionality
    const duplicateButtons = document.querySelectorAll('.action-btn.duplicate');
    duplicateButtons.forEach(button => {
        button.addEventListener('click', function() {
            const questionId = this.getAttribute('data-question-id');
            duplicateQuestion(questionId);
        });
    });
    
    // Delete button functionality
    const deleteButtons = document.querySelectorAll('.action-btn.delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const questionId = this.getAttribute('data-question-id');
            confirmDeleteQuestion(questionId);
        });
    });
    
    // Sidebar item functionality
    const sidebarItems = document.querySelectorAll('.sidebar-item');
    sidebarItems.forEach(item => {
        item.addEventListener('click', function() {
            const itemText = this.textContent.trim();
            
            if (itemText.includes('Question Bank')) {
                // Get the exam ID from URL
                const urlParams = new URLSearchParams(window.location.search);
                const examId = urlParams.get('exam_id');
                
                if (!examId) {
                    alert('Please save the exam first before importing questions.');
                    return;
                }
                
                // Show question bank import modal
                showQuestionBankModal(examId);
            } else if (itemText.includes('Spreadsheet')) {
                // Show import modal (would need to be implemented)
                alert(`Import from ${itemText} clicked`);
            } else if (itemText.includes('Time') || itemText.includes('Points')) {
                // Show bulk update modal (would need to be implemented)
                alert(`Bulk update ${itemText} clicked`);
            }
        });
    });
    
    // Toggle schedule container visibility
    scheduleExamCheckbox.addEventListener('change', function() {
        scheduleContainer.style.display = this.checked ? 'block' : 'none';
        
        // Reset fields if unchecked
        if (!this.checked) {
            scheduledDate.value = '';
            scheduledTime.value = '';
        }
    });
    
    // Publish button click handler - Show settings modal
    if (publishBtn) {
    publishBtn.addEventListener('click', function() {
            // Show settings modal
            settingsModal.style.display = 'flex';
            
            // Load exam settings if exam_id exists
        const urlParams = new URLSearchParams(window.location.search);
        const examId = urlParams.get('exam_id');
        
        if (examId) {
            loadExamSettings(examId);
        }
    });
    }
    
    // Settings button click handler
    if (settingsBtn) {
    settingsBtn.addEventListener('click', function() {
            // Show settings modal
            settingsModal.style.display = 'flex';
            
            // Load exam settings if exam_id exists
        const urlParams = new URLSearchParams(window.location.search);
        const examId = urlParams.get('exam_id');
        
        if (examId) {
            loadExamSettings(examId);
        }
        });
    }
    
    // Close settings modal when close button is clicked
    if (closeSettingsModal) {
    closeSettingsModal.addEventListener('click', function() {
        settingsModal.style.display = 'none';
    });
    }
    
    // Close settings modal when clicking outside
    if (settingsModal) {
    settingsModal.addEventListener('click', function(e) {
        if (e.target === settingsModal) {
            settingsModal.style.display = 'none';
        }
    });
    }
    
    // Show modal when quiz title is clicked
    quizTitle.addEventListener('click', function(e) {
        e.preventDefault(); // Prevent navigation
        settingsModal.style.display = 'flex';
    });
    
    // Handle form submission
    const examForm = document.getElementById('examForm');
    if (examForm) {
        examForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
            // Validate the form
            let isValid = true;
            const quizName = document.getElementById('quiz-name').value;
            const examType = document.getElementById('exam-type').value;
            
            // Name validation
            if (quizName.length < 4) {
                document.getElementById('name-error').style.display = 'flex';
                isValid = false;
            } else {
                document.getElementById('name-error').style.display = 'none';
            }
            
            // Exam type validation
            if (!examType) {
                document.getElementById('exam-type-error').style.display = 'flex';
                isValid = false;
            } else {
                document.getElementById('exam-type-error').style.display = 'none';
            }
            
            if (!isValid) {
                return false;
            }
            
            // Close the settings modal
            document.getElementById('settings-modal').style.display = 'none';
            
            // Show loading alert
            showAlert('Saving exam, please wait...', 'info');
            
            // Submit the form with AJAX
        const formData = new FormData(this);
        
            fetch('save_exam.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    showAlert(data.message, 'success', function() {
                        // Update URL if this is a new exam
                const urlParams = new URLSearchParams(window.location.search);
                        const isNewExam = urlParams.get('new');
                        
                        if (isNewExam && data.exam_id) {
                            // Replace URL without reloading the page
                            const newUrl = window.location.pathname + '?exam_id=' + data.exam_id;
                            window.history.replaceState({}, document.title, newUrl);
                            
                            // Update the quiz title in the header if needed
                            const quizTitle = document.querySelector('.quiz-title');
                            if (quizTitle) {
                                quizTitle.textContent = quizName;
                            }
                        }
                    });
            } else {
                    // Show error message
                    showAlert(data.message || 'An error occurred while saving the exam.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred while saving the exam.', 'error');
            });
            
            return false;
        });
    }
    
    // Update quiz name as user types
    quizNameInput.addEventListener('input', function() {
        if (this.value.length >= 4) {
            nameError.style.display = 'none';
        }
    });
    
    // Update exam type error when selection changes
    examTypeSelect.addEventListener('change', function() {
        if (this.value !== '' && this.value !== null) {
            examTypeError.style.display = 'none';
        }
    });

    // Get the preview button
    const previewBtn = document.querySelector('.btn-preview');

    // Add event listener for preview button only if it exists
    if (previewBtn) {
        previewBtn.addEventListener('click', function() {
            // Get the exam ID from URL
            const urlParams = new URLSearchParams(window.location.search);
            const examId = urlParams.get('exam_id');
            
            if (!examId) {
                alert('Please save the exam first before previewing.');
                return;
            }
            
            // Create a preview modal
            createPreviewModal(examId);
        });
    }

    // Function to create a temporary exam
    function createTemporaryExam() {
        // Show a loading indicator
        addQuestionBtn.innerHTML = '<span class="material-symbols-rounded">hourglass_empty</span> Creating...';
        addQuestionBtn.disabled = true;
        
        // Create FormData with default values
        const formData = new FormData();
        formData.append('quiz-name', 'Untitled Quiz');
        formData.append('quiz-description', '');
        formData.append('exam-type', 'tech'); // Default to tech type
        formData.append('duration', '60'); // Add a default duration
        
        // Submit form using fetch API
        fetch('save_exam.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update URL with new exam ID without refreshing the page
                const newUrl = `quiz_editor.php?exam_id=${data.exam_id}`;
                window.history.pushState({ path: newUrl }, '', newUrl);
                
                // Update the quiz title
                document.querySelector('.quiz-title').textContent = 'Untitled Quiz';
                
                // Show the question type modal
                questionTypeModal.style.display = 'flex';
                
                // Show a success message
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-success';
                alertDiv.style.padding = '10px 15px';
                alertDiv.style.marginBottom = '15px';
                alertDiv.style.backgroundColor = '#d4edda';
                alertDiv.style.color = '#155724';
                alertDiv.style.borderRadius = '4px';
                alertDiv.textContent = 'Exam created successfully! You can now add questions.';
                
                // Insert the alert at the top of the questions panel
                const questionsPanel = document.querySelector('.questions-panel');
                questionsPanel.insertBefore(alertDiv, questionsPanel.firstChild);
                
                // Remove the alert after 5 seconds
                setTimeout(() => {
                    alertDiv.remove();
                }, 5000);
            } else {
                alert('Error creating exam: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('There was a problem creating the exam. Please try again.');
        })
        .finally(() => {
            // Restore button state
            addQuestionBtn.innerHTML = '<span class="material-symbols-rounded">add</span> Add question';
            addQuestionBtn.disabled = false;
        });
    }

    // Question Bank functionality
    const questionBankModal = document.getElementById('question-bank-modal');
    const importFromQuestionBank = document.getElementById('import-from-question-bank');
    const bankSearchInput = document.getElementById('bank-search-input');
    const bankSearchButton = document.getElementById('bank-search-button');
    const questionTypeFilter = document.getElementById('question-type-filter');
    const categoryFilter = document.getElementById('category-filter');
    const questionBankList = document.getElementById('question-bank-list');
    const importQuestionsBtn = document.getElementById('import-questions-btn');

    // Get all close buttons in the question bank modal
    const closeButtons = questionBankModal ? questionBankModal.querySelectorAll('.close-modal, #close-question-bank-modal') : [];

    // Add click event listeners to all close buttons
    closeButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            closeQuestionBankModal();
        });
    });

    // Close modal when clicking outside
    if (questionBankModal) {
        questionBankModal.addEventListener('click', function(e) {
            if (e.target === questionBankModal) {
                closeQuestionBankModal();
            }
        });
    }

    // Function to close question bank modal
    function closeQuestionBankModal() {
        if (questionBankModal) {
            questionBankModal.style.display = 'none';
            selectedQuestions.clear();
            updateImportButton();
            
            // Reset filters
            if (bankSearchInput) bankSearchInput.value = '';
            if (questionTypeFilter) questionTypeFilter.value = '';
            if (categoryFilter) categoryFilter.value = '';
        }
    }

    // Handle search and filters
    if (bankSearchButton) {
        bankSearchButton.addEventListener('click', fetchQuestions);
    }
    if (questionTypeFilter) {
        questionTypeFilter.addEventListener('change', fetchQuestions);
    }
    if (categoryFilter) {
        categoryFilter.addEventListener('change', fetchQuestions);
    }

    // Handle import button click
    document.getElementById('import-questions-btn').addEventListener('click', function() {
        // Clear any previous selections to avoid duplicates
        selectedQuestions = new Set(selectedQuestions);
        
        if (selectedQuestions.size === 0) {
            alert('Please select at least one question to import');
            return;
        }

        // Get the exam ID from the URL
        const urlParams = new URLSearchParams(window.location.search);
        const examId = urlParams.get('exam_id');

        if (!examId) {
            alert('Invalid exam ID');
            return;
        }

        // Convert Set to Array for the selected question IDs
        const questionIds = Array.from(selectedQuestions);
        console.log('Selected question IDs:', questionIds); // Debug log
        
        // Call the import function
        importQuestionsToExam(examId, questionIds);
    });

    // Auto Generate Questions functionality
    const autoGenerateBtn = document.getElementById('auto-generate-questions');
    const autoGenerateModal = document.getElementById('auto-generate-modal');
    const closeAutoGenerateModal = document.getElementById('close-auto-generate-modal');
    const cancelAutoGenerateBtn = document.getElementById('cancel-auto-generate-btn');
    const confirmAutoGenerateBtn = document.getElementById('confirm-auto-generate-btn');
    const autoGenerateForm = document.getElementById('auto-generate-form');
    const questionTypeFilters = document.querySelectorAll('.question-type-filter');
    
    // Populate categories in the dropdown
    function populateCategories() {
        const categorySelect = document.getElementById('category-select');
        
        // Clear existing options except the first one
        while (categorySelect.options.length > 1) {
            categorySelect.remove(1);
        }
        
        // Example of how to populate categories from an API
        fetch('api/get_categories.php')
            .then(response => response.json())
            .then(data => {
                data.forEach(category => {
                    const option = document.createElement('option');
                    option.value = category.id;
                    option.textContent = category.name;
                    categorySelect.appendChild(option);
                });
            })
            .catch(error => console.error('Error loading categories:', error));
    }
    
    // Function to fetch question counts
    function fetchQuestionCounts() {
        // Show loading state
        document.querySelector('.stats-loading').style.display = 'block';
        document.querySelector('.stats-content').style.display = 'none';
        
        // Get selected question types
        const selectedTypes = Array.from(document.querySelectorAll('.question-type-filter:checked'))
            .map(checkbox => checkbox.value);
        
        // Get selected category
        const selectedCategory = document.getElementById('category-select').value;
        
        // Build query string
        let queryString = 'types=' + selectedTypes.join(',');
        if (selectedCategory) {
            queryString += '&category=' + selectedCategory;
        }
        
        // Fetch counts from API
        fetch('api/get_question_counts.php?' + queryString)
            .then(response => response.json())
            .then(data => {
                // Update total count
                document.getElementById('total-available-questions').textContent = data.total;
                
                // Update type counts
                document.getElementById('multiple-choice-count').textContent = 
                    data.by_type.multiple_choice || 0;
                document.getElementById('true-false-count').textContent = 
                    data.by_type.true_false || 0;
                document.getElementById('programming-count').textContent = 
                    data.by_type.programming || 0;
                
                // Show content
                document.querySelector('.stats-loading').style.display = 'none';
                document.querySelector('.stats-content').style.display = 'block';
                
                // Update max questions input
                const totalQuestionsInput = document.getElementById('total-questions');
                totalQuestionsInput.max = Math.min(data.total, 50);
                
                // If current value is higher than available, adjust it
                if (parseInt(totalQuestionsInput.value) > data.total) {
                    totalQuestionsInput.value = data.total;
                }
            })
            .catch(error => {
                console.error('Error fetching question counts:', error);
                document.querySelector('.stats-loading').textContent = 
                    'Error loading question counts. Please try again.';
            });
    }
    
    // Show auto generate modal
    autoGenerateBtn.addEventListener('click', function() {
        autoGenerateModal.style.display = 'flex';
        fetchQuestionCounts();
        populateCategories();
    });
    
    // Close modal handlers
    closeAutoGenerateModal.addEventListener('click', function() {
        autoGenerateModal.style.display = 'none';
    });
    
    cancelAutoGenerateBtn.addEventListener('click', function() {
        autoGenerateModal.style.display = 'none';
    });
    
    // Update counts when filters change
    questionTypeFilters.forEach(filter => {
        filter.addEventListener('change', fetchQuestionCounts);
    });
    
    // Handle form submission
    confirmAutoGenerateBtn.addEventListener('click', function() {
        // Get form data
        const totalQuestions = parseInt(document.getElementById('total-questions').value);
        const availableQuestions = parseInt(document.getElementById('total-available-questions').textContent);
        const questionTypes = Array.from(document.querySelectorAll('input[name="question-types[]"]:checked'))
            .map(checkbox => checkbox.value);
        const category = document.getElementById('category-select').value;
        const pointsPerQuestion = document.getElementById('points-per-question').value;
        
        // Validate form
        if (totalQuestions < 1 || questionTypes.length === 0) {
            alert('Please select at least one question type and specify the number of questions.');
            return;
        }
        
        // Check if enough questions are available
        if (totalQuestions > availableQuestions) {
            alert(`You requested ${totalQuestions} questions, but only ${availableQuestions} are available with your current filters. Please reduce the number of questions or adjust your filters.`);
            return;
        }
        
        // Get the exam ID from the URL or hidden input
        const urlParams = new URLSearchParams(window.location.search);
        const examId = urlParams.get('exam_id') || document.getElementById('exam_id').value;
        
        // Prepare data for API call
        const requestData = {
            exam_id: examId,
            total_questions: totalQuestions,
            question_types: questionTypes,
            category: category,
            points_per_question: pointsPerQuestion
        };
        
        // Show loading state
        confirmAutoGenerateBtn.textContent = 'Generating...';
        confirmAutoGenerateBtn.disabled = true;
        
        // Make API call to generate questions
        fetch('api/auto_generate_questions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close modal
                autoGenerateModal.style.display = 'none';
                
                // Show success message
                alert(`Successfully added ${data.added_questions} questions to your exam!`);
                
                // Reload the page to show the new questions
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error generating questions:', error);
            alert('An error occurred while generating questions. Please try again.');
        })
        .finally(() => {
            // Reset button state
            confirmAutoGenerateBtn.textContent = 'Generate Questions';
            confirmAutoGenerateBtn.disabled = false;
        });
    });

    // Search functionality for questions in the editor
    const searchInput = document.querySelector('.search-input');
    const searchButton = document.querySelector('.search-button');
    const sidebarQuestionType = document.getElementById('sidebar-question-type');
    const questionCards = document.querySelectorAll('.question-card');

    // Function to perform search
    function searchQuestions() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const selectedType = sidebarQuestionType.value.toLowerCase();
        let matchFound = false;
        
        // Show a loading indicator
        const questionsPanel = document.querySelector('.questions-panel');
        const existingNoResults = document.querySelector('.no-search-results');
        if (existingNoResults) {
            existingNoResults.remove();
        }
        
        // If search term is empty and no type filter, show all questions
        if (searchTerm === '' && selectedType === '') {
            questionCards.forEach(card => {
                card.style.display = 'block';
            });
            return;
        }
        
        // Loop through all question cards
        questionCards.forEach(card => {
            const questionText = card.querySelector('.question-text').textContent.toLowerCase();
            const questionType = card.querySelector('.question-type').textContent.toLowerCase();
            
            // Check if the question matches both search term and type filter
            const matchesSearchTerm = searchTerm === '' || questionText.includes(searchTerm);
            const matchesType = selectedType === '' || questionType.includes(selectedType);
            
            if (matchesSearchTerm && matchesType) {
                card.style.display = 'block';
                matchFound = true;
            } else {
                card.style.display = 'none';
            }
        });
        
        // Show "no results" message if no matches found
        if (!matchFound) {
            const noResults = document.createElement('div');
            noResults.className = 'no-search-results';
            noResults.style.textAlign = 'center';
            noResults.style.padding = '20px';
            noResults.style.color = '#666';
            noResults.innerHTML = `
                <span class="material-symbols-rounded" style="font-size: 48px; color: #ccc; display: block; margin-bottom: 10px;">search_off</span>
                <p>No questions found matching your search criteria.</p>
                <button class="btn btn-settings clear-search" style="margin-top: 10px;">Clear Search</button>
            `;
            
            // Insert after the search button but before the question cards
            const addQuestionBtn = document.getElementById('add-question-btn');
            questionsPanel.insertBefore(noResults, addQuestionBtn);
            
            // Add event listener to the clear search button
            noResults.querySelector('.clear-search').addEventListener('click', function() {
                searchInput.value = '';
                sidebarQuestionType.value = '';
                searchQuestions();
            });
        }
    }

    // Add event listeners for search
    searchButton.addEventListener('click', searchQuestions);
    searchInput.addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            searchQuestions();
        }
    });
    sidebarQuestionType.addEventListener('change', searchQuestions);

    // Add clear button inside search input
    const searchContainer = document.querySelector('.search-container');
    const clearButton = document.createElement('button');
    clearButton.className = 'clear-search-button';
    clearButton.innerHTML = '<span class="material-symbols-rounded">close</span>';
    clearButton.style.position = 'absolute';
    clearButton.style.right = '70px'; // Position it before the search button
    clearButton.style.top = '50%';
    clearButton.style.transform = 'translateY(-50%)';
    clearButton.style.background = 'none';
    clearButton.style.border = 'none';
    clearButton.style.color = '#666';
    clearButton.style.cursor = 'pointer';
    clearButton.style.display = 'none'; // Initially hidden

    searchContainer.appendChild(clearButton);

    // Show/hide clear button based on search input
    searchInput.addEventListener('input', function() {
        clearButton.style.display = this.value ? 'block' : 'none';
    });

    // Clear search when button is clicked
    clearButton.addEventListener('click', function() {
        searchInput.value = '';
        clearButton.style.display = 'none';
        searchQuestions();
        searchInput.focus();
    });

    // Load exam settings if we have an exam ID
    const urlParams = new URLSearchParams(window.location.search);
    const examId = urlParams.get('exam_id');
    
    if (examId) {
        loadExamSettings(examId);
    }

    // Passing score functionality
    const passingScoreType = document.getElementById('passing-score-type');
    const passingScoreContainer = document.getElementById('passing-score-container');
    const passingScoreInput = document.getElementById('passing-score');
    const passingScoreUnit = document.getElementById('passing-score-unit');
    const passingScoreHint = document.getElementById('passing-score-hint');

    // Check if all elements exist before adding event listeners
    if (passingScoreType && passingScoreContainer && passingScoreInput && passingScoreUnit && passingScoreHint) {
        console.log('All passing score elements found');
        
        // Add immediate check in case the select already has a value (for editing existing exams)
        if (passingScoreType.value !== '') {
            passingScoreContainer.style.display = 'block';
            
            if (passingScoreType.value === 'percentage') {
                passingScoreUnit.textContent = '%';
            } else {
                passingScoreUnit.textContent = 'points';
            }
        }
        
        passingScoreType.addEventListener('change', function() {
            const selectedType = this.value;
            
            if (selectedType === '') {
                passingScoreContainer.style.display = 'none';
                passingScoreInput.value = '';
            } else {
                passingScoreContainer.style.display = 'block';
                
                if (selectedType === 'percentage') {
                    passingScoreInput.min = 0;
                    passingScoreInput.max = 100;
                    passingScoreInput.step = 1;
                    passingScoreUnit.textContent = '%';
                    passingScoreHint.textContent = 'Enter a value between 0 and 100';
                    passingScoreInput.placeholder = 'e.g., 70';
                } else {
                    passingScoreInput.min = 0;
                    passingScoreInput.max = '';
                    passingScoreInput.step = 1;
                    passingScoreUnit.textContent = 'points';
                    passingScoreHint.textContent = 'Enter the minimum points required to pass';
                    passingScoreInput.placeholder = 'e.g., 50';
                }
            }
            
            console.log('Passing score type changed to:', selectedType);
            console.log('Container display set to:', passingScoreContainer.style.display);
        });
    } else {
        console.error('Some passing score elements are missing:',
            { 
                type: !!passingScoreType, 
                container: !!passingScoreContainer, 
                input: !!passingScoreInput, 
                unit: !!passingScoreUnit, 
                hint: !!passingScoreHint 
            }
        );
    }

    // Image upload preview handling
    const coverImagePreview = document.getElementById('cover-image-preview');
    const coverImageText = document.getElementById('cover-image-text');
    const coverImageOverlay = document.getElementById('cover-image-overlay');
    const coverImageInput = document.getElementById('cover-image');
    const removeImageBtn = document.getElementById('remove-image-btn');
    const removeImageInput = document.getElementById('remove-cover-image');
    
    // Hide remove button if no image
    if (!coverImagePreview.src || coverImagePreview.src === window.location.href) {
        removeImageBtn.style.display = 'none';
        coverImagePreview.style.display = 'none';
        coverImageText.textContent = 'Add cover image';
    } else {
        removeImageBtn.style.display = 'flex';
        coverImagePreview.style.display = 'block';
        coverImageText.textContent = 'Change cover image';
    }
    
    // Handle clicking on the image preview area
    coverImageOverlay.parentElement.addEventListener('click', function(e) {
        coverImageInput.click();
    });
    
    // Handle image selection
    coverImageInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            
            // Check file size (limit to 5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('Image size should be less than 5MB');
                this.value = '';
                return;
            }
            
            // Check file type
            if (!file.type.match('image.*')) {
                alert('Please select an image file');
                this.value = '';
                return;
            }
            
            const reader = new FileReader();
            
            reader.onload = function(e) {
                // Show the image preview
                coverImagePreview.src = e.target.result;
                coverImagePreview.style.display = 'block';
                
                // Show remove button and update text
                removeImageBtn.style.display = 'flex';
                coverImageText.textContent = 'Change cover image';
                
                // Reset the remove flag
                removeImageInput.value = '0';
            };
            
            reader.readAsDataURL(file);
        }
    });
    
    // Handle image removal
    removeImageBtn.addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('remove-cover-image').value = '1';
        document.getElementById('cover-image-preview').style.display = 'none';
        // You might want to show a placeholder or default image here
    });
});

// Function to duplicate a question
function duplicateQuestion(questionId) {
    // Show loading alert
    showAlert('Duplicating question...', 'info');
    
    // Get the exam ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    const examId = urlParams.get('exam_id');
    
    // Send AJAX request to duplicate the question
    fetch('duplicate_question.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `question_id=${questionId}&exam_id=${examId}`
    })
    .then(response => response.json())
        .then(data => {
            if (data.success) {
            // Show success message
            showAlert('Question duplicated successfully!', 'success', function() {
                // Reload the page to show the duplicated question
                window.location.reload();
            });
            } else {
            // Show error message
            showAlert(data.message || 'Failed to duplicate question.', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
        showAlert('An error occurred while duplicating the question.', 'error');
    });
}

// Function to confirm question deletion
function confirmDeleteQuestion(questionId) {
    // Create a confirmation alert modal
    const alertModal = document.getElementById('alert-modal');
    const alertMessage = document.getElementById('alert-message');
    const alertIcon = document.querySelector('.alert-icon');
    const alertIconSymbol = document.getElementById('alert-icon-symbol');
    const confirmBtn = document.getElementById('alert-confirm-btn');
    
    // Create a cancel button if it doesn't exist
    let cancelBtn = document.querySelector('.alert-btn.secondary');
    if (!cancelBtn) {
        cancelBtn = document.createElement('button');
        cancelBtn.className = 'alert-btn secondary';
        cancelBtn.id = 'alert-cancel-btn';
        cancelBtn.textContent = 'Cancel';
        document.querySelector('.alert-actions').appendChild(cancelBtn);
            } else {
        cancelBtn.style.display = 'inline-block';
    }
    
    // Set warning message and icon
    alertMessage.textContent = 'Are you sure you want to delete this question? This action cannot be undone.';
    alertIcon.className = 'alert-icon warning';
    alertIconSymbol.textContent = 'warning';
    
    // Update confirm button text
    confirmBtn.textContent = 'Delete';
    
    // Show the modal with animation
    alertModal.style.display = 'flex';
    setTimeout(() => {
        alertModal.classList.add('show');
    }, 10);
    
    // Handle the confirm button (delete)
    confirmBtn.onclick = function() {
        // Hide the modal with animation
        alertModal.classList.remove('show');
        setTimeout(() => {
            alertModal.style.display = 'none';
            // Reset button text
            confirmBtn.textContent = 'OK';
            // Hide cancel button
            cancelBtn.style.display = 'none';
            // Proceed with deletion
            deleteQuestion(questionId);
        }, 300);
    };
    
    // Handle the cancel button
    cancelBtn.onclick = function() {
        // Hide the modal with animation
        alertModal.classList.remove('show');
        setTimeout(() => {
            alertModal.style.display = 'none';
            // Reset button text
            confirmBtn.textContent = 'OK';
            // Hide cancel button
            cancelBtn.style.display = 'none';
        }, 300);
    };
    
    // Close when clicking outside (acts as cancel)
    alertModal.onclick = function(e) {
        if (e.target === alertModal) {
            cancelBtn.click();
        }
    };
}

// Function to delete a question
function deleteQuestion(questionId) {
    // Show loading alert
    showAlert('Deleting question...', 'info');
    
    // Get the exam ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    const examId = urlParams.get('exam_id');
    
    // Send AJAX request to delete the question
    fetch('delete_question.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `question_id=${questionId}&exam_id=${examId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            showAlert('Question deleted successfully!', 'success', function() {
                // Reload the page to update the question list
            window.location.reload();
            });
        } else {
            // Show error message
            showAlert(data.message || 'Failed to delete question.', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred while deleting the question.', 'error');
    });
}