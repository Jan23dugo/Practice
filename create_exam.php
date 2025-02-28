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
                <div class="question-type-btn selected" data-type="multiple-choice">
                    <div class="question-type-icon">✓</div>
                    Multiple Choice
                </div>
                <div class="question-type-btn" data-type="fill-blank">
                    <div class="question-type-icon">□</div>
                    Fill in the Blank
                </div>
                <div class="question-type-btn" data-type="passage">
                    <div class="question-type-icon">¶</div>
                    Passage
                </div>
                <div class="question-type-btn" data-type="programming">
                    <div class="question-type-icon">💻</div>
                    Programming
                </div>
            </div>
            
            <div class="section-divider">Interactive/Higher-order thinking</div>
            
            <div class="question-types-grid">
                <div class="question-type-btn" data-type="match">
                    <div class="question-type-icon interactive-icon">🔄</div>
                    Match
                </div>
                <div class="question-type-btn" data-type="reorder">
                    <div class="question-type-icon interactive-icon">↕️</div>
                    Reorder
                </div>
                <div class="question-type-btn" data-type="drag-drop">
                    <div class="question-type-icon interactive-icon">↪️</div>
                    Drag and Drop
                </div>
                <div class="question-type-btn" data-type="drop-down">
                    <div class="question-type-icon interactive-icon">↘️</div>
                    Drop Down
                </div>
                <div class="question-type-btn" data-type="hotspot">
                    <div class="question-type-icon interactive-icon">📍</div>
                    Hotspot
                </div>
                <div class="question-type-btn" data-type="labeling">
                    <div class="question-type-icon interactive-icon">🏷️</div>
                    Labeling
                </div>
                <div class="question-type-btn" data-type="categorize">
                    <div class="question-type-icon interactive-icon">📊</div>
                    Categorize
                </div>
            </div>
            
            <div class="section-divider">Open ended responses</div>
            
            <div class="question-types-grid">
                <div class="question-type-btn" data-type="draw">
                    <div class="question-type-icon open-ended-icon">✏️</div>
                    Draw
                </div>
                <div class="question-type-btn" data-type="open-ended">
                    <div class="question-type-icon open-ended-icon">📝</div>
                    Open Ended
                </div>
                <div class="question-type-btn" data-type="video">
                    <div class="question-type-icon open-ended-icon">🎥</div>
                    Video Response
                </div>
                <div class="question-type-btn" data-type="audio">
                    <div class="question-type-icon open-ended-icon">🎤</div>
                    Audio Response
                </div>
                <div class="question-type-btn" data-type="poll">
                    <div class="question-type-icon open-ended-icon">📊</div>
                    Poll
                </div>
                <div class="question-type-btn" data-type="word-cloud">
                    <div class="question-type-icon open-ended-icon">☁️</div>
                    Word Cloud
                </div>
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

    <script>
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
                
                // If programming, show the modal
                if (selectedQuestionType === 'programming') {
                    modalTitle.textContent = 'Add Programming Question';
                    programmingContainer.style.display = 'block';
                    document.querySelector('.options-container').style.display = 'none';
                    document.querySelector('.add-option-btn').style.display = 'none';
                    questionModal.style.display = 'flex';
                } else if (selectedQuestionType === 'multiple-choice') {
                    modalTitle.textContent = 'Add Multiple Choice Question';
                    programmingContainer.style.display = 'none';
                    document.querySelector('.options-container').style.display = 'block';
                    document.querySelector('.add-option-btn').style.display = 'block';
                    questionModal.style.display = 'flex';
                }
            });
        });
        
        // Event listeners for question modal
        closeModalBtn.addEventListener('click', () => {
            questionModal.style.display = 'none';
        });
        
        cancelQuestionBtn.addEventListener('click', () => {
            questionModal.style.display = 'none';
        });
        
        saveQuestionBtn.addEventListener('click', () => {
            addQuestion();
            questionModal.style.display = 'none';
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
    </script>                
</body>                
</html>
            