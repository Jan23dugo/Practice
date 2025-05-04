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

label {
    display: block;
    margin-bottom: 8px;
    color: #444;
    font-weight: 500;
}

.choice {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 14px;
    padding: 8px;
    border-radius: 6px;
    transition: background-color 0.2s ease;
}

.choice:hover {
    background-color: #f8f9fa;
}

.choice input[type="text"] {
    flex: 1;
    padding: 10px 14px;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.choice input[type="text"]:focus {
    outline: none;
    border-color: #75343A;
    box-shadow: 0 0 0 2px rgba(142, 104, 204, 0.2);
}

.choice input[type="radio"] {
    accent-color: #75343A;
    width: 18px;
    height: 18px;
    cursor: pointer;
}

#addChoice {
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

#addChoice:hover {
    background: #218838;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(40, 167, 69, 0.4);
}

#addChoice:active {
    transform: translateY(1px);
    box-shadow: none;
}

#addChoice::before {
    content: "+";
    font-size: 16px;
    font-weight: bold;
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
            <option value="multiple-choice" selected>Multiple Choice</option>
            <option value="true-false">True/False</option>
            <option value="programming">Programming</option>
        </select>
        <input type="number" class="question-points" id="question_points" value="1" min="1" max="100">
        <button type="button" class="save-btn" id="saveQuestionBtn">Save question</button>
    </div>

    <div class="question-container-wrapper">
        <h2>Add a New Question</h2>
        <form id="questionForm" action="save_question.php" method="POST">
            <input type="hidden" name="question_type" value="multiple-choice">
            <input type="hidden" name="exam_id" id="exam_id" value="<?php echo isset($_GET['exam_id']) ? $_GET['exam_id'] : ''; ?>">
            <input type="hidden" name="question_id" id="question_id" value="<?php echo isset($_GET['question_id']) ? $_GET['question_id'] : ''; ?>">
            <input type="hidden" name="points" id="points_input" value="1">
            <input type="hidden" name="mode" value="<?php echo isset($_GET['question_id']) ? 'edit' : 'new'; ?>">
            
            <label for="editor-container">Question:</label>
            <!-- Quill editor container -->
            <div id="editor-container"></div>
            <!-- Hidden textarea to store Quill content -->
            <textarea id="question" name="question" style="display:none;"></textarea>

            <label>Answer Choices:</label>
            <div id="choices">
                <div class="choice">
                    <input type="text" name="choices[]" required>
                    <input type="radio" name="correct" value="0" required>
                </div>
                <div class="choice">
                    <input type="text" name="choices[]" required>
                    <input type="radio" name="correct" value="1" required>
                </div>
            </div>
            <button type="button" id="addChoice">Add Choice</button>
        </form>
    </div>
</div>
<script src="assets/js/side.js"></script>
<!-- Add Quill JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js"></script>
<script> 
document.addEventListener("DOMContentLoaded", function () {
    const addChoiceBtn = document.getElementById("addChoice");
    const choicesContainer = document.getElementById("choices");
    const saveQuestionBtn = document.getElementById("saveQuestionBtn");
    const questionForm = document.getElementById("questionForm");
    const pointsInput = document.getElementById("points_input");
    
    // Initialize Quill editor
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
        placeholder: 'Enter your question text here...',
        theme: 'snow'
    });
    
    // Make Quill editor globally available
    window.questionEditor = quill;
    
    // Add event listener for save button
    saveQuestionBtn.addEventListener("click", function() {
        // Update the points value from the input field
        pointsInput.value = document.getElementById('question_points').value;
        
        // Get Quill content and save it to the hidden textarea
        const questionTextarea = document.getElementById('question');
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
        // Get content from Quill editor
        const questionText = quill.getText().trim();
        if (questionText === "") {
            alert("Please enter a question");
            return false;
        }
        
        // Check if at least one choice is entered
        const choiceInputs = document.querySelectorAll('input[name="choices[]"]');
        let hasChoice = false;
        
        for (let i = 0; i < choiceInputs.length; i++) {
            if (choiceInputs[i].value.trim() !== "") {
                hasChoice = true;
                break;
            }
        }
        
        if (!hasChoice) {
            alert("Please enter at least one answer choice");
            return false;
        }
        
        // Check if a correct answer is selected
        const correctRadio = document.querySelector('input[name="correct"]:checked');
        if (!correctRadio) {
            alert("Please select a correct answer");
            return false;
        }
        
        return true;
    }

    // Add choice button functionality
    addChoiceBtn.addEventListener("click", function () {
        const choiceIndex = choicesContainer.children.length;
        const choiceDiv = document.createElement("div");
        choiceDiv.classList.add("choice");
        choiceDiv.innerHTML = `
            <input type="text" name="choices[]" required>
            <input type="radio" name="correct" value="${choiceIndex}" required>
        `;
        choicesContainer.appendChild(choiceDiv);
    });

    // Function to handle question type changes
    window.handleQuestionTypeChange = function(value) {
        const examId = document.getElementById('exam_id').value;
        const questionId = document.getElementById('question_id').value;
        
        let url = '';
        if (value === 'programming') {
            url = 'programming.php';
        } else if (value === 'true-false') {
            url = 'true_false.php';
        } else {
            return; // Already on multiple-choice
        }
        
        // Add exam_id parameter
        url += '?exam_id=' + examId;
        
        // Add question_id parameter if editing an existing question
        if (questionId) {
            url += '&question_id=' + questionId;
        }
        
        window.location.href = url;
    };

    // Check if we're in edit mode by looking for question_id parameter
    const urlParams = new URLSearchParams(window.location.search);
    const questionId = urlParams.get('question_id');
    
    if (questionId) {
        console.log('Edit mode detected, question ID:', questionId);
        
        // Fetch existing question data
        fetch(`get_question.php?question_id=${questionId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Question data:', data);
                if (data.success && data.question) {
                    // Set question text in Quill editor
                    quill.clipboard.dangerouslyPasteHTML(data.question.question_text);
                    
                    // Set points
                    const pointsInput = document.getElementById('question_points');
                    pointsInput.value = data.question.points;
                    document.getElementById('points_input').value = data.question.points;
                    
                    // Clear existing choices
                    const choicesContainer = document.getElementById('choices');
                    choicesContainer.innerHTML = '';
                    
                    // Add existing answers
                    if (Array.isArray(data.answers)) {
                        data.answers.forEach((answer, index) => {
                            const choiceDiv = document.createElement('div');
                            choiceDiv.classList.add('choice');
                            choiceDiv.innerHTML = `
                                <input type="text" name="choices[]" value="${answer.answer_text.replace(/"/g, '&quot;')}" required>
                                <input type="radio" name="correct" value="${index}" ${answer.is_correct == 1 ? 'checked' : ''} required>
                            `;
                            choicesContainer.appendChild(choiceDiv);
                        });
                    } else {
                        console.warn('No answers array found in response');
                        // Add two default empty choices
                        for (let i = 0; i < 2; i++) {
                            const choiceDiv = document.createElement('div');
                            choiceDiv.classList.add('choice');
                            choiceDiv.innerHTML = `
                                <input type="text" name="choices[]" required>
                                <input type="radio" name="correct" value="${i}" required>
                            `;
                            choicesContainer.appendChild(choiceDiv);
                        }
                    }
                    
                    // Update form title
                    const formTitle = document.querySelector('.question-container-wrapper h2');
                    if (formTitle) {
                        formTitle.textContent = 'Edit Question';
                    }
                    
                    // Update save button text
                    const saveBtn = document.querySelector('.save-btn');
                    if (saveBtn) {
                        saveBtn.textContent = 'Update question';
                    }
                } else {
                    console.error('Error loading question:', data.message);
                    alert('Error loading question: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while loading the question. Please try again.');
            });
    }
});
</script>
</body>
</html>