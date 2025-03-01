document.addEventListener('DOMContentLoaded', function() {
    // Your existing script code
    
    // DOM Elements
    const questionTypeButtons = document.querySelectorAll('.question-type-btn');
    const questionsContainer = document.getElementById('questions-container');
    const questionModal = document.getElementById('question-modal');
    const closeModalBtn = document.querySelector('.close-modal');
    const cancelQuestionBtn = document.getElementById('cancel-question');
    const saveQuestionBtn = document.getElementById('save-question');
    const modalTitle = document.querySelector('.modal-title');
    const programmingContainer = document.querySelector('.programming-container');
    
    // Import modal elements
    const importModal = document.getElementById('import-modal');
    const importSpreadsheetBtn = document.getElementById('import-spreadsheet-btn');
    const importGFormBtn = document.getElementById('import-gform-btn');
    const closeImportModalBtn = document.getElementById('close-import-modal');
    const cancelImportBtn = document.getElementById('cancel-import');
    const startImportBtn = document.getElementById('start-import');
    const spreadsheetFileInput = document.getElementById('spreadsheet-file');
    const spreadsheetUploadBtn = document.getElementById('spreadsheet-upload-btn');
    const selectedFileName = document.getElementById('selected-file-name');
    const importTabs = document.querySelectorAll('.import-tab');
    
    // Variables to track state
    let selectedQuestionType = 'multiple-choice';
    let questionCounter = 0;
    
    // Event Listeners for question type buttons
    questionTypeButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Remove selection from all buttons
            questionTypeButtons.forEach(btn => btn.classList.remove('selected'));
            
            // Add selection to clicked button
            button.classList.add('selected');
            
            // Update selected question type
            selectedQuestionType = button.getAttribute('data-type');
            
            // Update preview content based on selected type
            updatePreview(selectedQuestionType);
            
            // Show the modal with appropriate content
            modalTitle.textContent = `Add ${getQuestionTypeDisplayName(selectedQuestionType)} Question`;
            
            // Show/hide appropriate controls based on question type
            if (selectedQuestionType === 'programming') {
                programmingContainer.style.display = 'block';
                document.querySelector('.options-container').style.display = 'none';
                document.querySelector('.add-option-btn').style.display = 'none';
            } else if (selectedQuestionType === 'multiple-choice') {
                programmingContainer.style.display = 'none';
                document.querySelector('.options-container').style.display = 'block';
                document.querySelector('.add-option-btn').style.display = 'block';
            } else {
                programmingContainer.style.display = 'none';
                document.querySelector('.options-container').style.display = 'none';
                document.querySelector('.add-option-btn').style.display = 'none';
            }
            
            questionModal.style.display = 'flex';
        });
    });
    
    // Helper function to get display name for question type
    function getQuestionTypeDisplayName(type) {
        switch(type) {
            case 'multiple-choice': return 'Multiple Choice';
            case 'fill-blank': return 'Fill in the Blank';
            case 'passage': return 'True or False';
            case 'programming': return 'Programming';
            default: return 'Question';
        }
    }
    
    // Event listeners for question modal
    closeModalBtn.addEventListener('click', () => {
        questionModal.style.display = 'none';
    });
    
    cancelQuestionBtn.addEventListener('click', () => {
        questionModal.style.display = 'none';
    });
    
    saveQuestionBtn.addEventListener('click', () => {
        // Check if we're editing an existing question
        const editingId = saveQuestionBtn.getAttribute('data-editing-id');
        
        if (editingId) {
            updateQuestion(editingId);
        } else {
            addQuestion();
        }
        
        questionModal.style.display = 'none';
    });
    
    // Add the new functions we created earlier
    function addQuestion() {
        questionCounter++;
        
        // Get the question text
        const questionText = document.querySelector('.question-input').value;
        
        // Create a new question element
        const questionElement = document.createElement('div');
        questionElement.className = 'question-card';
        questionElement.setAttribute('data-question-id', questionCounter);
        
        let questionContent = '';
        
        // Create content based on question type
        if (selectedQuestionType === 'multiple-choice') {
            // Get all option inputs and checkboxes
            const optionInputs = document.querySelectorAll('.option-input');
            const optionCheckboxes = document.querySelectorAll('.option-checkbox');
            
            let optionsHTML = '';
            
            // Create HTML for each option
            optionInputs.forEach((input, index) => {
                if (input.value.trim() !== '') {
                    const isCorrect = optionCheckboxes[index].checked ? 'correct-option' : '';
                    optionsHTML += `
                        <div class="question-option ${isCorrect}">
                            <span class="option-label">${String.fromCharCode(65 + index)}.</span>
                            <span class="option-text">${input.value}</span>
                        </div>
                    `;
                }
            });
            
            questionContent = `
                <div class="question-header">
                    <span class="question-number">${questionCounter}</span>
                    <h3 class="question-text">${questionText || 'Untitled Question'}</h3>
                </div>
                <div class="question-options">
                    ${optionsHTML}
                </div>
                <div class="question-actions">
                    <button class="question-action-btn edit-btn"><i>✏️</i> Edit</button>
                    <button class="question-action-btn duplicate-btn"><i>🔄</i> Duplicate</button>
                    <button class="question-action-btn delete-btn"><i>🗑️</i> Delete</button>
                </div>
            `;
        } 
        else if (selectedQuestionType === 'programming') {
            // Get programming question details
            const instructions = document.querySelector('.tab-content[data-content="instructions"] .code-editor').value;
            const language = document.querySelector('.language-select').value;
            
            questionContent = `
                <div class="question-header">
                    <span class="question-number">${questionCounter}</span>
                    <h3 class="question-text">${questionText || 'Untitled Programming Question'}</h3>
                </div>
                <div class="question-programming">
                    <div class="programming-language">Language: ${language}</div>
                    <div class="programming-instructions">${instructions}</div>
                </div>
                <div class="question-actions">
                    <button class="question-action-btn edit-btn"><i>✏️</i> Edit</button>
                    <button class="question-action-btn duplicate-btn"><i>🔄</i> Duplicate</button>
                    <button class="question-action-btn delete-btn"><i>🗑️</i> Delete</button>
                </div>
            `;
        }
        else if (selectedQuestionType === 'fill-blank') {
            questionContent = `
                <div class="question-header">
                    <span class="question-number">${questionCounter}</span>
                    <h3 class="question-text">${questionText || 'Untitled Fill in the Blank Question'}</h3>
                </div>
                <div class="question-fill-blank">
                    <div class="fill-blank-text">Fill in the blank question</div>
                </div>
                <div class="question-actions">
                    <button class="question-action-btn edit-btn"><i>✏️</i> Edit</button>
                    <button class="question-action-btn duplicate-btn"><i>🔄</i> Duplicate</button>
                    <button class="question-action-btn delete-btn"><i>🗑️</i> Delete</button>
                </div>
            `;
        }
        else if (selectedQuestionType === 'passage') {
            questionContent = `
                <div class="question-header">
                    <span class="question-number">${questionCounter}</span>
                    <h3 class="question-text">${questionText || 'Untitled True/False Question'}</h3>
                </div>
                <div class="question-true-false">
                    <div class="true-false-options">
                        <div class="true-false-option">True</div>
                        <div class="true-false-option">False</div>
                    </div>
                </div>
                <div class="question-actions">
                    <button class="question-action-btn edit-btn"><i>✏️</i> Edit</button>
                    <button class="question-action-btn duplicate-btn"><i>🔄</i> Duplicate</button>
                    <button class="question-action-btn delete-btn"><i>🗑️</i> Delete</button>
                </div>
            `;
        }
        
        // Add the content to the question element
        questionElement.innerHTML = questionContent;
        
        // Append the new question to the questions container
        questionsContainer.appendChild(questionElement);
        
        // Add event listeners for the action buttons
        setupQuestionActionButtons(questionElement);
        
        // Reset the form
        resetQuestionForm();
    }
    
    // Function to update an existing question
    function updateQuestion(questionId) {
        // Find the question element
        const questionElement = document.querySelector(`.question-card[data-question-id="${questionId}"]`);
        
        if (questionElement) {
            // Get the question text
            const questionText = document.querySelector('.question-input').value;
            
            // Update the question text
            const questionTextElement = questionElement.querySelector('.question-text');
            if (questionTextElement) {
                questionTextElement.textContent = questionText || 'Untitled Question';
            }
            
            // Update based on question type
            if (selectedQuestionType === 'multiple-choice') {
                // Get all option inputs and checkboxes
                const optionInputs = document.querySelectorAll('.option-input');
                const optionCheckboxes = document.querySelectorAll('.option-checkbox');
                
                let optionsHTML = '';
                
                // Create HTML for each option
                optionInputs.forEach((input, index) => {
                    if (input.value.trim() !== '') {
                        const isCorrect = optionCheckboxes[index].checked ? 'correct-option' : '';
                        optionsHTML += `
                            <div class="question-option ${isCorrect}">
                                <span class="option-label">${String.fromCharCode(65 + index)}.</span>
                                <span class="option-text">${input.value}</span>
                            </div>
                        `;
                    }
                });
                
                // Update options container
                const optionsContainer = questionElement.querySelector('.question-options');
                if (optionsContainer) {
                    optionsContainer.innerHTML = optionsHTML;
                }
            }
            
            // Reset the form and save button
            resetQuestionForm();
        }
    }

    // Function to set up event listeners for question action buttons
    function setupQuestionActionButtons(questionElement) {
        // Edit button
        const editBtn = questionElement.querySelector('.edit-btn');
        if (editBtn) {
            editBtn.addEventListener('click', () => {
                // Get question ID
                const questionId = questionElement.getAttribute('data-question-id');
                editQuestion(questionId);
            });
        }
        
        // Duplicate button
        const duplicateBtn = questionElement.querySelector('.duplicate-btn');
        if (duplicateBtn) {
            duplicateBtn.addEventListener('click', () => {
                // Get question ID
                const questionId = questionElement.getAttribute('data-question-id');
                duplicateQuestion(questionId);
            });
        }
        
        // Delete button
        const deleteBtn = questionElement.querySelector('.delete-btn');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', () => {
                // Remove the question element from the DOM
                questionElement.remove();
            });
        }
    }

    // Function to edit a question
    function editQuestion(questionId) {
        // Find the question element
        const questionElement = document.querySelector(`.question-card[data-question-id="${questionId}"]`);
        
        if (questionElement) {
            // Get question type from the classes
            let questionType = 'multiple-choice'; // Default
            
            if (questionElement.querySelector('.question-programming')) {
                questionType = 'programming';
            } else if (questionElement.querySelector('.question-fill-blank')) {
                questionType = 'fill-blank';
            } else if (questionElement.querySelector('.question-true-false')) {
                questionType = 'passage';
            }
            
            // Update selected question type
            selectedQuestionType = questionType;
            
            // Update question type buttons UI
            questionTypeButtons.forEach(btn => {
                btn.classList.remove('selected');
                if (btn.getAttribute('data-type') === questionType) {
                    btn.classList.add('selected');
                    btn.classList.add('selected');
                }
            });
            
            // Get question text
            const questionText = questionElement.querySelector('.question-text').textContent;
            
            // Pre-fill the modal with the question data
            document.querySelector('.question-input').value = questionText;
            
            // Show/hide appropriate sections based on question type
            if (questionType === 'programming') {
                modalTitle.textContent = 'Edit Programming Question';
                programmingContainer.style.display = 'block';
                document.querySelector('.options-container').style.display = 'none';
                document.querySelector('.add-option-btn').style.display = 'none';
                
                // For now, we're not pre-filling programming details
                // This could be expanded later
            } else {
                modalTitle.textContent = 'Edit Question';
                programmingContainer.style.display = 'none';
                
                if (questionType === 'multiple-choice') {
                    document.querySelector('.options-container').style.display = 'block';
                    document.querySelector('.add-option-btn').style.display = 'block';
                    
                    // Pre-fill options
                    const optionElements = questionElement.querySelectorAll('.question-option');
                    const optionInputs = document.querySelectorAll('.option-input');
                    const optionCheckboxes = document.querySelectorAll('.option-checkbox');
                    
                    // Reset all options
                    optionInputs.forEach((input, i) => {
                        input.value = '';
                        optionCheckboxes[i].checked = false;
                    });
                    
                    // Fill in existing options
                    optionElements.forEach((option, i) => {
                        if (i < optionInputs.length) {
                            const optionText = option.querySelector('.option-text').textContent;
                            optionInputs[i].value = optionText;
                            optionCheckboxes[i].checked = option.classList.contains('correct-option');
                        }
                    });
                } else {
                    document.querySelector('.options-container').style.display = 'none';
                    document.querySelector('.add-option-btn').style.display = 'none';
                }
            }
            
            // Show the modal
            questionModal.style.display = 'flex';
            
            // Change save button text to "Update"
            saveQuestionBtn.textContent = 'Update Question';
            
            // Store the question ID to update later
            saveQuestionBtn.setAttribute('data-editing-id', questionId);
        }
    }

    // Function to duplicate a question
    function duplicateQuestion(questionId) {
        // Find the question element
        const questionElement = document.querySelector(`.question-card[data-question-id="${questionId}"]`);
        
        if (questionElement) {
            // Clone the question element
            const newQuestionElement = questionElement.cloneNode(true);
            
            // Update question ID and number
            questionCounter++;
            newQuestionElement.setAttribute('data-question-id', questionCounter);
            
            // Update question number
            const questionNumber = newQuestionElement.querySelector('.question-number');
            if (questionNumber) {
                questionNumber.textContent = questionCounter;
            }
            
            // Append the new question to the questions container
            questionsContainer.appendChild(newQuestionElement);
            
            // Setup action buttons for the new question
            setupQuestionActionButtons(newQuestionElement);
        }
    }

    // Function to reset the question form
    function resetQuestionForm() {
        // Reset question input
        document.querySelector('.question-input').value = '';
        
        // Reset option inputs and checkboxes
        const optionInputs = document.querySelectorAll('.option-input');
        const optionCheckboxes = document.querySelectorAll('.option-checkbox');
        
        optionInputs.forEach((input, i) => {
            input.value = i === 0 ? '' : '';
            optionCheckboxes[i].checked = false;
        });
        
        // Reset code editors
        const codeEditors = document.querySelectorAll('.code-editor');
        codeEditors.forEach(editor => {
            editor.value = '';
        });
        
        // Reset save button text
        saveQuestionBtn.textContent = 'Add Question';
        saveQuestionBtn.removeAttribute('data-editing-id');
    }

    // Event listener for the "Add Option" button
    const addOptionBtn = document.querySelector('.add-option-btn');
    addOptionBtn.addEventListener('click', () => {
        const optionsContainer = document.querySelector('.options-container');
        const optionCount = optionsContainer.children.length;
        
        const newOptionRow = document.createElement('div');
        newOptionRow.className = 'option-row';
        newOptionRow.innerHTML = `
            <input type="checkbox" class="option-checkbox">
            <input type="text" class="option-input" placeholder="Option ${optionCount + 1}">
            <button class="remove-option-btn">×</button>
        `;
        
        optionsContainer.appendChild(newOptionRow);
        
        // Add event listener for the remove button
        const removeBtn = newOptionRow.querySelector('.remove-option-btn');
        removeBtn.addEventListener('click', () => {
            newOptionRow.remove();
        });
    });

    // Add event listeners for existing option remove buttons
    document.querySelectorAll('.option-row').forEach(row => {
        // Add remove buttons to existing options
        if (!row.querySelector('.remove-option-btn')) {
            const removeBtn = document.createElement('button');
            removeBtn.className = 'remove-option-btn';
            removeBtn.textContent = '×';
            row.appendChild(removeBtn);
            
            removeBtn.addEventListener('click', () => {
                row.remove();
            });
        }
    });

    // Event listeners for tabs in the programming container
    const tabs = document.querySelectorAll('.tab');
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            // Remove active class from all tabs
            tabs.forEach(t => t.classList.remove('active'));
            
            // Add active class to clicked tab
            tab.classList.add('active');
            
            // Show corresponding tab content
            const tabName = tab.getAttribute('data-tab');
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
                if (content.getAttribute('data-content') === tabName) {
                    content.classList.add('active');
                }
            });
        });
    });

    // Event listeners for import tabs
    importTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            // Remove active class from all tabs
            importTabs.forEach(t => t.classList.remove('active'));
            
            // Add active class to clicked tab
            tab.classList.add('active');
            
            // Show corresponding tab content
            const tabName = tab.getAttribute('data-tab');
            document.querySelectorAll('.import-tab-content').forEach(content => {
                content.classList.remove('active');
                if (content.getAttribute('data-content') === tabName) {
                    content.classList.add('active');
                }
            });
        });
    });
    
    // Event listeners for import functionality
    importSpreadsheetBtn.addEventListener('click', () => {
        // Set active tab to spreadsheet
        importTabs.forEach(tab => tab.classList.remove('active'));
        document.querySelector('.import-tab[data-tab="spreadsheet"]').classList.add('active');
        
        // Show spreadsheet content, hide others
        document.querySelectorAll('.import-tab-content').forEach(content => {
            content.classList.remove('active');
        });
        document.querySelector('.import-tab-content[data-content="spreadsheet"]').classList.add('active');
        
        // Update modal title
        document.getElementById('import-modal-title').textContent = 'Import from Spreadsheet';
        
        // Show the import modal
        importModal.style.display = 'flex';
    });
    
    importGFormBtn.addEventListener('click', () => {
        // Set active tab to Google Forms
        importTabs.forEach(tab => tab.classList.remove('active'));
        document.querySelector('.import-tab[data-tab="google-forms"]').classList.add('active');
        
        // Show Google Forms content, hide others
        document.querySelectorAll('.import-tab-content').forEach(content => {
            content.classList.remove('active');
        });
        document.querySelector('.import-tab-content[data-content="google-forms"]').classList.add('active');
        
        // Update modal title
        document.getElementById('import-modal-title').textContent = 'Import from Google Forms';
        
        // Show the import modal            
        importModal.style.display = 'flex';            
    });
    
    closeImportModalBtn.addEventListener('click', () => {
        importModal.style.display = 'none';            
    });
    
    cancelImportBtn.addEventListener('click', () => {
        importModal.style.display = 'none';            
    });
    
    startImportBtn.addEventListener('click', () => {
        // In a real implementation, this would handle importing questions
        // For now, just close the modal
        importModal.style.display = 'none';
    });
    
    spreadsheetFileInput.addEventListener('change', () => {
        const file = spreadsheetFileInput.files[0];
        if (file) {
            selectedFileName.textContent = file.name;
        }            
    });
    
    spreadsheetUploadBtn.addEventListener('click', () => {
        spreadsheetFileInput.click();            
    });

    // Function to update preview based on question type
    function updatePreview(type) {
        // This function could be expanded to show different previews
        // based on the selected question type
    }

    // Initialize event listeners for true/false options
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('true-false-option')) {
            // Get the parent container
            const container = e.target.closest('.true-false-options');
            
            // Remove 'selected' class from all options
            container.querySelectorAll('.true-false-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Add 'selected' class to the clicked option
            e.target.classList.add('selected');
        }
    });
});