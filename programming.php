<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Question</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <!-- Quill CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.css" rel="stylesheet">
    <style>
/* Scoped styles for the question builder */
.question-builder {
    padding: 24px;
    font-family: Arial, sans-serif;
    background-color: #f8f9fa;
    border-radius: 8px;
}

/* Updated styles for the question builder header */
.question-header {
    display: flex;
    align-items: center;
    gap: 15px;
    background: #ffffff;
    padding: 12px 20px;
    border-radius: 8px;
    box-shadow: 0px 2px 6px rgba(0, 0, 0, 0.1);
    margin-bottom: 24px;
    transition: all 0.3s ease;
}

.back-btn {
    background: #f0f0f0;
    color: #444;
    padding: 8px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.back-btn:hover {
    background: #e0e0e0;
    transform: translateX(-2px);
}

.question-type, .question-points {
    padding: 10px 16px;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    background-color: white;
    color: #333;
    font-size: 14px;
    cursor: pointer;
    transition: border-color 0.2s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

.question-type:hover, .question-points:hover {
    border-color: #75343A;
}

.question-type:focus, .question-points:focus {
    outline: none;
    border-color: #75343A;
    box-shadow: 0 0 0 2px rgba(142, 104, 204, 0.2);
}

.save-btn {
    background: #75343A;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(142, 104, 204, 0.3);
    margin-left: auto;
}

.save-btn:hover {
    background: #7d5bb9;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(142, 104, 204, 0.4);
}

.save-btn:active {
    transform: translateY(1px);
    box-shadow: 0 1px 2px rgba(142, 104, 204, 0.4);
}

.question-container-wrapper {
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    margin-top: 24px;
    transition: all 0.3s ease;
}

.question-container-wrapper h2 {
    margin-top: 0;
    color: #333;
    font-size: 20px;
    margin-bottom: 20px;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 12px;
}

/* Quill Editor Styles */
#editor-container {
    margin-bottom: 20px;
}

.ql-editor {
    min-height: 100px;
    font-family: Arial, sans-serif;
    font-size: 15px;
    line-height: 1.5;
}

.ql-toolbar {
    border: 1px solid #e0e0e0;
    border-bottom: none;
    border-radius: 8px 8px 0 0;
    background-color: #f8f9fa;
}

.ql-container {
    border: 1px solid #e0e0e0;
    border-top: none;
    border-radius: 0 0 8px 8px;
    font-family: Arial, sans-serif;
    font-size: 15px;
}

.ql-editor:focus {
    outline: none;
}

.ql-container.ql-snow:focus {
    border-color: #75343A;
    box-shadow: 0 0 0 2px rgba(142, 104, 204, 0.2);
}

label {
    display: block;
    margin-bottom: 8px;
    color: #444;
    font-weight: 500;
}

/* New styles for programming question type */
.code-textarea {
    width: 100%;
    min-height: 40px;
    padding: 12px 16px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    font-family: monospace;
    font-size: 14px;
    line-height: 1.5;
    transition: all 0.3s ease;
    resize: vertical;
    margin-bottom: 20px;
    background-color: #f8f9fa;
    overflow: hidden; /* Hide scrollbar */
    box-sizing: border-box; /* Include padding in height calculation */
}

.code-textarea:focus {
    outline: none;
    border-color: #75343A;
    box-shadow: 0 0 0 2px rgba(142, 104, 204, 0.2);
    height: auto; /* Allow height to adjust when focused */
}

.test-case {
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 16px;
    border: 1px solid #e0e0e0;
    transition: all 0.3s ease;
    box-sizing: border-box;
}

.test-case:hover {
    border-color: #75343A;
}

.test-case-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.test-case-header h3 {
    margin: 0;
    font-size: 16px;
    color: #333;
}

.test-case-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 12px;
}

.hidden-checkbox {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 12px;
}

.hidden-checkbox input[type="checkbox"] {
    width: 16px;
    height: 16px;
    accent-color: #75343A;
    cursor: pointer;
}

.hidden-description {
    margin-top: 10px;
    display: none;
}

#addTestCase {
    background: #75343A;
    color: white;
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    margin-top: 16px;
    font-size: 14px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3);
}

#addTestCase:hover {
    background: #218838;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(40, 167, 69, 0.4);
}

#addTestCase:active {
    transform: translateY(1px);
    box-shadow: none;
}

#addTestCase::before {
    content: "+";
    font-size: 16px;
    font-weight: bold;
}

.remove-test-case {
    background: #75343A;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    padding: 4px 8px;
    font-size: 12px;
    transition: all 0.2s ease;
    font-weight: 500;   
}

.remove-test-case:hover {
    background: #c82333;
}

#questionForm {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

/* Style for the language dropdown */
select#programming_language {
    width: 100%;
    max-width: 300px;
    padding: 10px 16px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    background-color: white;
    font-family: Arial, sans-serif;
    font-size: 15px;
    color: #333;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-bottom: 20px;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23333' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: calc(100% - 12px) center;
    padding-right: 35px;
}

select#programming_language:focus {
    outline: none;
    border-color: #75343A;
    box-shadow: 0 0 0 2px rgba(142, 104, 204, 0.2);
}

select#programming_language:hover {
    border-color: #75343A;
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: #fefefe;
    margin: 15% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 500px;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    position: relative;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.modal-title {
    margin: 0;
    color: #333;
    font-size: 1.2em;
}

.close-modal {
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    background: none;
    border: none;
    padding: 0;
}

.close-modal:hover {
    color: #333;
}
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream

.modal-body {
    margin-bottom: 20px;
    color: #666;
}
<<<<<<< Updated upstream

.modal-footer {
    text-align: right;
}
<<<<<<< Updated upstream

.modal-btn {
    background: #75343A;
    color: white;
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
}

.modal-btn:hover {
    background: #7d5bb9;
}
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
    </style>
</head>
<body>
<div class="question-container">
    <?php include 'sidebar.php'; ?>

    <div class="main">

    <div class="question-builder">
    <!-- Header with back button, question type, and save button -->
    <div class="question-header">
    <a href="quiz_editor.php?exam_id=<?php echo isset($_GET['exam_id']) ? $_GET['exam_id'] : ''; ?>">
    <button class="back-btn"><i class="material-symbols-rounded">arrow_back</i></button>
    </a>
    <select class="question-type" onchange="handleQuestionTypeChange(this.value)">
        <option value="programming" selected>Programming</option>
        <option value="multiple-choice">Multiple Choice</option>
        <option value="true-false">True/False</option>
        <option value="fill-in-the-blank">Fill in the Blank</option>
    </select>
    <input type="number" class="question-points" id="question_points" value="1" min="1" max="100">
    <button type="button" class="save-btn" id="saveQuestionBtn">Save question</button>
</div>

       <div class="question-container-wrapper">
        <h2>Add a New Programming Question</h2>
        <form id="questionForm" action="save_question.php" method="POST">
            <input type="hidden" name="exam_id" value="<?php echo isset($_GET['exam_id']) ? $_GET['exam_id'] : ''; ?>">
            <input type="hidden" name="question_type" value="programming">
            <input type="hidden" name="points" id="points_input" value="1">
            <input type="hidden" name="question_id" id="question_id" value="<?php echo isset($_GET['question_id']) ? $_GET['question_id'] : ''; ?>">
            
            <label for="editor-container">Question Description:</label>
            <!-- Quill editor container -->
            <div id="editor-container"></div>
            <!-- Hidden textarea to store Quill content -->
            <textarea id="question" name="question" style="display:none;"></textarea>
            
            <label for="programming_language">Programming Language:</label>
            <select id="programming_language" name="programming_language" class="question-type">
                <option value="python">Python</option>
                <option value="cpp">C++</option>
                <option value="java">Java</option>
            </select>
            <p style="margin-top: -15px; margin-bottom: 20px; font-size: 12px; color: #666;">Select the programming language for this question. This will be shown to students.</p>
            
            <label for="starter_code">Starter Code (optional):</label>
            <textarea id="starter_code" name="starter_code" class="code-textarea"></textarea>
            
            <label>Test Cases:</label>
            <div id="test_cases">
                <div class="test-case" data-index="0">
                    <div class="test-case-header">
                        <h3>Test Case 1</h3>
                        <button type="button" class="remove-test-case">Remove</button>
                    </div>
                    <div class="test-case-content">
                        <div>
                            <label for="test_input_0">Input:</label>
                            <textarea id="test_input_0" name="test_input[]" class="code-textarea" required></textarea>
                        </div>
                        <div>
                            <label for="test_output_0">Expected Output:</label>
                            <textarea id="test_output_0" name="test_output[]" class="code-textarea" required></textarea>
                        </div>
                    </div>
                    <div class="hidden-checkbox">
                        <input type="checkbox" id="is_hidden_0" name="is_hidden[]" value="1">
                        <label for="is_hidden_0">Make this test case hidden from students</label>
                    </div>
                    <div class="hidden-description" id="hidden_desc_container_0">
                        <label for="hidden_description_0">Description (shown to students instead of the hidden test case):</label>
                        <textarea id="hidden_description_0" name="hidden_description[]" class="question-textarea"></textarea>
                    </div>
                </div>
            </div>
            <button type="button" id="addTestCase">Add Test Case</button>
        </form>
    </div>
</div>
<div id="modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Remove Test Case</h3>
            <button class="close-modal">&times;</button>
        </div>
        <div class="modal-body">
            <p>You need at least one test case.</p>
        </div>
        <div class="modal-footer">
            <button class="modal-btn" id="modalCloseBtn">OK</button>
        </div>
    </div>
</div>

<!-- Add Validation Modal -->
<div id="validationModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Validation Error</h3>
            <button class="close-modal">&times;</button>
        </div>
        <div class="modal-body">
            <p id="validationMessage"></p>
        </div>
        <div class="modal-footer">
            <button class="modal-btn" id="validationCloseBtn">OK</button>
        </div>
    </div>
</div>

<script src="assets/js/side.js"></script>
<!-- Quill JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js"></script>
<script> 
document.addEventListener("DOMContentLoaded", function () {
    const addTestCaseBtn = document.getElementById("addTestCase");
    const testCasesContainer = document.getElementById("test_cases");
    
    // Initialize Quill editor with full toolbar like multiple_choice.php and true_false.php
    const quill = new Quill('#editor-container', {
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline', 'strike'],
                ['blockquote', 'code-block'],
                [{ 'header': 1 }, { 'header': 2 }],
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                [{ 'script': 'sub' }, { 'script': 'super' }],
                [{ 'indent': '-1' }, { 'indent': '+1' }],
                ['clean']
            ]
        },
        placeholder: 'Enter your question description here...',
        theme: 'snow'
    });
    
    // Store Quill instance globally
    window.questionEditor = quill;
    
    // Function to handle question type changes
    window.handleQuestionTypeChange = function(value) {
        // Get the exam ID from the URL
        const urlParams = new URLSearchParams(window.location.search);
        const examId = urlParams.get('exam_id');
        const questionId = urlParams.get('question_id');
        
        let url = '';
        if (value === 'multiple-choice') {
            url = 'multiple_choice.php';
        } else if (value === 'true-false') {
            url = 'true_false.php';
        } else if (value === 'fill-in-the-blank') {
            url = 'fill_in_the_blank.php';
        } else {
            return; // Already on programming
        }
        
        // Add exam_id parameter
        if (examId) {
            url += '?exam_id=' + examId;
            
            // Add question_id parameter if editing an existing question
            if (questionId) {
                url += '&question_id=' + questionId;
            }
        }
        
        window.location.href = url;
    };
    
    // Auto-resize textareas function (for code textareas only)
    function setupAutoResize(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = (textarea.scrollHeight) + 'px';
        
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    }
    
    // Set up auto-resize for code textareas only
    document.querySelectorAll('.code-textarea').forEach(textarea => {
        setupAutoResize(textarea);
    });
    
    // Function to handle hiding/showing hidden description textarea
    function setupHiddenDescriptionToggle(checkbox, descContainer) {
        checkbox.addEventListener("change", function() {
            if (this.checked) {
                descContainer.style.display = "block";
            } else {
                descContainer.style.display = "none";
            }
        });
    }
    
    // Set up initial test case checkbox
    const initialCheckbox = document.getElementById("is_hidden_0");
    const initialDescContainer = document.getElementById("hidden_desc_container_0");
    setupHiddenDescriptionToggle(initialCheckbox, initialDescContainer);
    
    // Function to remove test case
    function setupRemoveButtons() {
        const removeButtons = document.querySelectorAll(".remove-test-case");
        const modal = document.getElementById("modal");
        const closeButton = document.querySelector(".close-modal");

        removeButtons.forEach(button => {
            button.addEventListener("click", function() {
                if (testCasesContainer.children.length > 1) {
                    const testCase = this.closest(".test-case");
                    testCase.remove();
                    
                    // Renumber remaining test cases
                    const testCases = testCasesContainer.querySelectorAll(".test-case");
                    testCases.forEach((tc, index) => {
                        tc.querySelector("h3").textContent = `Test Case ${index + 1}`;
                    });
                } else {
                    modal.style.display = "block"; // Show the modal
                }
            });
        });

        closeButton.addEventListener("click", function() {
            modal.style.display = "none"; // Hide the modal
        });

        window.addEventListener("click", function(event) {
            if (event.target == modal) {
                modal.style.display = "none"; // Hide the modal if clicked outside
            }
        });
    }
    
    // Set up initial remove button
    setupRemoveButtons();
    
    // Add new test case
    addTestCaseBtn.addEventListener("click", function () {
    const testCaseIndex = testCasesContainer.children.length;
    const testCaseDiv = document.createElement("div");
    testCaseDiv.classList.add("test-case");
    testCaseDiv.dataset.index = testCaseIndex;
    
    testCaseDiv.innerHTML = `
        <div class="test-case-header">
            <h3>Test Case ${testCaseIndex + 1}</h3>
            <button type="button" class="remove-test-case">Remove</button>
        </div>
        <div class="test-case-content">
            <div>
                <label for="test_input_${testCaseIndex}">Input:</label>
                <textarea id="test_input_${testCaseIndex}" name="test_input[]" class="code-textarea" required></textarea>
            </div>
            <div>
                <label for="test_output_${testCaseIndex}">Expected Output:</label>
                <textarea id="test_output_${testCaseIndex}" name="test_output[]" class="code-textarea" required></textarea>
            </div>
        </div>
        <div class="hidden-checkbox">
            <input type="checkbox" id="is_hidden_${testCaseIndex}" name="is_hidden[]" value="1">
            <label for="is_hidden_${testCaseIndex}">Make this test case hidden from students</label>
        </div>
        <div class="hidden-description" id="hidden_desc_container_${testCaseIndex}">
            <label for="hidden_description_${testCaseIndex}">Description (shown to students instead of the hidden test case):</label>
            <textarea id="hidden_description_${testCaseIndex}" name="hidden_description[]" class="question-textarea"></textarea>
        </div>
    `;
    
    testCasesContainer.appendChild(testCaseDiv);
    
    // Set up auto-resize for new textareas
    testCaseDiv.querySelectorAll('textarea').forEach(textarea => {
        setupAutoResize(textarea);
    });
    
    // Set up the hidden description toggle for the new test case
    const newCheckbox = document.getElementById(`is_hidden_${testCaseIndex}`);
    const newDescContainer = document.getElementById(`hidden_desc_container_${testCaseIndex}`);
    setupHiddenDescriptionToggle(newCheckbox, newDescContainer);
    
    // Set up remove button functionality
    setupRemoveButtons();
});

    // Add save button functionality
    const saveQuestionBtn = document.getElementById("saveQuestionBtn");
    const questionForm = document.getElementById("questionForm");
    const pointsInput = document.getElementById("points_input");

<<<<<<< Updated upstream
    // Modal functionality
    const validationModal = document.getElementById("validationModal");
    const validationMessage = document.getElementById("validationMessage");
    const closeButtons = document.querySelectorAll(".close-modal");
    const modalCloseBtn = document.getElementById("modalCloseBtn");
    const validationCloseBtn = document.getElementById("validationCloseBtn");

    function showValidationModal(message) {
        validationMessage.textContent = message;
        validationModal.style.display = "block";
    }

    function hideValidationModal() {
        validationModal.style.display = "none";
    }

    // Add click event listeners to close buttons
    closeButtons.forEach(button => {
        button.addEventListener("click", function() {
            const modal = this.closest(".modal");
            modal.style.display = "none";
        });
=======
    // Add event listener for save button
    saveQuestionBtn.addEventListener("click", function() {
        // Update points value
        pointsInput.value = document.getElementById('question_points').value;
        
        // Get Quill content and save it to the hidden textarea
        const questionTextarea = document.getElementById('question');
        questionTextarea.value = quill.root.innerHTML;
        
        // Validate the form
        if (!validateForm()) {
            return;
        }
        
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
        // Submit the form
        questionForm.submit();
>>>>>>> Stashed changes
    });

    // Add click event listeners to OK buttons
    modalCloseBtn.addEventListener("click", function() {
        document.getElementById("modal").style.display = "none";
    });

    validationCloseBtn.addEventListener("click", function() {
        validationModal.style.display = "none";
    });

    // Close modal when clicking outside
    window.addEventListener("click", function(event) {
        if (event.target.classList.contains("modal")) {
            event.target.style.display = "none";
        }
    });

    // Update validation function to use modal
    function validateForm() {
        // Check if question is empty
        const questionText = quill.getText().trim();
        if (questionText === "") {
            showValidationModal("Please enter a question description");
            return false;
        }
        
        // Check if there's at least one test case
        const testCases = document.querySelectorAll(".test-case");
        if (testCases.length === 0) {
            showValidationModal("Please add at least one test case");
            return false;
        }
        
        // Validate each test case
        for (let i = 0; i < testCases.length; i++) {
            const testCase = testCases[i];
            const input = testCase.querySelector(`[name="test_input[]"]`).value.trim();
            const output = testCase.querySelector(`[name="test_output[]"]`).value.trim();
            
            if (input === "" || output === "") {
                showValidationModal(`Please fill in both input and output for test case ${i + 1}`);
                return false;
            }
        }
        
        // Check if exam_id is present
        const examId = document.querySelector('input[name="exam_id"]').value;
        if (!examId) {
            showValidationModal("Missing exam ID");
            return false;
        }
        
        return true;
    }

    // Update save button event listener
    saveQuestionBtn.addEventListener("click", function() {
        // Update points value
        pointsInput.value = document.getElementById('question_points').value;
        
        // Get Quill content and save it to the hidden textarea
        const questionTextarea = document.getElementById('question');
        questionTextarea.value = quill.root.innerHTML;
        
        // Validate the form
        if (!validateForm()) {
            return;
        }
        
<<<<<<< Updated upstream
        // Get the question content from TinyMCE
        if (window.questionEditor) {
            const questionContent = window.questionEditor.getContent();
            if (!questionContent) {
                showValidationModal("Please enter a question description");
                return;
            }
            // Update the hidden textarea with TinyMCE content
            document.getElementById("question").value = questionContent;
        }
        
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
        // Submit the form
        questionForm.submit();
    });

<<<<<<< Updated upstream
    // Update error handling in loadQuestionData
=======
    // Form validation function
    function validateForm() {
        // Check if question is empty
        const questionText = quill.getText().trim();
        if (questionText === "") {
            alert("Please enter a question description");
            return false;
        }
        
        // Check if there's at least one test case
        const testCases = document.querySelectorAll(".test-case");
        if (testCases.length === 0) {
            alert("Please add at least one test case");
            return false;
        }
        
        // Validate each test case
        for (let i = 0; i < testCases.length; i++) {
            const testCase = testCases[i];
            const input = testCase.querySelector(`[name="test_input[]"]`).value.trim();
            const output = testCase.querySelector(`[name="test_output[]"]`).value.trim();
            
            if (input === "" || output === "") {
                alert(`Please fill in both input and output for test case ${i + 1}`);
                return false;
            }
        }
        
        // Check if exam_id is present
        const examId = document.querySelector('input[name="exam_id"]').value;
        if (!examId) {
            alert("Missing exam ID");
            return false;
        }
        
        return true;
    }

    // Add this function to load question data
>>>>>>> Stashed changes
    function loadQuestionData(questionId) {
        console.log('Loading question data for ID:', questionId);
        
        if (!window.questionEditor) {
            console.error('Quill editor not initialized yet');
            return;
        }

        fetch(`get_question.php?question_id=${questionId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text();
            })
            .then(text => {
                console.log('Raw response:', text);
                try {
                    const data = JSON.parse(text);
                    if (!data.success) {
                        throw new Error(data.message || 'Failed to load question data');
                    }
                    
                    // Set question text in Quill
                    window.questionEditor.clipboard.dangerouslyPasteHTML(data.question.question_text || '');
                    
                    // Set points
                    const pointsSelect = document.querySelector('.question-points');
                    if (data.question.points) {
                        pointsSelect.value = data.question.points;
                        document.getElementById('points_input').value = data.question.points;
                    }

                    // Set starter code
                    if (data.programming && data.programming.starter_code) {
                        document.getElementById('starter_code').value = data.programming.starter_code;
                    }
                    
                    // Set programming language if available
                    if (data.programming && data.programming.language) {
                        const languageDropdown = document.getElementById('programming_language');
                        const language = data.programming.language.toLowerCase();
                        
                        // Find the option that matches the language
                        for (let i = 0; i < languageDropdown.options.length; i++) {
                            if (languageDropdown.options[i].value === language) {
                                languageDropdown.selectedIndex = i;
                                break;
                            }
                        }
                    }

                    // Clear and rebuild test cases
                    const testCasesContainer = document.getElementById('test_cases');
                    testCasesContainer.innerHTML = '';

                    if (data.test_cases && data.test_cases.length > 0) {
                        data.test_cases.forEach((testCase, index) => {
                            console.log('Processing test case:', testCase); // Debug log
                            
                            const testCaseDiv = document.createElement('div');
                            testCaseDiv.classList.add('test-case');
                            testCaseDiv.dataset.index = index;

                            testCaseDiv.innerHTML = `
                                <div class="test-case-header">
                                    <h3>Test Case ${index + 1}</h3>
                                    <button type="button" class="remove-test-case">Remove</button>
                                </div>
                                <div class="test-case-content">
                                    <div>
                                        <label for="test_input_${index}">Input:</label>
                                        <textarea id="test_input_${index}" name="test_input[]" class="code-textarea" required>${testCase.input || ''}</textarea>
                                    </div>
                                    <div>
                                        <label for="test_output_${index}">Expected Output:</label>
                                        <textarea id="test_output_${index}" name="test_output[]" class="code-textarea" required>${testCase.expected_output || ''}</textarea>
                                    </div>
                                </div>
                                <div class="hidden-checkbox">
                                    <input type="checkbox" id="is_hidden_${index}" name="is_hidden[]" value="1" ${testCase.is_hidden == 1 ? 'checked' : ''}>
                                    <label for="is_hidden_${index}">Make this test case hidden from students</label>
                                </div>
                                <div class="hidden-description" id="hidden_desc_container_${index}" style="display: ${testCase.is_hidden == 1 ? 'block' : 'none'}">
                                    <label for="hidden_description_${index}">Description (shown to students instead of the hidden test case):</label>
                                    <textarea id="hidden_description_${index}" name="hidden_description[]" class="question-textarea">${testCase.description || ''}</textarea>
                                </div>
                            `;

                            testCasesContainer.appendChild(testCaseDiv);

                            // Set up auto-resize for new textareas
                            testCaseDiv.querySelectorAll('textarea').forEach(textarea => {
                                setupAutoResize(textarea);
                            });

                            // Set up hidden description toggle
                            const newCheckbox = document.getElementById(`is_hidden_${index}`);
                            const newDescContainer = document.getElementById(`hidden_desc_container_${index}`);
                            setupHiddenDescriptionToggle(newCheckbox, newDescContainer);
                        });

                        // Set up remove buttons
                        setupRemoveButtons();
                    }

                    // Update form title and save button
                    document.querySelector('.question-container-wrapper h2').textContent = 'Edit Programming Question';
                    document.getElementById('saveQuestionBtn').textContent = 'Update Question';
                } catch (e) {
                    console.error('JSON parse error:', e);
                    throw new Error('Invalid JSON response from server');
                }
            })
            .catch(error => {
                console.error('Error loading question:', error);
                showValidationModal('Error loading question: ' + error.message);
            });
    }

    // Check if we're in edit mode and load question data
    const questionId = document.getElementById('question_id').value;
    if (questionId) {
        setTimeout(() => {
            loadQuestionData(questionId);
        }, 100); // Small delay to ensure editor is ready
    }
});
</script>
</body>
</html>