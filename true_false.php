<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Question</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
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
    border-color: #8e68cc;
}

.question-type:focus, .question-points:focus {
    outline: none;
    border-color: #8e68cc;
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
    border-color: #8e68cc;
    color: #8e68cc;
}

.save-btn {
    background: #8e68cc;
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

.question-textarea {
    width: 100%;
    height: 100px;
    padding: 12px 16px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    font-family: Arial, sans-serif;
    font-size: 15px;
    transition: all 0.3s ease;
    resize: vertical;
    margin-bottom: 20px;
}

.question-textarea:focus {
    outline: none;
    border-color: #8e68cc;
    box-shadow: 0 0 0 2px rgba(142, 104, 204, 0.2);
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
    border-color: #8e68cc;
}

.option input[type="radio"] {
    accent-color: #8e68cc;
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
    <select class="question-points" id="question_points">
        <option value="1">1 point</option>
        <option value="2">2 points</option>
    </select>
    
    <div class="toolbar">
        <button class="bold-btn"><b>B</b></button>
        <button class="italic-btn"><i>I</i></button>
        <button class="underline-btn"><u>U</u></button>
        <button class="strikethrough-btn"><s>S</s></button>
        <button class="superscript-btn">x¹</button>
        <button class="subscript-btn">x₂</button>
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
            
            <label for="question">Question Statement:</label>
            <textarea id="question" name="question" class="question-textarea" required></textarea>

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
<script>
document.addEventListener("DOMContentLoaded", function () {
    // Add save button functionality
    const saveQuestionBtn = document.getElementById("saveQuestionBtn");
    const questionForm = document.getElementById("questionForm");
    const pointsSelect = document.getElementById("question_points");
    const pointsInput = document.getElementById("points_input");

    // Add event listener for save button
    saveQuestionBtn.addEventListener("click", function() {
        // Update the points value from the select element
        pointsInput.value = pointsSelect.value;
        
        // Validate the form
        if (!validateForm()) {
            return;
        }
        
        // Submit the form
        questionForm.submit();
    });

    // Form validation function
    function validateForm() {
        const questionText = window.questionEditor ? window.questionEditor.getContent() : document.getElementById("question").value.trim();
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

    // Update points input when select changes
    pointsSelect.addEventListener("change", function() {
        pointsInput.value = this.value;
    });

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
    
    // First, load TinyMCE script dynamically
    const tinymceScript = document.createElement('script');
    tinymceScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js';
    tinymceScript.referrerPolicy = 'origin';
    document.head.appendChild(tinymceScript);
    
    // Initialize TinyMCE after script loads
    tinymceScript.onload = function() {
        // Initialize TinyMCE on question textarea only
        tinymce.init({
            selector: '#question',
            inline: false,
            menubar: false,
            toolbar: false,
            plugins: 'autoresize lists link image table code help wordcount',
            autoresize_bottom_margin: 20,
            height: 200,
            forced_root_block: false,
            remove_linebreaks: false,
            convert_newlines_to_brs: true,
            remove_trailing_brs: false,
            content_style: `
                body {
                    font-family: Arial, sans-serif;
                    font-size: 15px;
                    padding: 12px 16px;
                }
            `,
            setup: function(editor) {
                // Store the editor instance for later use with our toolbar
                window.questionEditor = editor;
                
                // Style the editor to match our design
                editor.on('init', function() {
                    const editorContainer = editor.getContainer();
                    editorContainer.style.borderRadius = '8px';
                    editorContainer.style.overflow = 'hidden';
                    editorContainer.style.border = '1px solid #e0e0e0';
                    
                    // Hide the statusbar
                    const statusbar = editorContainer.querySelector('.tox-statusbar');
                    if (statusbar) {
                        statusbar.style.display = 'none';
                    }
                    
                    // Check if we're in edit mode and load question data after editor is initialized
                    const questionId = document.getElementById('question_id').value;
                    if (questionId) {
                        loadQuestionData(questionId);
                    }
                });
            }
        });
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
                    // Set question text
                    if (window.questionEditor) {
                        window.questionEditor.setContent(data.question.question_text);
                    } else {
                        document.getElementById('question').value = data.question.question_text;
                    }
                    
                    // Set points
                    const pointsSelect = document.getElementById('question_points');
                    pointsSelect.value = data.question.points;
                    document.getElementById('points_input').value = data.question.points;
                    
                    // Set correct answer (true/false)
                    if (data.answers && data.answers.length > 0) {
                        // Log all answers to debug
                        console.log('Answers:', data.answers);
                        
                        // Find the correct answer
                        const correctAnswer = data.answers.find(answer => answer.is_correct == 1);
                        console.log('Correct answer:', correctAnswer);
                        
                        if (correctAnswer) {
                            // Check the exact answer text without converting case
                            if (correctAnswer.answer_text === 'True') {
                                document.getElementById('true').checked = true;
                                console.log('Setting TRUE as checked');
                            } else if (correctAnswer.answer_text === 'False') {
                                document.getElementById('false').checked = true;
                                console.log('Setting FALSE as checked');
                            } else {
                                console.log('Unknown answer value:', correctAnswer.answer_text);
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
    
    // Connect our existing toolbar buttons to TinyMCE commands
    const boldBtn = document.querySelector(".bold-btn");
    const italicBtn = document.querySelector(".italic-btn");
    const underlineBtn = document.querySelector(".underline-btn");
    const strikethroughBtn = document.querySelector(".strikethrough-btn");
    const superscriptBtn = document.querySelector(".superscript-btn");
    const subscriptBtn = document.querySelector(".subscript-btn");
    
    // Function to apply formatting using TinyMCE
    function applyTinyMCEFormatting(format) {
        if (window.questionEditor) {
            window.questionEditor.focus();
            
            switch(format) {
                case 'bold':
                    window.questionEditor.execCommand('Bold');
                    break;
                case 'italic':
                    window.questionEditor.execCommand('Italic');
                    break;
                case 'underline':
                    window.questionEditor.execCommand('Underline');
                    break;
                case 'strikethrough':
                    window.questionEditor.execCommand('Strikethrough');
                    break;
                case 'superscript':
                    window.questionEditor.execCommand('Superscript');
                    break;
                case 'subscript':
                    window.questionEditor.execCommand('Subscript');
                    break;
            }
        } else {
            alert("Editor is still initializing. Please try again in a moment.");
        }
    }
    
    // Add event listeners to toolbar buttons
    boldBtn.addEventListener("click", () => applyTinyMCEFormatting('bold'));
    italicBtn.addEventListener("click", () => applyTinyMCEFormatting('italic'));
    underlineBtn.addEventListener("click", () => applyTinyMCEFormatting('underline'));
    strikethroughBtn.addEventListener("click", () => applyTinyMCEFormatting('strikethrough'));
    superscriptBtn.addEventListener("click", () => applyTinyMCEFormatting('superscript'));
    subscriptBtn.addEventListener("click", () => applyTinyMCEFormatting('subscript'));
    
    // Add tooltip to toolbar buttons
    document.querySelectorAll('.toolbar button').forEach(btn => {
        btn.title = "Click to format selected text in the question statement";
    });

    // Check if we're in edit mode but don't load data here - we'll do it after TinyMCE initializes
    const questionId = document.getElementById('question_id').value;
    if (questionId) {
        console.log('Edit mode detected, question ID:', questionId);
        // We'll load the question data after TinyMCE is initialized
    }
});
</script>
</body>
</html>