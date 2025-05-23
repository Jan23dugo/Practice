<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Question</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <!-- Add Quill CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css" rel="stylesheet">
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

/* Quill editor container styles */
#editor-container {
    height: 200px;
    margin-bottom: 24px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
}

/* Custom toolbar styles */
.ql-toolbar.ql-snow {
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
    border: 1px solid #e0e0e0;
    border-bottom: none;
    background-color: #f9f9f9;
}

.ql-container.ql-snow {
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
    border: 1px solid #e0e0e0;
    border-top: none;
    font-family: Arial, sans-serif;
    font-size: 15px;
}

/* Quill focus styles */
.ql-container.ql-snow:focus-within {
    border-color: #75343A;
    box-shadow: 0 0 0 2px rgba(142, 104, 204, 0.2);
}

/* Hidden question textarea (used for form submission) */
#question {
    display: none;
}

label {
    display: block;
    margin-bottom: 8px;
    color: #444;
    font-weight: 500;
}

.true-false-options {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.option {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    background-color: white;
    cursor: pointer;
    transition: all 0.2s ease;
}

.option:hover {
    background-color: #f8f9fa;
    border-color: #75343A;
}

.option input[type="radio"] {
    accent-color: #75343A;
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.option span {
    font-size: 15px;
    color: #333;
}

#questionForm {
    display: flex;
    flex-direction: column;
    gap: 16px;
}
    </style>
    <!-- Add Modal Styles -->
    <style>
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

        .modal-body {
            margin-bottom: 20px;
            color: #666;
        }

        .modal-footer {
            text-align: right;
        }

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
    </style>
</head>
<body>
<!-- Add Modal HTML -->
<div id="validationModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Validation Error</h3>
            <button class="close-modal">&times;</button>
        </div>
        <div class="modal-body">
            <p id="modalMessage"></p>
        </div>
        <div class="modal-footer">
            <button class="modal-btn" id="modalCloseBtn">OK</button>
        </div>
    </div>
</div>

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
            <option value="multiple-choice">Multiple Choice</option>
            <option value="true-false" selected>True/False</option>
            <option value="programming">Programming</option>
        </select>
        <input type="number" class="question-points" id="question_points" value="1" min="1" max="100">
        <button type="button" class="save-btn" id="saveQuestionBtn">Save question</button>
    </div>

    <div class="question-container-wrapper">
        <h2>Add a New True/False Question</h2>
        <form id="questionForm" action="save_question.php" method="POST">
            <input type="hidden" name="question_type" value="true-false">
            <input type="hidden" name="exam_id" id="exam_id" value="<?php echo isset($_GET['exam_id']) ? $_GET['exam_id'] : ''; ?>">
            <input type="hidden" name="edit_mode" id="edit_mode" value="<?php echo isset($_GET['edit_mode']) ? $_GET['edit_mode'] : 'false'; ?>">
            <input type="hidden" name="question_id" id="question_id" value="<?php echo isset($_GET['question_id']) ? $_GET['question_id'] : ''; ?>">
            <input type="hidden" name="points" id="points_input" value="1">
            
            <label for="editor-container">Question Statement:</label>
            <!-- Quill editor container -->
            <div id="editor-container"></div>
            <!-- Hidden textarea to store Quill content -->
            <textarea id="question" name="question" required></textarea>

            <label>Correct Answer:</label>
            <div class="true-false-options">
                <div class="option">
                    <input type="radio" id="true" name="correct_answer" value="True" required>
                    <span>True</span>
                </div>
                <div class="option">
                    <input type="radio" id="false" name="correct_answer" value="False" required>
                    <span>False</span>
                </div>
            </div>
        </form>
    </div>
</div>
<script src="assets/js/side.js"></script>
<!-- Add Quill JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    // Initialize Quill with full toolbar like in multiple_choice.php
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
        placeholder: 'Type your question here...',
        theme: 'snow'
    });
    
    // Store quill instance for global access
    window.questionEditor = quill;
    
    // Add save button functionality
    const saveQuestionBtn = document.getElementById("saveQuestionBtn");
    const questionForm = document.getElementById("questionForm");
    const pointsInput = document.getElementById("points_input");
    const questionTextarea = document.getElementById("question");

    // Add event listener for save button
    saveQuestionBtn.addEventListener("click", function() {
        // Update the points value from the input field
        pointsInput.value = document.getElementById('question_points').value;
        
        // Update hidden textarea with Quill content
        questionTextarea.value = quill.root.innerHTML;
        
        // Validate the form
        if (!validateForm()) {
            return;
        }
        
        // Submit the form
        questionForm.submit();
    });

    // Modal functionality
    const modal = document.getElementById('validationModal');
    const modalMessage = document.getElementById('modalMessage');
    const closeModal = document.querySelector('.close-modal');
    const modalCloseBtn = document.getElementById('modalCloseBtn');

    function showModal(message) {
        modalMessage.textContent = message;
        modal.style.display = 'block';
    }

    function hideModal() {
        modal.style.display = 'none';
    }

    closeModal.onclick = hideModal;
    modalCloseBtn.onclick = hideModal;

    window.onclick = function(event) {
        if (event.target == modal) {
            hideModal();
        }
    }

    // Update validation function to use modal
    function validateForm() {
        const questionText = quill.getText().trim();
        if (questionText === "") {
            showModal("Please enter a question statement");
            return false;
        }
        
        // Check if a correct answer is selected
        const correctAnswer = document.querySelector('input[name="correct_answer"]:checked');
        if (!correctAnswer) {
            showModal("Please select either True or False as the correct answer");
            return false;
        }
        
        return true;
    }

    // Function to handle question type changes
    window.handleQuestionTypeChange = function(value) {
        const examId = document.getElementById('exam_id').value;
        const questionId = document.getElementById('question_id').value;
        
        let url = '';
        if (value === 'programming') {
            url = 'programming.php';
        } else if (value === 'multiple-choice') {
            url = 'multiple_choice.php';
        } else {
            return; // Already on true-false
        }
        
        // Add exam_id parameter
        url += '?exam_id=' + examId;
        
        // Add question_id parameter if editing an existing question
        if (questionId) {
            url += '&question_id=' + questionId;
        }
        
        window.location.href = url;
    };
    
    // Function to load question data for editing
    function loadQuestionData(questionId) {
        fetch(`get_question.php?question_id=${questionId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Question data:', data);
                if (data.success) {
                    // Set question text in Quill editor
                    if (window.questionEditor) {
                        window.questionEditor.clipboard.dangerouslyPasteHTML(data.question.question_text);
                    }
                    
                    // Set points
                    const pointsSelect = document.getElementById('question_points');
                    pointsSelect.value = data.question.points;
                    document.getElementById('points_input').value = data.question.points;
                    
                    // Set correct answer (true/false)
                    if (data.answers && data.answers.length > 0) {
                        // Find the correct answer
                        const correctAnswer = data.answers.find(answer => answer.is_correct == 1);
                        
                        if (correctAnswer) {
                            // Check the exact answer text without converting case
                            if (correctAnswer.answer_text === 'True') {
                                document.getElementById('true').checked = true;
                            } else if (correctAnswer.answer_text === 'False') {
                                document.getElementById('false').checked = true;
                            }
                        }
                    }
                    
                    // Update form title to indicate edit mode
                    const formTitle = document.querySelector('.question-container-wrapper h2');
                    if (formTitle) {
                        formTitle.textContent = 'Edit True/False Question';
                    }
                    
                    // Update save button text
                    if (saveQuestionBtn) {
                        saveQuestionBtn.textContent = 'Update Question';
                    }
                } else {
                    console.error('Error loading question:', data.message);
                    showModal('Error loading question: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showModal('An error occurred while loading the question: ' + error.message);
            });
    }

    // Check if we're in edit mode
    const questionId = document.getElementById('question_id').value;
    if (questionId) {
        console.log('Edit mode detected, question ID:', questionId);
        loadQuestionData(questionId);
    }
});
</script>
</body>
</html>