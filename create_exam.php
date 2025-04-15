<?php
session_start(); 
// Include database connection
include('config/config.php');

// Check if user is logged in as admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    // Not logged in as admin, redirect to admin login page
    header("Location: admin_login.php");
    exit();
}
?>
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
    <style>
    /* Modal styles */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        justify-content: center;
        align-items: center;
    }

    .modal-content {
        background-color: white;
        border-radius: 8px;
        width: 80%;
        max-width: 800px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 20px;
        border-bottom: 1px solid #e0e0e0;
    }

    .modal-title {
        margin: 0;
        font-size: 20px;
        color: #333;
    }

    .close-modal {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #666;
    }

    .modal-body {
        padding: 20px;
    }

    .import-tabs {
        display: flex;
        border-bottom: 1px solid #e0e0e0;
        margin-bottom: 20px;
    }

    .import-tab {
        padding: 10px 20px;
        cursor: pointer;
        font-weight: 500;
        color: #666;
        border-bottom: 2px solid transparent;
    }

    .import-tab.active {
        color: #8e68cc;
        border-bottom: 2px solid #8e68cc;
    }

    .import-tab-content {
        display: none;
    }

    .import-tab-content.active {
        display: block;
    }

    /* Question Bank Tab Styles */
    .question-bank-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #f0f0f0;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .filter-select {
        padding: 8px 12px;
        border: 1px solid #e0e0e0;
        border-radius: 4px;
        min-width: 150px;
    }

    .search-filter {
        flex-grow: 1;
    }

    .search-filter input {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #e0e0e0;
        border-radius: 4px;
    }

    .question-bank-list {
        max-height: 400px;
        overflow-y: auto;
        margin-bottom: 15px;
        border: 1px solid #f0f0f0;
        border-radius: 4px;
    }

    .question-item {
        padding: 12px 15px;
        border-bottom: 1px solid #f0f0f0;
    }

    .question-item:last-child {
        border-bottom: none;
    }

    .question-item-header {
        display: flex;
        gap: 10px;
    }

    .question-checkbox {
        display: flex;
        align-items: flex-start;
        padding-top: 3px;
    }

    .question-checkbox input {
        width: 18px;
        height: 18px;
        accent-color: #8e68cc;
    }

    .question-preview h4 {
        margin: 0 0 5px 0;
        font-size: 16px;
        color: #333;
    }

    .question-details {
        display: flex;
        gap: 10px;
        font-size: 12px;
    }

    .question-type, .question-difficulty {
        padding: 3px 8px;
        border-radius: 12px;
        background-color: #f0f0f0;
    }

    .question-type {
        background-color: #e6f7ff;
        color: #0070c0;
    }

    .question-difficulty {
        background-color: #fff4e6;
        color: #ff8c00;
    }

    .question-item-body {
        margin-top: 10px;
        margin-left: 28px;
        padding: 10px;
        background-color: #f8f9fa;
        border-radius: 4px;
        font-size: 14px;
    }

    .pagination {
        display: flex;
        justify-content: center;
        gap: 5px;
        margin: 15px 0;
    }

    .pagination-btn {
        padding: 5px 10px;
        border: 1px solid #e0e0e0;
        background-color: white;
        border-radius: 4px;
        cursor: pointer;
    }

    .pagination-btn.active {
        background-color: #8e68cc;
        color: white;
        border-color: #8e68cc;
    }

    .pagination-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .selected-count {
        text-align: right;
        font-size: 14px;
        color: #666;
        margin-bottom: 10px;
    }

    /* Spreadsheet Tab Styles */
    .file-upload {
        text-align: center;
        padding: 20px;
        border: 2px dashed #e0e0e0;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .file-upload p {
        margin: 5px 0;
        color: #666;
    }

    .file-upload-btn {
        margin-top: 15px;
        padding: 10px 20px;
        background-color: #f0f0f0;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 500;
    }

    .file-upload-btn:hover {
        background-color: #e0e0e0;
    }

    .spreadsheet-preview {
        margin-bottom: 20px;
    }

    .preview-container {
        border: 1px solid #e0e0e0;
        border-radius: 4px;
        padding: 15px;
        max-height: 200px;
        overflow-x: auto;
        overflow-y: auto;
    }

    .no-file-selected {
        color: #999;
        text-align: center;
        padding: 20px;
    }

    .column-mapping {
        margin-bottom: 20px;
    }

    .mapping-container {
        display: grid;
        gap: 15px;
    }

    .mapping-row {
        display: grid;
        grid-template-columns: 150px 1fr;
        align-items: center;
        gap: 10px;
    }

    .mapping-select {
        padding: 8px 12px;
        border: 1px solid #e0e0e0;
        border-radius: 4px;
    }

    .template-link {
        color: #8e68cc;
        text-decoration: none;
        font-size: 14px;
    }

    .template-link:hover {
        text-decoration: underline;
    }

    .modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
        padding-top: 15px;
        border-top: 1px solid #f0f0f0;
    }

    /* Preview table styles */
    .preview-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }

    .preview-table th, .preview-table td {
        border: 1px solid #e0e0e0;
        padding: 8px 12px;
        text-align: left;
    }

    .preview-table th {
        background-color: #f8f9fa;
        font-weight: 500;
    }

    .preview-table tr:nth-child(even) {
        background-color: #f8f9fa;
    }
    </style>
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
                    <button class="import-btn" id="import-question-bank-btn">
                        <i>📚</i>
                        Import from Question Bank
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
            <a href="true_false.php?type=passage" class="question-type-btn" data-type="passage">
                <div class="question-type-icon">¶</div>
                True or False
                </a>
                <a href="programming.php?type=programming" class="question-type-btn" data-type="programming">
                <div class="question-type-icon">💻</div>
                Programming
                </a>
            </div>
            

           
           
            
            <div id="questions-container">
                <!-- Added questions will appear here -->
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
                    <div class="import-tab active" data-tab="question-bank">Question Bank</div>
                    <div class="import-tab" data-tab="spreadsheet">Spreadsheet</div>
                </div>
                
                <!-- Question Bank Tab Content -->
                <div class="import-tab-content active" data-content="question-bank">
                    <div class="question-bank-filters">
                        <div class="filter-group">
                            <label for="topic-filter">Topic:</label>
                            <select id="topic-filter" class="filter-select">
                                <option value="all">All Topics</option>
                                <option value="programming">Programming</option>
                                <option value="mathematics">Mathematics</option>
                                <option value="science">Science</option>
                                <option value="history">History</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="type-filter">Question Type:</label>
                            <select id="type-filter" class="filter-select">
                                <option value="all">All Types</option>
                                <option value="multiple-choice">Multiple Choice</option>
                                <option value="true-false">True/False</option>
                                <option value="programming">Programming</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="difficulty-filter">Difficulty:</label>
                            <select id="difficulty-filter" class="filter-select">
                                <option value="all">All Levels</option>
                                <option value="easy">Easy</option>
                                <option value="medium">Medium</option>
                                <option value="hard">Hard</option>
                            </select>
                        </div>
                        <div class="search-filter">
                            <input type="text" id="question-search" placeholder="Search questions...">
                        </div>
                    </div>
                    
                    <div class="question-bank-list">
                        <!-- Question items will be populated here -->
                        <div class="question-item">
                            <div class="question-item-header">
                                <div class="question-checkbox">
                                    <input type="checkbox" id="question-1" class="question-select">
                                </div>
                                <div class="question-preview">
                                    <h4>What is the output of the following code snippet?</h4>
                                    <div class="question-details">
                                        <span class="question-type">Programming</span>
                                        <span class="question-difficulty">Medium</span>
                                    </div>
                                </div>
                            </div>
                            <div class="question-item-body">
                                <pre><code>for(int i=0; i<5; i++) {
    System.out.println(i);
}</code></pre>
                            </div>
                        </div>
                        
                        <div class="question-item">
                            <div class="question-item-header">
                                <div class="question-checkbox">
                                    <input type="checkbox" id="question-2" class="question-select">
                                </div>
                                <div class="question-preview">
                                    <h4>Which of the following is NOT a primitive data type in Java?</h4>
                                    <div class="question-details">
                                        <span class="question-type">Multiple Choice</span>
                                        <span class="question-difficulty">Easy</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="question-item">
                            <div class="question-item-header">
                                <div class="question-checkbox">
                                    <input type="checkbox" id="question-3" class="question-select">
                                </div>
                                <div class="question-preview">
                                    <h4>JavaScript is a statically typed language.</h4>
                                    <div class="question-details">
                                        <span class="question-type">True/False</span>
                                        <span class="question-difficulty">Easy</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="pagination">
                        <button class="pagination-btn" disabled>&laquo;</button>
                        <button class="pagination-btn active">1</button>
                        <button class="pagination-btn">2</button>
                        <button class="pagination-btn">3</button>
                        <button class="pagination-btn">&raquo;</button>
                    </div>
                    
                    <div class="selected-count">
                        <span id="selected-questions-count">0</span> questions selected
                    </div>
                </div>
                
                <!-- Spreadsheet Tab Content -->
                <div class="import-tab-content" data-content="spreadsheet">
                    <div class="file-upload">
                        <p>Upload your spreadsheet file (.xlsx, .csv)</p>
                        <p>Make sure your spreadsheet has columns for question text, options, and correct answers</p>
                        <input type="file" id="spreadsheet-file" accept=".xlsx,.csv" style="display: none;">
                        <button class="file-upload-btn" id="spreadsheet-upload-btn">Choose File</button>
                        <p id="selected-file-name" style="margin-top: 10px; font-size: 14px;"></p>
                    </div>
                    
                    <div class="spreadsheet-preview">
                        <h4>File Preview</h4>
                        <div class="preview-container">
                            <p class="no-file-selected">No file selected</p>
                            <!-- Table preview will be inserted here when a file is selected -->
                    </div>
                </div>
                
                    <div class="column-mapping" style="display: none;">
                        <h4>Map Columns</h4>
                        <div class="mapping-container">
                            <div class="mapping-row">
                                <label for="question-column">Question Text:</label>
                                <select id="question-column" class="mapping-select">
                                    <option value="">Select column</option>
                                </select>
                            </div>
                            <div class="mapping-row">
                                <label for="options-column">Options:</label>
                                <select id="options-column" class="mapping-select">
                                    <option value="">Select column</option>
                                </select>
                            </div>
                            <div class="mapping-row">
                                <label for="answer-column">Correct Answer:</label>
                                <select id="answer-column" class="mapping-select">
                                    <option value="">Select column</option>
                                </select>
                            </div>
                            <div class="mapping-row">
                                <label for="type-column">Question Type (optional):</label>
                                <select id="type-column" class="mapping-select">
                                    <option value="">Select column</option>
                                    <option value="default">Use default: Multiple Choice</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="template-download" style="text-align: center; margin: 20px 0;">
                        <a href="#" class="template-link">Download template spreadsheet</a>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button class="btn btn-settings" id="cancel-import">Cancel</button>
                    <button class="btn btn-publish" id="start-import">Import Questions</button>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>
<script src="assets/js/side.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal elements
    const importModal = document.getElementById('import-modal');
    const closeImportModal = document.getElementById('close-import-modal');
    const importQuestionBankBtn = document.getElementById('import-question-bank-btn');
    const importGformBtn = document.getElementById('import-gform-btn');
    const cancelImportBtn = document.getElementById('cancel-import');
    const startImportBtn = document.getElementById('start-import');
    
    // Tab elements
    const importTabs = document.querySelectorAll('.import-tab');
    const importTabContents = document.querySelectorAll('.import-tab-content');
    
    // File upload elements
    const spreadsheetFileInput = document.getElementById('spreadsheet-file');
    const spreadsheetUploadBtn = document.getElementById('spreadsheet-upload-btn');
    const selectedFileName = document.getElementById('selected-file-name');
    const previewContainer = document.querySelector('.preview-container');
    const columnMapping = document.querySelector('.column-mapping');
    
    // Question selection counter
    const selectedQuestionsCount = document.getElementById('selected-questions-count');
    const questionCheckboxes = document.querySelectorAll('.question-select');
    
    // Show modal when import buttons are clicked
    importQuestionBankBtn.addEventListener('click', function() {
        importModal.style.display = 'flex';
        // Activate question bank tab
        activateTab('question-bank');
    });
    
    importGformBtn.addEventListener('click', function() {
        importModal.style.display = 'flex';
        // Activate spreadsheet tab
        activateTab('spreadsheet');
    });
    
    // Close modal
    closeImportModal.addEventListener('click', function() {
        importModal.style.display = 'none';
    });
    
    cancelImportBtn.addEventListener('click', function() {
        importModal.style.display = 'none';
    });
    
    // Tab switching
    importTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const tabName = this.getAttribute('data-tab');
            activateTab(tabName);
        });
    });
    
    function activateTab(tabName) {
        // Deactivate all tabs
        importTabs.forEach(tab => {
            tab.classList.remove('active');
        });
        
        importTabContents.forEach(content => {
            content.classList.remove('active');
        });
        
        // Activate selected tab
        document.querySelector(`.import-tab[data-tab="${tabName}"]`).classList.add('active');
        document.querySelector(`.import-tab-content[data-content="${tabName}"]`).classList.add('active');
    }
    
    // File upload handling
    spreadsheetUploadBtn.addEventListener('click', function() {
        spreadsheetFileInput.click();
    });
    
    spreadsheetFileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            const file = this.files[0];
            selectedFileName.textContent = file.name;
            
            // Show file preview (simplified for this example)
            previewContainer.innerHTML = `
                <table class="preview-table">
                    <thead>
                        <tr>
                            <th>Column A</th>
                            <th>Column B</th>
                            <th>Column C</th>
                            <th>Column D</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Question Text</td>
                            <td>Option A, Option B, Option C</td>
                            <td>A</td>
                            <td>Multiple Choice</td>
                        </tr>
                        <tr>
                            <td>Another question?</td>
                            <td>True, False</td>
                            <td>False</td>
                            <td>True/False</td>
                        </tr>
                    </tbody>
                </table>
            `;
            
            // Show column mapping
            columnMapping.style.display = 'block';
            
            // Populate column mapping dropdowns
            const mappingSelects = document.querySelectorAll('.mapping-select');
            mappingSelects.forEach(select => {
                if (select.id !== 'type-column' || !select.querySelector('option[value="default"]')) {
                    select.innerHTML = `
                        <option value="">Select column</option>
                        <option value="A">Column A</option>
                        <option value="B">Column B</option>
                        <option value="C">Column C</option>
                        <option value="D">Column D</option>
                    `;
                }
            });
            
            // Pre-select based on preview
            document.getElementById('question-column').value = 'A';
            document.getElementById('options-column').value = 'B';
            document.getElementById('answer-column').value = 'C';
            document.getElementById('type-column').value = 'D';
        }
    });
    
    // Question selection counter
    questionCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedCount);
    });
    
    function updateSelectedCount() {
        const checkedCount = document.querySelectorAll('.question-select:checked').length;
        selectedQuestionsCount.textContent = checkedCount;
    }
    
    // Import button action
    startImportBtn.addEventListener('click', function() {
        const activeTab = document.querySelector('.import-tab.active').getAttribute('data-tab');
        
        if (activeTab === 'question-bank') {
            const selectedQuestions = document.querySelectorAll('.question-select:checked').length;
            alert(`Importing ${selectedQuestions} questions from the question bank.`);
        } else if (activeTab === 'spreadsheet') {
            const fileName = selectedFileName.textContent || 'No file';
            alert(`Importing questions from spreadsheet: ${fileName}`);
        }
        
        importModal.style.display = 'none';
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === importModal) {
            importModal.style.display = 'none';
        }
    });
});
</script>
</body>                
</html>
            