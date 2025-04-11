<!-- Question Edit Modal -->
<div id="questionEditModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><span class="material-symbols-rounded">edit_note</span> Edit Question</h3>
            <span class="close-modal" onclick="closeEditModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="questionEditForm" method="POST" action="save_question.php">
                <input type="hidden" id="question_id" name="question_id">
                <input type="hidden" id="exam_id" name="exam_id">
                <input type="hidden" name="mode" value="edit">
                
                <div class="form-section">
                    <div class="form-group">
                        <label for="question_text">
                            <span class="material-symbols-rounded">help</span>
                            Question Text:
                        </label>
                        <textarea id="question_text" name="question" rows="4" required 
                                class="form-control" placeholder="Enter the question text here..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="question_type">
                            <span class="material-symbols-rounded">category</span>
                            Question Type:
                        </label>
                        <select id="question_type" name="question_type" required 
                                onchange="toggleQuestionTypeFields()" class="form-control">
                            <option value="multiple-choice">Multiple Choice</option>
                            <option value="true-false">True/False</option>
                            <option value="programming">Programming</option>
                        </select>
                    </div>
                </div>

                <!-- Multiple Choice Options -->
                <div id="multiple-choice-fields" class="question-type-fields">
                    <div class="section-header">
                        <span class="material-symbols-rounded">list</span>
                        Multiple Choice Options
                    </div>
                    <div class="form-group" id="choices-container">
                        <label>Answer Choices:</label>
                        <div id="choice-list" class="choice-list">
                            <!-- Choices will be dynamically added here -->
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm add-choice-btn" onclick="addChoice()">
                            <span class="material-symbols-rounded">add</span> Add Choice
                        </button>
                    </div>
                    <div class="form-group">
                        <label for="correct_choice">Correct Answer:</label>
                        <select id="correct_choice" name="correct" required class="form-control">
                            <!-- Options will be dynamically updated -->
                        </select>
                    </div>
                </div>

                <!-- True/False Options -->
                <div id="true-false-fields" class="question-type-fields">
                    <div class="section-header">
                        <span class="material-symbols-rounded">check_box</span>
                        True/False Options
                    </div>
                    <div class="form-group">
                        <label for="correct_answer">Correct Answer:</label>
                        <div class="true-false-toggle">
                            <label class="toggle-option">
                                <input type="radio" name="correct_answer" value="True"> True
                            </label>
                            <label class="toggle-option">
                                <input type="radio" name="correct_answer" value="False"> False
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Programming Options -->
                <div id="programming-fields" class="question-type-fields">
                    <div class="section-header">
                        <span class="material-symbols-rounded">code</span>
                        Programming Options
                    </div>
                    <div class="form-group">
                        <label for="programming_language">Programming Language:</label>
                        <select id="programming_language" name="programming_language" class="form-control">
                            <option value="python">Python</option>
                            <option value="java">Java</option>
                            <option value="cpp">C++</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="starter_code">Starter Code:</label>
                        <textarea id="starter_code" name="starter_code" rows="6" 
                                class="form-control code-editor" 
                                placeholder="Enter starter code here..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Test Cases:</label>
                        <div id="test-cases-container" class="test-cases-container">
                            <!-- Test cases will be dynamically added here -->
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="addTestCase()">
                            <span class="material-symbols-rounded">add</span> Add Test Case
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeEditModal()">
                <span class="material-symbols-rounded">close</span> Cancel
            </button>
            <button type="button" class="btn btn-primary" onclick="saveQuestion()">
                <span class="material-symbols-rounded">save</span> Save Changes
            </button>
        </div>
    </div>
</div>

<style>
/* Enhanced Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    overflow-y: auto;
}

.modal-content {
    background-color: #fff;
    margin: 30px auto;
    width: 90%;
    max-width: 800px;
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.modal-header {
    background-color: #75343A;
    color: white;
    padding: 20px;
    border-radius: 12px 12px 0 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.modal-header h3 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1.5rem;
}

.close-modal {
    color: white;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    transition: 0.3s;
}

.close-modal:hover {
    opacity: 0.7;
}

.modal-body {
    padding: 25px;
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid #eee;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

/* Form Styles */
.form-section {
    margin-bottom: 25px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
}

.form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.3s;
}

.form-control:focus {
    border-color: #75343A;
    outline: none;
    box-shadow: 0 0 0 2px rgba(117, 52, 58, 0.1);
}

/* Question Type Fields */
.question-type-fields {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-top: 20px;
    display: none;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 1.1rem;
    font-weight: 600;
    color: #75343A;
    margin-bottom: 20px;
}

/* Choice List Styles */
.choice-list {
    margin-bottom: 15px;
}

.choice-item {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
    background: white;
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #ddd;
}

.choice-item input {
    flex: 1;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

/* Test Cases Styles */
.test-cases-container {
    margin-bottom: 15px;
}

.test-case {
    background: white;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 15px;
    border: 1px solid #ddd;
}

.test-case-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.test-case-inputs {
    display: grid;
    gap: 15px;
}

/* Code Editor Style */
.code-editor {
    font-family: monospace;
    background-color: #f8f9fa;
    tab-size: 4;
}

/* True/False Toggle Style */
.true-false-toggle {
    display: flex;
    gap: 20px;
    padding: 10px;
    background: white;
    border-radius: 6px;
    border: 1px solid #ddd;
}

.toggle-option {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

/* Button Styles */
.btn {
    padding: 10px 20px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
    border: none;
}

.btn-sm {
    padding: 8px 15px;
    font-size: 13px;
}

.btn-primary {
    background-color: #75343A;
    color: white;
}

.btn-primary:hover {
    background-color: #5a2930;
}

.btn-secondary {
    background-color: #f8f9fa;
    color: #333;
    border: 1px solid #ddd;
}

.btn-secondary:hover {
    background-color: #e9ecef;
}

.add-choice-btn {
    margin-top: 10px;
}

/* Material Icons Alignment */
.material-symbols-rounded {
    font-size: 20px;
    vertical-align: middle;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize modal elements
    const modal = document.getElementById('questionEditModal');
    const closeBtn = document.querySelector('.close-modal');
    const form = document.getElementById('questionEditForm');

    // Function to open modal
    window.openEditModal = function(questionId, questionText, questionType) {
        document.getElementById('question_id').value = questionId;
        document.getElementById('question_text').value = questionText;
        document.getElementById('question_type').value = questionType;
        modal.style.display = 'block';
    };

    // Function to close modal
    window.closeEditModal = function() {
        modal.style.display = 'none';
    };

    // Close modal when clicking X
    closeBtn.onclick = closeEditModal;

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target === modal) {
            closeEditModal();
        }
    };

    // Handle form submission
    form.onsubmit = function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('update_question.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Question updated successfully!');
                closeEditModal();
                location.reload(); // Refresh to show updated data
            } else {
                alert('Error updating question: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating the question.');
        });
    };
});</script> 