<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExamMaker - Create Interactive Exams</title>
    <style>
        :root {
            --primary: #8854C0;
            --primary-light: #a671d6;
            --secondary: #30b9a5;
            --dark: #333;
            --light: #f8f9fa;
            --light-gray: #f1f1f1;
            --border-color: #e0e0e0;
            --text-gray: #5a5a5a;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        }

        body {
            background-color: #f8f8f8;
            color: var(--dark);
        }

        header {
            background-color: white;
            border-bottom: 1px solid var(--border-color);
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .back-button {
            color: var(--dark);
            text-decoration: none;
            display: flex;
            align-items: center;
            font-size: 14px;
        }
        
        .back-button i {
            margin-right: 8px;
        }

        .quiz-title {
            font-weight: normal;
            font-size: 16px;
            color: var(--dark);
        }

        .header-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-settings {
            background-color: white;
            color: var(--dark);
            border: 1px solid var(--border-color);
        }

        .btn-preview {
            background-color: white;
            color: var(--dark);
            border: 1px solid var(--border-color);
        }

        .btn-publish {
            background-color: var(--primary);
            color: white;
        }

        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 15px;
        }

        .quiz-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            padding: 30px;
        }

        h1 {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 25px;
            color: var(--dark);
        }

        .topic-input {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 16px;
            margin-bottom: 30px;
        }

        .search-container {
            position: relative;
            max-width: 300px;
            margin-left: auto;
        }

        .search-input {
            width: 100%;
            padding: 10px 15px 10px 35px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 14px;
            background-color: var(--light-gray);
        }

        .search-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-gray);
        }

        .section-title {
            font-size: 16px;
            font-weight: 500;
            margin: 20px 0;
            color: var(--dark);
        }

        .question-types-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .section-divider {
            font-size: 14px;
            color: var(--text-gray);
            margin: 30px 0 15px;
            font-weight: 500;
        }

        .question-type-btn {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background-color: white;
            cursor: pointer;
            transition: all 0.2s;
        }

        .question-type-btn:hover {
            background-color: var(--light-gray);
        }

        .question-type-btn.selected {
            border-color: var(--primary);
            background-color: #f9f4ff;
        }

        .question-type-icon {
            width: 28px;
            height: 28px;
            background-color: var(--primary);
            color: white;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
        }

        .interactive-icon {
            background-color: #30b9a5;
        }

        .open-ended-icon {
            background-color: #3f8cff;
        }

        .question-preview {
            margin-top: 30px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
        }

        .preview-header {
            background-color: #490A73;
            color: white;
            padding: 15px;
            font-weight: 500;
        }

        .preview-content {
            padding: 20px;
            background-color: var(--light);
        }

        .preview-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-top: 15px;
        }

        .preview-option {
            aspect-ratio: 1/1;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: bold;
            border-radius: 4px;
        }

        .option-a {
            background-color: #2271b3;
        }
        
        .option-b {
            background-color: #30b9a5;
        }
        
        .option-c {
            background-color: #f1af41;
        }
        
        .option-d {
            background-color: #d75c55;
        }

        .question-description {
            margin-top: 15px;
            padding: 15px;
            background-color: white;
            border-radius: 8px;
        }

        .description-title {
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 10px;
        }

        .description-text {
            color: var(--text-gray);
            line-height: 1.5;
            font-size: 14px;
        }

        /* Question card styling for when questions are added */
        .question-card {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
        }

        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .question-input {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 16px;
            margin-bottom: 15px;
        }

        .options-container {
            margin-bottom: 15px;
        }

        .option-row {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .option-checkbox {
            margin-right: 10px;
        }

        .option-input {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 14px;
        }

        .add-option-btn {
            background-color: white;
            border: 1px dashed var(--border-color);
            color: var(--text-gray);
            padding: 10px;
            width: 100%;
            text-align: center;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .add-option-btn:hover {
            background-color: var(--light-gray);
        }

        .programming-container {
            margin-top: 15px;
        }

        .tabs {
            display: flex;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 15px;
        }

        .tab {
            padding: 10px 15px;
            cursor: pointer;
            font-size: 14px;
            color: var(--text-gray);
            border-bottom: 2px solid transparent;
        }

        .tab.active {
            color: var(--primary);
            border-bottom: 2px solid var(--primary);
            font-weight: 500;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .code-editor {
            width: 100%;
            min-height: 200px;
            font-family: monospace;
            padding: 15px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 14px;
            line-height: 1.5;
            background-color: #f8f8f8;
        }

        .language-select {
            margin-top: 15px;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 14px;
            width: 200px;
        }

        .add-question-container {
            margin-top: 30px;
            text-align: center;
        }

        .add-question-btn {
            padding: 15px 30px;
            background-color: #f8f4ff;
            color: var(--primary);
            border: 1px dashed var(--primary);
            border-radius: 4px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
        }

        .add-question-btn:hover {
            background-color: #f0e6ff;
        }

        /* Modal for question type selection */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            display: none;
        }

        .modal-content {
            background-color: white;
            width: 90%;
            max-width: 800px;
            border-radius: 8px;
            padding: 30px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 600;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            color: var(--text-gray);
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .question-types-grid {
                grid-template-columns: 1fr;
            }
            
            .preview-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <header>
        <a href="#" class="back-button">
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

    <div class="container">
        <div class="quiz-container">
            <h1>Create a new quiz</h1>
            
            <input type="text" class="topic-input" placeholder="Enter topic name">
            
            <div class="search-container">
                <i class="search-icon">🔍</i>
                <input type="text" class="search-input" placeholder="Search questions">
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
            
            <div class="question-preview">
                <div class="preview-header">
                    Questions with more than one correct answer
                </div>
                <div class="preview-content">
                    <div class="preview-grid">
                        <div class="preview-option option-a">A</div>
                        <div class="preview-option option-b">B</div>
                        <div class="preview-option option-c">C</div>
                        <div class="preview-option option-d">D</div>
                    </div>
                </div>
                <div class="question-description">
                    <h3 class="description-title">Multiple Choice</h3>
                    <p class="description-text">Check for retention by asking students to pick one or more correct answers. Use text, images, or math equations to spice things up!</p>
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
        
        // Variables to track state
        let selectedQuestionType = 'multiple-choice';
        let questionCounter = 0;
        
        // Event Listeners
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
        
        // Setup tabs in programming container
        const tabs = document.querySelectorAll('.tab');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                // Remove active class from all tabs and content
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                // Add active class to clicked tab
                tab.classList.add('active');
                
                // Show corresponding content
                const contentId = tab.getAttribute('data-tab');
                document.querySelector(`.tab-content[data-content="${contentId}"]`).classList.add('active');
            });
        });
        
        // Add "Add option" button functionality
        document.querySelector('.add-option-btn').addEventListener('click', () => {
            const optionsContainer = document.querySelector('.options-container');
            const optionCount = optionsContainer.children.length + 1;
            
            const optionRow = document.createElement('div');
            optionRow.className = 'option-row';
            optionRow.innerHTML = `
                <input type="checkbox" class="option-checkbox">
                <input type="text" class="option-input" placeholder="Option ${optionCount}">
            `;
            
            optionsContainer.appendChild(optionRow);
        });
        
        function updatePreview(type) {
            const previewHeader = document.querySelector('.preview-header');
            const descriptionTitle = document.querySelector('.description-title');
            const descriptionText = document.querySelector('.description-text');
            
            if (type === 'multiple-choice') {
                previewHeader.textContent = 'Questions with more than one correct answer';
                descriptionTitle.textContent = 'Multiple Choice';
                descriptionText.textContent = 'Check for retention by asking students to pick one or more correct answers. Use text, images, or math equations to spice things up!';
            } else if (type === 'programming') {
                previewHeader.textContent = 'Test programming skills with custom code challenges';
                descriptionTitle.textContent = 'Programming Question';
                descriptionText.textContent = 'Create coding problems with starter code, test cases, and auto-grading. Support for multiple programming languages.';
            }
            // Add more types as needed
        }
        
        function addQuestion() {
            questionCounter++;
            const questionId = `question-${questionCounter}`;
            
            const questionCard = document.createElement('div');
            questionCard.className = 'question-card';
            questionCard.id = questionId;
            
            // Get the question text from the modal
            const questionText = document.querySelector('.question-input').value || `Question ${questionCounter}`;
            
            if (selectedQuestionType === 'multiple-choice') {
                // Create a multiple choice question card
                
                // Get options from the modal
                const optionRows = document.querySelectorAll('.option-row');
                let optionsHTML = '';
                
                optionRows.forEach((row, index) => {
                    const optionText = row.querySelector('.option-input').value || `Option ${index + 1}`;
                    const isChecked = row.querySelector('.option-checkbox').checked;
                    
                    optionsHTML += `
                        <div class="option-row">
                            <input type="checkbox" class="option-checkbox" ${isChecked ? 'checked' : ''}>
                            <input type="text" class="option-input" value="${optionText}">
                        </div>
                    `;
                });
                
                questionCard.innerHTML = `
                    <div class="question-header">
                        <h3>Question ${questionCounter}</h3>
                        <div>
                            <button class="btn btn-settings">Edit</button>
                            <button class="btn btn-settings" onclick="deleteQuestion('${questionId}')">Delete</button>
                        </div>
                    </div>
                    <input type="text" class="question-input" value="${questionText}">
                    <div class="options-container">
                        ${optionsHTML}
                    </div>
                    <button class="add-option-btn">+ Add Option</button>
                `;
            } else if (selectedQuestionType === 'programming') {
                // Create a programming question card
                
                // Get programming data from the modal
                const instructions = document.querySelector('.tab-content[data-content="instructions"] textarea').value;
                const starterCode = document.querySelector('.tab-content[data-content="starter-code"] textarea').value;
                const language = document.querySelector('.language-select').value;
                
                questionCard.innerHTML = `
                    <div class="question-header">
                        <h3>Question ${questionCounter}</h3>
                        <div>
                            <button class="btn btn-settings">Edit</button>
                            <button class="btn btn-settings" onclick="deleteQuestion('${questionId}')">Delete</button>
                        </div>
                    </div>
                    <input type="text" class="question-input" value="${questionText}">
                    <div class="programming-container">
                        <div class="tabs">
                            <div class="tab active" data-tab="instructions">Instructions</div>
                            <div class="tab" data-tab="starter-code">Starter Code</div>
                            <div class="tab" data-tab="solution">Solution</div>
                            <div class="tab" data-tab="test-cases">Test Cases</div>
                        </div>
                        
                        <div class="tab-content active" data-content="instructions">
                            <textarea class="code-editor">${instructions}</textarea>
                        </div>
                        
                        <div class="tab-content" data-content="starter-code">
                            <textarea class="code-editor">${starterCode}</textarea>
                        </div>
                        
                        <div class="tab-content" data-content="solution">
                            <textarea class="code-editor"></textarea>
                        </div>
                        
                        <div class="tab-content" data-content="test-cases">
                            <textarea class="code-editor"></textarea>
                        </div>
                        
                        <select class="language-select">
                            <option value="javascript" ${language === 'javascript' ? 'selected' : ''}>JavaScript</option>
                            <option value="python" ${language === 'python' ? 'selected' : ''}>Python</option>
                            <option value="java" ${language === 'java' ? 'selected' : ''}>Java</option>
                            <option value="csharp" ${language === 'csharp' ? 'selected' : ''}>C#</option>
                            <option value="cpp" ${language === 'cpp' ? 'selected' : ''}>C++</option>
                        </select>
                    </div>
                `;
                
                // Set up tabs for the new card
                setupTabs(questionId);
            }
            
            questionsContainer.appendChild(