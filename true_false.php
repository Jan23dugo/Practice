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

.toolbar {
    display: flex;
    align-items: center;
    margin-left: auto;
    gap: 8px;
    padding-right: 16px;
    border-right: 1px solid #e0e0e0;
    margin-right: 16px;
}

.toolbar button {
    background: #ffffff;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    font-size: 14px;
    cursor: pointer;
    padding: 6px 12px;
    color: #444;
    transition: all 0.2s ease;
    min-width: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.toolbar button:hover {
    background: #f5f0ff;
    border-color: #75343A;
    color: #75343A;
}

.toolbar button:active {
    background: #75343A;
    color: white;
    border-color: #75343A;
}

.toolbar button.active {
    background: #75343A;
    color: white;
    border-color: #75343A;
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

/* Quill editor styles */
#editor-container {
    height: 200px;
    margin-bottom: 20px;
}

#editor-container .ql-editor {
    font-family: Arial, sans-serif;
    font-size: 15px;
    background-color: white;
    min-height: 100px;
}

.ql-toolbar.ql-snow {
    border-radius: 8px 8px 0 0;
    border: 1px solid #e0e0e0;
    border-bottom: none;
}

.ql-container.ql-snow {
    border-radius: 0 0 8px 8px;
    border: 1px solid #e0e0e0;
    font-family: Arial, sans-serif;
}

/* Hide Quill's own toolbar since we're using our custom one */
.ql-toolbar.ql-snow {
    display: none;
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
        <option value="multiple-choice">Multiple Choice</option>
        <option value="true-false" selected>True/False</option>
        <option value="programming">Programming</option>
    </select>
    <input type="number" class="question-points" id="question_points" value="1" min="1" max="100">
    
    <div class="toolbar">
        <button class="bold-btn" title="Bold"><b>B</b></button>
        <button class="italic-btn" title="Italic"><i>I</i></button>
        <button class="underline-btn" title="Underline"><u>U</u></button>
        <button class="strikethrough-btn" title="Strikethrough"><s>S</s></button>
        <button class="superscript-btn" title="Superscript">x¹</button>
        <button class="subscript-btn" title="Subscript">x₂</button>
    </div>

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
            <div id="editor-container"></div>
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
    // Initialize Quill
    const quill = new Quill('#editor-container', {
        theme: 'snow',
        modules: {
            toolbar: false // We're using our custom toolbar
        },
        placeholder: 'Type your question here...'
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

    // Form validation function
    function validateForm() {
        const questionText = quill.getText().trim();
        if (questionText === "") {
            alert("Please enter a question statement");
            return false;
        }
        
        // Check if a correct answer is selected
        const correctAnswer = document.querySelector('input[name="correct_answer"]:checked');
        if (!correctAnswer) {
            alert("Please select either True or False as the correct answer");
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
    
    // Function to apply formatting using Quill
    function applyQuillFormatting(format) {
        const quill = window.questionEditor;
        if (quill) {
            const selection = quill.getSelection();
            if (selection) {
                // Apply formatting to selection
                switch(format) {
                    case 'bold':
                        quill.format('bold', !quill.getFormat(selection).bold);
                        break;
                    case 'italic':
                        quill.format('italic', !quill.getFormat(selection).italic);
                        break;
                    case 'underline':
                        quill.format('underline', !quill.getFormat(selection).underline);
                        break;
                    case 'strike':
                        quill.format('strike', !quill.getFormat(selection).strike);
                        break;
                    case 'script':
                        // Toggle between superscript and subscript
                        const currentScript = quill.getFormat(selection).script;
                        if (currentScript === 'super') {
                            quill.format('script', false);
                        } else {
                            quill.format('script', 'super');
                        }
                        break;
                    case 'sub':
                        // Toggle between superscript and subscript
                        const currentSub = quill.getFormat(selection).script;
                        if (currentSub === 'sub') {
                            quill.format('script', false);
                        } else {
                            quill.format('script', 'sub');
                        }
                        break;
                }
            } else {
                // Focus the editor if no selection
                quill.focus();
            }
        } else {
            alert("Editor is still initializing. Please try again in a moment.");
        }
    }
    
    // Add event listeners to toolbar buttons
    document.querySelector(".bold-btn").addEventListener("click", () => applyQuillFormatting('bold'));
    document.querySelector(".italic-btn").addEventListener("click", () => applyQuillFormatting('italic'));
    document.querySelector(".underline-btn").addEventListener("click", () => applyQuillFormatting('underline'));
    document.querySelector(".strikethrough-btn").addEventListener("click", () => applyQuillFormatting('strike'));
    document.querySelector(".superscript-btn").addEventListener("click", () => applyQuillFormatting('script'));
    document.querySelector(".subscript-btn").addEventListener("click", () => applyQuillFormatting('sub'));
    
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
                    alert('Error loading question: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while loading the question: ' + error.message);
            });
    }

    // Check if we're in edit mode
    const questionId = document.getElementById('question_id').value;
    if (questionId) {
        console.log('Edit mode detected, question ID:', questionId);
        loadQuestionData(questionId);
    }

    // Toggle active class for toolbar buttons for visual feedback
    document.querySelectorAll('.toolbar button').forEach(button => {
        button.addEventListener('click', function() {
            // Toggle active class
            this.classList.toggle('active');
        });
    });
});
</script>
</body>
</html>