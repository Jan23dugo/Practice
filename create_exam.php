<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExamMaker - Create Interactive Exams</title>
    <link rel="stylesheet" href="assets/css/styles.css">
        <!-- Linking Google Fonts For Icons -->
        <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="assets/css/exam.css">

</head>
<body>
<div class="container">
    <?php include 'sidebar.php'; ?>

    <div class="main">
    <header class="exam-header">
    <a href="exam.php" class="back-button">
        <i>&lt;</i>
        <h2 class="quiz-title">Untitled Quiz</h2>
    </a>
    <div class="header-actions">
        <button class="btn btn-settings">
            <i>⚙️</i>
            Settings
        </button>
        <button class="btn btn-preview">
            <i>▶️</i>
            Preview
        </button>
        <button class="btn btn-publish">
            <i>📤</i>
            Publish
        </button>
    </div>
</header>

    <div class="container-wrapper">
        <div class="quiz-container">
            <h1>Create a new quiz</h1>
            
            <input type="text" class="topic-input" placeholder="Enter topic name">
            
            <div class="import-container">
                <div class="import-buttons">
                    <button class="import-btn" id="import-spreadsheet-btn">
                        <i>📊</i>
                        Import from Spreadsheet
                    </button>
                    <button class="import-btn" id="import-gform-btn">
                        <i>📝</i>
                        Import from Google Forms
                    </button>
                </div>
                
                <div class="search-container">
                    <i class="search-icon">🔍</i>
                    <input type="text" class="search-input" placeholder="Search questions">
                </div>
            </div>
            
            <h2 class="section-title">Add a new question</h2>
            
            <div class="question-types-grid">
                <a href="multiple_choice.php?type=multiple-choice" class="question-type-btn selected" data-type="multiple-choice">
            <div class="question-type-icon">✓</div>
                Multiple Choice
                </a>
            <a href="add_question.php?type=fill-blank" class="question-type-btn" data-type="fill-blank">
                <div class="question-type-icon">□</div>
                Fill in the Blank
                </a>
            <a href="add_question.php?type=passage" class="question-type-btn" data-type="passage">
                <div class="question-type-icon">¶</div>
                True or False
                </a>
                <a href="add_question.php?type=programming" class="question-type-btn" data-type="programming">
                <div class="question-type-icon">💻</div>
                Programming
                </a>
            </div>
            

           
           
            
            <div id="questions-container">
                <!-- Added questions will appear here -->
            </div>
        </div>
    </div>

    <!-- Modal for adding specific question details -->
    <div class="modal-overlay" id="question-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add Multiple Choice Question</h3>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="text" class="question-input" placeholder="Enter your question">
                
                <div class="options-container">
                    <div class="option-row">
                        <input type="checkbox" class="option-checkbox">
                        <input type="text" class="option-input" placeholder="Option 1">
                    </div>
                    <div class="option-row">
                        <input type="checkbox" class="option-checkbox">
                        <input type="text" class="option-input" placeholder="Option 2">
                    </div>
                    <div class="option-row">
                        <input type="checkbox" class="option-checkbox">
                        <input type="text" class="option-input" placeholder="Option 3">
                    </div>
                    <div class="option-row">
                        <input type="checkbox" class="option-checkbox">
                        <input type="text" class="option-input" placeholder="Option 4">
                    </div>
                </div>
                
                <button class="add-option-btn">+ Add Option</button>
                
                <div class="programming-container" style="display: none;">
                    <div class="tabs">
                        <div class="tab active" data-tab="instructions">Instructions</div>
                        <div class="tab" data-tab="starter-code">Starter Code</div>
                        <div class="tab" data-tab="solution">Solution</div>
                        <div class="tab" data-tab="test-cases">Test Cases</div>
                    </div>
                    
                    <div class="tab-content active" data-content="instructions">
                        <textarea class="code-editor" placeholder="Enter detailed instructions for the programming question"></textarea>
                    </div>
                    
                    <div class="tab-content" data-content="starter-code">
                        <textarea class="code-editor" placeholder="Enter starter code that will be provided to students"></textarea>
                    </div>
                    
                    <div class="tab-content" data-content="solution">
                        <textarea class="code-editor" placeholder="Enter the solution code"></textarea>
                    </div>
                    
                    <div class="tab-content" data-content="test-cases">
                        <textarea class="code-editor" placeholder="Enter test cases to verify student code"></textarea>
                    </div>
                    
                    <select class="language-select">
                        <option value="javascript">JavaScript</option>
                        <option value="python">Python</option>
                        <option value="java">Java</option>
                        <option value="csharp">C#</option>
                        <option value="cpp">C++</option>
                    </select>
                </div>
                
                <div class="modal-actions" style="margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px;">
                    <button class="btn btn-settings" id="cancel-question">Cancel</button>
                    <button class="btn btn-publish" id="save-question">Add Question</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Modal -->
    <div class="modal-overlay import-modal-overlay" id="import-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="import-modal-title">Import Questions</h3>
                <button class="close-modal" id="close-import-modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="import-tabs">
                    <div class="import-tab active" data-tab="spreadsheet">Spreadsheet</div>
                    <div class="import-tab" data-tab="google-forms">Google Forms</div>
                </div>
                
                <div class="import-tab-content active" data-content="spreadsheet">
                    <div class="file-upload">
                        <p>Upload your spreadsheet file (.xlsx, .csv)</p>
                        <p>Make sure your spreadsheet has columns for question text, options, and correct answers</p>
                        <input type="file" id="spreadsheet-file" accept=".xlsx,.csv" style="display: none;">
                        <button class="file-upload-btn" id="spreadsheet-upload-btn">Choose File</button>
                        <p id="selected-file-name" style="margin-top: 10px; font-size: 14px;"></p>
                    </div>
                    
                    <div class="template-download" style="text-align: center; margin-bottom: 20px;">
                        <a href="#" style="color: var(--primary); text-decoration: none; font-size: 14px;">Download template spreadsheet</a>
                    </div>
                </div>
                
                <div class="import-tab-content" data-content="google-forms">
                    <p style="margin-bottom: 15px;">Paste the URL of your Google Form:</p>
                    <input type="text" class="google-form-input" placeholder="https://forms.google.com/...">
                    
                    <p style="margin-bottom: 15px; color: var(--text-gray);">
                        We'll import your Google Form questions and answer choices. Multiple choice, 
                        checkboxes, dropdown, and short answer questions are supported.
                    </p>
                </div>
                
                <div class="modal-actions" style="margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px;">
                    <button class="btn btn-settings" id="cancel-import">Cancel</button>
                    <button class="btn btn-publish" id="start-import">Import Questions</button>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>
<script src="assets/js/side.js"></script>
           
</body>                
</html>
            