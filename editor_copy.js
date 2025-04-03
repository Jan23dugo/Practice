// Define selectedQuestions in the global scope
let selectedQuestions = new Set();

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
    addQuestionBtn.addEventListener('click', function() {
        // Get the exam ID from URL
        const urlParams = new URLSearchParams(window.location.search);
        const examId = urlParams.get('exam_id');
        
        if (examId) {
            // If we have an exam ID, just show the question type modal
            questionTypeModal.style.display = 'flex';
        } else {
            // If no exam ID, create a temporary exam first, then show question type modal
            createTemporaryExam();
        }
    });
    
    // Close question type modal when close button is clicked
    closeQuestionTypeModal.addEventListener('click', function() {
        questionTypeModal.style.display = 'none';
    });
    
    // Close question type modal when clicking outside
    questionTypeModal.addEventListener('click', function(e) {
        if (e.target === questionTypeModal) {
            questionTypeModal.style.display = 'none';
        }
    });
    
    // Handle question type selection
    questionTypeCards.forEach(card => {
        card.addEventListener('click', function() {
            const questionType = this.getAttribute('data-type');
            const urlParams = new URLSearchParams(window.location.search);
            const examId = urlParams.get('exam_id');
            
            // Redirect to the appropriate question editor
            window.location.href = `${questionType}.php?exam_id=${examId}`;
        });
    });
    
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
            const examId = new URLSearchParams(window.location.search).get('exam_id');
            
            if (confirm('Are you sure you want to duplicate this question?')) {
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
                        // Reload the page to show the duplicated question
                        window.location.reload();
                    } else {
                        alert('Error duplicating question: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while duplicating the question.');
                });
            }
        });
    });
    
    // Delete button functionality
    const deleteButtons = document.querySelectorAll('.action-btn.delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const questionId = this.getAttribute('data-question-id');
            
            if (confirm('Are you sure you want to delete this question? This action cannot be undone.')) {
                // Send AJAX request to delete the question
                fetch('delete_question.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `question_id=${questionId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the question card from the DOM
                this.closest('.question-card').remove();
                        
                        // Show success message
                        alert('Question deleted successfully!');
                    } else {
                        alert('Error deleting question: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the question.');
                });
            }
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
    
    // Show modal when Publish button is clicked
    publishBtn.addEventListener('click', function() {
        // Get the exam ID from URL
        const urlParams = new URLSearchParams(window.location.search);
        const examId = urlParams.get('exam_id');
        
        if (examId) {
            loadExamSettings(examId);
        }
        
        settingsModal.style.display = 'flex';
    });
    
    // Show modal when Settings button is clicked
    settingsBtn.addEventListener('click', function() {
        // Get the exam ID from URL
        const urlParams = new URLSearchParams(window.location.search);
        const examId = urlParams.get('exam_id');
        
        if (examId) {
            loadExamSettings(examId);
        }
        
        settingsModal.style.display = 'flex';
    });
    
    // Show modal when quiz title is clicked
    quizTitle.addEventListener('click', function(e) {
        e.preventDefault(); // Prevent navigation
        settingsModal.style.display = 'flex';
    });
    
    // Close modal when close button is clicked
    closeSettingsModal.addEventListener('click', function() {
        settingsModal.style.display = 'none';
    });
    
    // Close modal when clicking outside
    settingsModal.addEventListener('click', function(e) {
        if (e.target === settingsModal) {
            settingsModal.style.display = 'none';
        }
    });
    
    // Validate and publish
    const examForm = document.getElementById('examForm');
    
    examForm.addEventListener('submit', function(e) {
        e.preventDefault();
        console.log('Form submitted');
        
        // Validate form if needed
        // [Your validation code...]
        
        // Prepare form data
        const formData = new FormData(this);
        
        // Show loading indicator or disable button
        const submitButton = document.getElementById('confirm-publish');
        submitButton.disabled = true;
        submitButton.innerHTML = 'Saving...';
        
        // Submit form via AJAX
        fetch('save_exam.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Response is not valid JSON:', text);
                    throw new Error('Invalid JSON response from server');
                }
            });
        })
        .then(data => {
            console.log('Response data:', data);
            
            if (data.success) {
                // Set success message in session storage before redirecting
                sessionStorage.setItem('examSuccess', 'Exam saved successfully!');
                // Redirect to exams page
                window.location.href = 'exam.php';
            } else {
                // Show error message
                alert('Error: ' + data.message);
                submitButton.disabled = false;
                submitButton.innerHTML = 'Publish';
            }
        })
        .catch(error => {
            console.error('Error saving exam:', error);
            alert('There was an error saving the exam. Please try again.');
            submitButton.disabled = false;
            submitButton.innerHTML = 'Publish';
        });
    });
    
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

    // Add event listener for preview button
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
    const closeQuestionBankModal = document.getElementById('close-question-bank-modal');
    const bankSearchInput = document.getElementById('bank-search-input');
    const bankSearchButton = document.getElementById('bank-search-button');
    const questionTypeFilter = document.getElementById('question-type-filter');
    const categoryFilter = document.getElementById('category-filter');
    const questionBankList = document.getElementById('question-bank-list');
    const importQuestionsBtn = document.getElementById('import-questions-btn');
    
    // Show modal when clicking "Question Bank" in sidebar
    importFromQuestionBank.addEventListener('click', function() {
        questionBankModal.style.display = 'flex';
        fetchQuestions();
    });

    // Close modal
    closeQuestionBankModal.addEventListener('click', function() {
        questionBankModal.style.display = 'none';
    });

    // Handle search and filters
    bankSearchButton.addEventListener('click', fetchQuestions);
    questionTypeFilter.addEventListener('change', fetchQuestions);
    categoryFilter.addEventListener('change', fetchQuestions);

    function fetchQuestions() {
        questionBankList.innerHTML = '<div class="loading-indicator">Loading questions...</div>';
        
        const searchParams = new URLSearchParams({
            search: bankSearchInput.value,
            type: questionTypeFilter.value,
            category: categoryFilter.value
        });

        fetch(`fetch_question_bank.php?${searchParams.toString()}`)
            .then(response => response.json())
            .then(data => {
                displayQuestions(data.questions);
            })
            .catch(error => {
                questionBankList.innerHTML = '<div class="error-message">Error loading questions. Please try again.</div>';
                console.error('Error:', error);
            });
    }

    function displayQuestions(questions) {
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

    function updateImportButton() {
        importQuestionsBtn.disabled = selectedQuestions.size === 0;
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
        e.stopPropagation();
        
        // Clear the file input
        coverImageInput.value = '';
        
        // Hide the preview
        coverImagePreview.src = '';
        coverImagePreview.style.display = 'none';
        
        // Hide remove button and update text
        this.style.display = 'none';
        coverImageText.textContent = 'Add cover image';
        
        // Set the remove flag to 1
        removeImageInput.value = '1';
    });
});

// Function to create and display the preview modal
function createPreviewModal(examId) {
    // Create modal overlay
    const previewModalOverlay = document.createElement('div');
    previewModalOverlay.className = 'settings-modal-overlay';
    previewModalOverlay.id = 'preview-modal';
    previewModalOverlay.style.display = 'flex';
    
    // Create modal content
    const previewModalContent = document.createElement('div');
    previewModalContent.className = 'settings-modal-content';
    previewModalContent.style.maxWidth = '900px';
    
    // Create modal header
    const previewModalHeader = document.createElement('div');
    previewModalHeader.className = 'settings-modal-header';
    previewModalHeader.innerHTML = `
        <div class="settings-modal-title">
            <div class="settings-icon">
                <span class="material-symbols-rounded">visibility</span>
            </div>
            <div class="settings-text">
                <h2>Exam Preview</h2>
                <p>This is how your exam will appear to students</p>
            </div>
            <button type="button" class="close-modal" id="close-preview-modal">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>
    `;
    
    // Create modal body
    const previewModalBody = document.createElement('div');
    previewModalBody.className = 'settings-modal-body';
    previewModalBody.innerHTML = '<div id="preview-content" style="min-height: 300px;"><p>Loading preview...</p></div>';
    
    // Create modal footer
    const previewModalFooter = document.createElement('div');
    previewModalFooter.className = 'settings-modal-footer';
    previewModalFooter.innerHTML = `
        <button type="button" class="btn btn-settings" id="close-preview-btn">Close</button>
    `;
    
    // Assemble modal
    previewModalContent.appendChild(previewModalHeader);
    previewModalContent.appendChild(previewModalBody);
    previewModalContent.appendChild(previewModalFooter);
    previewModalOverlay.appendChild(previewModalContent);
    
    // Add modal to document
    document.body.appendChild(previewModalOverlay);
    
    // Add event listeners for close buttons
    document.getElementById('close-preview-modal').addEventListener('click', function() {
        document.body.removeChild(previewModalOverlay);
    });
    
    document.getElementById('close-preview-btn').addEventListener('click', function() {
        document.body.removeChild(previewModalOverlay);
    });
    
    // Close modal when clicking outside
    previewModalOverlay.addEventListener('click', function(e) {
        if (e.target === previewModalOverlay) {
            document.body.removeChild(previewModalOverlay);
        }
    });
    
    // Fetch exam preview data
    fetchExamPreview(examId);
}

// Function to fetch exam preview data
function fetchExamPreview(examId) {
    fetch(`get_exam_preview.php?exam_id=${examId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                displayExamPreview(data.exam, data.questions);
            } else {
                document.getElementById('preview-content').innerHTML = `
                    <div style="text-align: center; padding: 30px;">
                        <p style="color: #dc3545;">${data.message || 'Failed to load preview'}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('preview-content').innerHTML = `
                <div style="text-align: center; padding: 30px;">
                    <p style="color: #dc3545;">Error loading preview: ${error.message}</p>
                </div>
            `;
        });
}

// Function to display exam preview
function displayExamPreview(exam, questions) {
    const previewContent = document.getElementById('preview-content');
    
    // Create exam header
    let html = `
        <div style="padding: 20px; border-bottom: 1px solid #e0e0e0; margin-bottom: 20px;">
            <h2 style="margin: 0 0 10px 0; color: #333;">${exam.title}</h2>
            ${exam.description ? `<p style="color: #666; margin-bottom: 15px;">${exam.description}</p>` : ''}
            <div style="display: flex; gap: 15px; font-size: 14px; color: #666;">
                <div>
                    <span class="material-symbols-rounded" style="vertical-align: middle; font-size: 16px;">quiz</span>
                    ${questions.length} question${questions.length !== 1 ? 's' : ''}
                </div>
               
                <div>
                    <span class="material-symbols-rounded" style="vertical-align: middle; font-size: 16px;">star</span>
                    ${calculateTotalPoints(questions)} points
                </div>
            </div>
        </div>
    `;
    
    // Add question navigation and container
    html += `
        <div class="question-navigator" style="padding: 0 20px;">
            <div id="question-container"></div>
            
            <div style="display: flex; justify-content: space-between; margin-top: 30px;">
                <button id="prev-question" class="btn btn-settings" style="display: none;">
                    <span class="material-symbols-rounded">arrow_back</span> Previous
                </button>
                <div id="question-pagination" style="align-self: center; font-size: 14px; color: #666;">
                    Question <span id="current-question">1</span> of ${questions.length}
                </div>
                <button id="next-question" class="btn btn-settings" ${questions.length <= 1 ? 'style="display: none;"' : ''}>
                    Next <span class="material-symbols-rounded">arrow_forward</span>
                </button>
            </div>
        </div>
    `;
    
    // Add submit button at the bottom
    html += `
        <div style="padding: 20px; text-align: center; border-top: 1px solid #e0e0e0; margin-top: 20px;">
            <button style="background-color: #8e68cc; color: white; border: none; border-radius: 4px; padding: 10px 24px; font-size: 16px; font-weight: 500; cursor: pointer;">
                Submit Exam
            </button>
        </div>
    `;
    
    previewContent.innerHTML = html;
    
    // Initialize question navigation
    if (questions.length > 0) {
        initializeQuestionNavigation(questions);
    } else {
        document.getElementById('question-container').innerHTML = `
            <div style="text-align: center; padding: 30px;">
                <p>No questions have been added to this exam yet.</p>
            </div>
        `;
    }
}

// Function to initialize question navigation
function initializeQuestionNavigation(questions) {
    let currentQuestionIndex = 0;
    const questionContainer = document.getElementById('question-container');
    const prevButton = document.getElementById('prev-question');
    const nextButton = document.getElementById('next-question');
    const currentQuestionSpan = document.getElementById('current-question');
    
    // Function to display a specific question
    function displayQuestion(index) {
        const question = questions[index];
        currentQuestionIndex = index;
        currentQuestionSpan.textContent = index + 1;
        
        // Update navigation buttons
        prevButton.style.display = index > 0 ? 'flex' : 'none';
        nextButton.style.display = index < questions.length - 1 ? 'flex' : 'none';
        
        // Generate question HTML
        let questionHtml = `
            <div style="margin-bottom: 30px; padding-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <div style="font-weight: 500;">Question ${index + 1}</div>
                    <div style="color: #666; font-size: 14px;">${question.points} point${question.points !== 1 ? 's' : ''}</div>
                </div>
                <div style="margin-bottom: 15px;">${question.question_text}</div>
        `;
        
        // Display different answer formats based on question type
        if (question.question_type === 'multiple-choice') {
            questionHtml += '<div style="display: flex; flex-direction: column; gap: 10px;">';
            question.answers.forEach(answer => {
                questionHtml += `
                    <div style="display: flex; align-items: center; gap: 10px; padding: 10px; background-color: #f8f9fa; border-radius: 4px;">
                        <div style="width: 20px; height: 20px; border-radius: 50%; border: 2px solid #8e68cc;"></div>
                        <div>${answer.answer_text}</div>
                    </div>
                `;
            });
            questionHtml += '</div>';
        } else if (question.question_type === 'true-false') {
            questionHtml += `
                <div style="display: flex; gap: 15px;">
                    <div style="display: flex; align-items: center; gap: 10px; padding: 10px 15px; background-color: #f8f9fa; border-radius: 4px;">
                        <div style="width: 18px; height: 18px; border-radius: 50%; border: 2px solid #8e68cc;"></div>
                        <div>True</div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px; padding: 10px 15px; background-color: #f8f9fa; border-radius: 4px;">
                        <div style="width: 18px; height: 18px; border-radius: 50%; border: 2px solid #8e68cc;"></div>
                        <div>False</div>
                    </div>
                </div>
            `;
        } else if (question.question_type === 'programming') {
            // Enhanced programming question display with test cases and sample compiler
            questionHtml += `
                <div style="border: 1px solid #e0e0e0; border-radius: 4px; margin-bottom: 15px;">
                    ${question.starter_code ? `
                        <div style="padding: 15px; background-color: #f8f9fa; border-bottom: 1px solid #e0e0e0;">
                            <h4 style="margin: 0 0 10px 0; font-size: 14px; color: #333;">Starter Code:</h4>
                            <pre style="background-color: #fff; padding: 12px; border-radius: 4px; border: 1px solid #e0e0e0; font-family: monospace; font-size: 13px; line-height: 1.4; overflow-x: auto; margin: 0;">${question.starter_code}</pre>
                        </div>
                    ` : ''}
                    
                    <div style="padding: 15px; background-color: #f8f9fa;">
                        <h4 style="margin: 0 0 10px 0; font-size: 14px; color: #333;">Your Solution:</h4>
                        <div style="position: relative;">
                            <div style="position: absolute; top: 10px; right: 10px; display: flex; gap: 5px;">
                                <button class="btn" style="padding: 4px 8px; font-size: 12px; background-color: #f0f0f0; color: #333; border: none; border-radius: 4px; cursor: pointer;">
                                    <span class="material-symbols-rounded" style="font-size: 16px;">content_copy</span>
                                </button>
                                <button class="btn" style="padding: 4px 8px; font-size: 12px; background-color: #f0f0f0; color: #333; border: none; border-radius: 4px; cursor: pointer;">
                                    <span class="material-symbols-rounded" style="font-size: 16px;">format_indent_increase</span>
                                </button>
                            </div>
                            <div style="background-color: #fff; border: 1px solid #ddd; border-radius: 4px; padding: 10px; min-height: 150px; font-family: monospace; font-size: 14px; line-height: 1.5; color: #333; white-space: pre-wrap;">// Write your code here</div>
                        </div>
                    </div>
                </div>
            `;
            
            // Add test cases section if available
            if (question.test_cases && question.test_cases.length > 0) {
                questionHtml += `
                    <div style="margin-top: 20px; padding: 15px; background-color: #f8f9fa; border: 1px solid #e0e0e0; border-radius: 4px;">
                        <h4 style="margin: 0 0 10px 0; font-size: 14px; color: #333;">Test Cases:</h4>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                `;
                
                // Display visible test cases
                question.test_cases.forEach((testCase, idx) => {
                    if (!testCase.is_hidden) {
                        questionHtml += `
                            <div style="padding: 10px; background-color: #fff; border: 1px solid #e0e0e0; border-radius: 4px;">
                                <div style="font-weight: 500; margin-bottom: 5px;">Test Case ${idx + 1}:</div>
                                <div style="display: flex; flex-wrap: wrap; gap: 15px;">
                                    <div style="flex: 1; min-width: 200px;">
                                        <div style="font-size: 13px; color: #666; margin-bottom: 3px;">Input:</div>
                                        <pre style="background-color: #f8f9fa; padding: 8px; border-radius: 4px; font-family: monospace; font-size: 12px; margin: 0;">${testCase.input || 'N/A'}</pre>
                                    </div>
                                    <div style="flex: 1; min-width: 200px;">
                                        <div style="font-size: 13px; color: #666; margin-bottom: 3px;">Expected Output:</div>
                                        <pre style="background-color: #f8f9fa; padding: 8px; border-radius: 4px; font-family: monospace; font-size: 12px; margin: 0;">${testCase.expected_output || 'N/A'}</pre>
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                });
                
                // Count hidden test cases
                const hiddenCount = question.test_cases.filter(tc => tc.is_hidden).length;
                if (hiddenCount > 0) {
                    questionHtml += `
                        <div style="padding: 10px; background-color: #fff; border: 1px solid #e0e0e0; border-radius: 4px; color: #666;">
                            <span class="material-symbols-rounded" style="vertical-align: middle; margin-right: 5px; font-size: 16px;">visibility_off</span>
                            ${hiddenCount} hidden test case${hiddenCount !== 1 ? 's' : ''} will be used to evaluate your solution
                        </div>
                    `;
                }
                
                questionHtml += `
                        </div>
                    </div>
                `;
            }
            
            // Add run code button and output section
            questionHtml += `
                <div style="margin-top: 20px; display: flex; justify-content: flex-end;">
                    <button class="btn" style="padding: 8px 16px; background-color: #8e68cc; color: white; border: none; border-radius: 4px; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                        <span class="material-symbols-rounded">play_arrow</span>
                        Run Code
                    </button>
                </div>
                
                <div style="margin-top: 15px; padding: 15px; background-color: #f8f9fa; border: 1px solid #e0e0e0; border-radius: 4px; display: none;" id="code-output-${index}">
                    <h4 style="margin: 0 0 10px 0; font-size: 14px; color: #333;">Output:</h4>
                    <pre style="background-color: #fff; padding: 12px; border-radius: 4px; border: 1px solid #e0e0e0; font-family: monospace; font-size: 13px; line-height: 1.4; overflow-x: auto; margin: 0; min-height: 60px;">// Code output will appear here</pre>
                </div>
            `;
        }
        
        questionHtml += '</div>';
        questionContainer.innerHTML = questionHtml;
        
        // Add event listeners for programming question buttons
        if (question.question_type === 'programming') {
            const runCodeBtn = questionContainer.querySelector('.btn');
            runCodeBtn.addEventListener('click', function() {
                const outputDiv = document.getElementById(`code-output-${index}`);
                outputDiv.style.display = 'block';
                // This is just a preview, so we'll simulate code execution
                setTimeout(() => {
                    const outputPre = outputDiv.querySelector('pre');
                    outputPre.textContent = '// This is a preview mode.\n// In the actual exam, students will see their code output here.';
                }, 500);
            });
        }
    }
    
    // Display the first question
    displayQuestion(0);
    
    // Add event listeners for navigation buttons
    prevButton.addEventListener('click', function() {
        if (currentQuestionIndex > 0) {
            displayQuestion(currentQuestionIndex - 1);
        }
    });
    
    nextButton.addEventListener('click', function() {
        if (currentQuestionIndex < questions.length - 1) {
            displayQuestion(currentQuestionIndex + 1);
        }
    });
}

// Helper function to calculate total time
function calculateTotalTime(questions) {
    // Assuming 30 seconds per question as default
    return Math.ceil(questions.length * 0.5);
}

// Helper function to calculate total points
function calculateTotalPoints(questions) {
    return questions.reduce((total, question) => total + parseInt(question.points || 1), 0);
}

// Function to show question bank modal
function showQuestionBankModal(examId) {
    const questionBankModal = document.getElementById('question-bank-modal');
    questionBankModal.style.display = 'flex';
    
    // Load questions from question bank
    loadQuestionBankQuestions(examId);
    
    // Add event listeners for close buttons
    document.getElementById('close-question-bank-modal').addEventListener('click', function() {
        questionBankModal.style.display = 'none';
    });
    
    document.getElementById('cancel-import-btn').addEventListener('click', function() {
        questionBankModal.style.display = 'none';
    });
    
    // Close modal when clicking outside
    questionBankModal.addEventListener('click', function(e) {
        if (e.target === questionBankModal) {
            questionBankModal.style.display = 'none';
        }
    });
    
    // Handle search
    document.getElementById('bank-search-button').addEventListener('click', function() {
        const searchQuery = document.getElementById('bank-search-input').value;
        loadQuestionBankQuestions(examId, searchQuery);
    });
    
    // Handle filters
    const filterElements = [
        document.getElementById('question-type-filter'),
        document.getElementById('category-filter')
    ];
    
    filterElements.forEach(filter => {
        filter.addEventListener('change', function() {
            loadQuestionBankQuestions(examId);
        });
    });
    
    // Handle import button
    document.getElementById('import-questions-btn').addEventListener('click', function() {
        const selectedQuestions = document.querySelectorAll('#question-bank-list .question-bank-item input[type="checkbox"]:checked');
        
        if (selectedQuestions.length === 0) {
            alert('Please select at least one question to import.');
            return;
        }
        
        const questionIds = Array.from(selectedQuestions).map(checkbox => checkbox.value);
        importQuestionsToExam(examId, questionIds);
    });
}

// Function to load questions from question bank
function loadQuestionBankQuestions(examId, searchQuery = '') {
    const questionBankList = document.getElementById('question-bank-list');
    questionBankList.innerHTML = '<div class="loading-indicator" style="text-align: center; padding: 20px;"><p>Loading questions...</p></div>';
    
    // Get filter values
    const questionType = document.getElementById('question-type-filter').value;
    const category = document.getElementById('category-filter').value;
    
    // Build query string
    let queryString = `exam_id=${examId}`;
    if (searchQuery) queryString += `&search=${encodeURIComponent(searchQuery)}`;
    if (questionType) queryString += `&type=${encodeURIComponent(questionType)}`;
    if (category) queryString += `&category=${encodeURIComponent(category)}`;
    
    // Fetch questions from question bank
    fetch(`fetch_question_bank.php?${queryString}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                displayQuestionBankQuestions(data.questions);
            } else {
                questionBankList.innerHTML = `
                    <div style="text-align: center; padding: 30px;">
                        <p style="color: #666;">${data.message || 'No questions found in the question bank.'}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            questionBankList.innerHTML = `
                <div style="text-align: center; padding: 30px;">
                    <p style="color: #dc3545;">Error loading questions: ${error.message}</p>
                </div>
            `;
        });
}

// Function to display questions from question bank
function displayQuestionBankQuestions(questions) {
    const questionBankList = document.getElementById('question-bank-list');
    
    if (questions.length === 0) {
        questionBankList.innerHTML = `
            <div style="text-align: center; padding: 30px;">
                <p style="color: #666;">No questions found matching your criteria.</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    
    questions.forEach(question => {
        html += `
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
        `;
    });
    
    questionBankList.innerHTML = html;
    
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
    
    // Add event listeners for preview buttons
    const previewButtons = document.querySelectorAll('#question-bank-list .action-btn.preview');
    previewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const questionId = this.getAttribute('data-question-id');
            previewQuestionFromBank(questionId);
        });
    });
}

// Function to preview a question from the bank
function previewQuestionFromBank(questionId) {
    // Fetch question details
    fetch(`get_question_details.php?question_id=${questionId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Create and show preview modal
                createQuestionPreviewModal(data.question);
            } else {
                alert(data.message || 'Failed to load question preview');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading question preview: ' + error.message);
        });
}

// Function to create question preview modal
function createQuestionPreviewModal(question) {
    // Create modal overlay
    const previewModalOverlay = document.createElement('div');
    previewModalOverlay.className = 'settings-modal-overlay';
    previewModalOverlay.style.display = 'flex';
    
    // Create modal content
    const previewModalContent = document.createElement('div');
    previewModalContent.className = 'settings-modal-content';
    previewModalContent.style.maxWidth = '600px';
    
    // Create modal header
    const previewModalHeader = document.createElement('div');
    previewModalHeader.className = 'settings-modal-header';
    previewModalHeader.innerHTML = `
        <div class="settings-modal-title">
            <div class="settings-icon">
                <span class="material-symbols-rounded">visibility</span>
            </div>
            <div class="settings-text">
                <h2>Question Preview</h2>
                <p>${question.question_type} • ${question.points || 1} point${(question.points || 1) !== 1 ? 's' : ''}</p>
            </div>
            <button type="button" class="close-modal" id="close-question-preview">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>
    `;
    
    // Create modal body
    const previewModalBody = document.createElement('div');
    previewModalBody.className = 'settings-modal-body';
    
    let bodyHtml = `
        <div style="margin-bottom: 20px;">
            <div style="font-weight: 500; margin-bottom: 10px;">Question:</div>
            <div style="padding: 15px; background-color: #f8f9fa; border-radius: 4px;">${question.question_text}</div>
        </div>
    `;
    
    // Display different answer formats based on question type
    if (question.question_type === 'multiple-choice') {
        bodyHtml += '<div style="margin-bottom: 20px;"><div style="font-weight: 500; margin-bottom: 10px;">Answer Choices:</div>';
        question.answers.forEach(answer => {
            const isCorrect = answer.is_correct == 1;
            bodyHtml += `
                <div style="display: flex; align-items: center; gap: 10px; padding: 10px; background-color: ${isCorrect ? '#e6f7e6' : '#f8f9fa'}; border-radius: 4px; margin-bottom: 8px; ${isCorrect ? 'border-left: 3px solid #28a745;' : ''}">
                    <span class="material-symbols-rounded" style="color: ${isCorrect ? '#28a745' : '#666'};">${isCorrect ? 'check_circle' : 'radio_button_unchecked'}</span>
                    <div>${answer.answer_text}</div>
                </div>
            `;
        });
        bodyHtml += '</div>';
    } else if (question.question_type === 'true-false') {
        const correctAnswer = question.answers.find(a => a.is_correct == 1);
        bodyHtml += `
            <div style="margin-bottom: 20px;">
                <div style="font-weight: 500; margin-bottom: 10px;">Correct Answer:</div>
                <div style="padding: 10px; background-color: #e6f7e6; border-radius: 4px; border-left: 3px solid #28a745;">
                    <span class="material-symbols-rounded" style="color: #28a745; vertical-align: middle; margin-right: 8px;">check_circle</span>
                    ${correctAnswer ? correctAnswer.answer_text : 'True'}
                </div>
            </div>
        `;
    } else if (question.question_type === 'programming') {
        bodyHtml += `
            <div style="margin-bottom: 20px;">
                <div style="font-weight: 500; margin-bottom: 10px;">Expected Output:</div>
                <div style="padding: 15px; background-color: #f8f9fa; border-radius: 4px; font-family: monospace;">${question.expected_output || 'Not specified'}</div>
            </div>
        `;
    }
    
    // Add metadata
    bodyHtml += `
        <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #e0e0e0;">
            <div style="display: flex; flex-wrap: wrap; gap: 15px;">
                ${question.category ? `<div style="font-size: 14px; color: #666;"><span style="font-weight: 500;">Category:</span> ${question.category}</div>` : ''}
                ${question.created_at ? `<div style="font-size: 14px; color: #666;"><span style="font-weight: 500;">Created:</span> ${new Date(question.created_at).toLocaleDateString()}</div>` : ''}
            </div>
        </div>
    `;
    
    previewModalBody.innerHTML = bodyHtml;
    
    // Create modal footer
    const previewModalFooter = document.createElement('div');
    previewModalFooter.className = 'settings-modal-footer';
    previewModalFooter.innerHTML = `
        <button type="button" class="btn btn-settings" id="close-preview-btn">Close</button>
    `;
    
    // Assemble modal
    previewModalContent.appendChild(previewModalHeader);
    previewModalContent.appendChild(previewModalBody);
    previewModalContent.appendChild(previewModalFooter);
    previewModalOverlay.appendChild(previewModalContent);
    
    // Add modal to document
    document.body.appendChild(previewModalOverlay);
    
    // Add event listeners for close buttons
    document.getElementById('close-question-preview').addEventListener('click', function() {
        document.body.removeChild(previewModalOverlay);
    });
    
    document.getElementById('close-preview-btn').addEventListener('click', function() {
        document.body.removeChild(previewModalOverlay);
    });
    
    // Close modal when clicking outside
    previewModalOverlay.addEventListener('click', function(e) {
        if (e.target === previewModalOverlay) {
            document.body.removeChild(previewModalOverlay);
        }
    });
}

// Function to import selected questions to exam
function importQuestionsToExam(examId, questionIds) {
    console.log('Importing questions:', questionIds, 'to exam:', examId);
    
    if (!questionIds || questionIds.length === 0) {
        alert('No questions selected for import');
        return;
    }
    
    // Show loading indicator
    const importBtn = document.getElementById('import-questions-btn');
    const originalBtnText = importBtn.innerHTML;
    importBtn.innerHTML = 'Importing...';
    importBtn.disabled = true;
    
    // Send AJAX request to import questions
    fetch('import_questions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `exam_id=${examId}&question_ids=${questionIds.join(',')}`
    })
    .then(response => response.json())
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            // Close modal
            document.getElementById('question-bank-modal').style.display = 'none';
            
            // Show success message
            alert(`Successfully imported ${questionIds.length} question(s) to your exam.`);
            
            // Reload the page to show the imported questions
            window.location.reload();
        } else {
            alert('Error importing questions: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while importing questions.');
    })
    .finally(() => {
        // Restore button state
        importBtn.innerHTML = originalBtnText;
        importBtn.disabled = false;
    });
}