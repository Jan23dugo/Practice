document.addEventListener('DOMContentLoaded', function() {
    // Object to store all active charts
    let charts = {
        demographics: null,
        examType: null,
        examResults: null
    };

    // Tab switching functionality
    const tabs = document.querySelectorAll('.analytics-tab');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            // Remove active class from all tabs and contents
            tabs.forEach(t => t.classList.remove('active'));
            tabContents.forEach(content => {
                content.classList.remove('active');
                // Hide all content when switching tabs
                content.style.display = 'none';
            });
            
            // Add active class to clicked tab and corresponding content
            tab.classList.add('active');
            const tabId = tab.getAttribute('data-tab');
            const activeContent = document.getElementById(tabId);
            if (activeContent) {
                activeContent.classList.add('active');
                activeContent.style.display = 'block';
            }

            // Destroy all existing charts
            Object.keys(charts).forEach(key => {
                if (charts[key]) {
                    charts[key].destroy();
                    charts[key] = null;
                }
            });

            // Initialize appropriate chart based on active tab
            switch(tabId) {
                case 'demographics':
                    initDemographicsChart();
                    break;
                case 'exam-overview':
                    initExamOverviewChart();
                    break;
                case 'exam-results':
                    initExamResultsChart();
                    break;
            }
        });
    });

    // Set initial tab content visibility
    const activeTab = document.querySelector('.analytics-tab.active');
    if (activeTab) {
        const tabId = activeTab.getAttribute('data-tab');
        const activeContent = document.getElementById(tabId);
        if (activeContent) {
            activeContent.style.display = 'block';
        }
    }

    // Initialize Demographics Chart
    function initDemographicsChart() {
        const canvas = document.getElementById('demographicsChart');
        if (!canvas) return;

        charts.demographics = new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: ['Transferee', 'Shiftee', 'Ladderized'],
                datasets: [{
                    data: [
                        parseInt(canvas.dataset.transferee) || 0,
                        parseInt(canvas.dataset.shiftee) || 0,
                        parseInt(canvas.dataset.ladderized) || 0
                    ],
                    backgroundColor: ['#2196F3', '#FFC107', '#9C27B0']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    // Initialize Exam Overview Chart
    function initExamOverviewChart() {
        const canvas = document.getElementById('examTypeChart');
        if (!canvas) return;

        charts.examType = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: ['Technical', 'Non-Technical'],
                datasets: [{
                    label: 'Exam Types',
                    data: [
                        parseInt(canvas.dataset.technical) || 0,
                        parseInt(canvas.dataset.nonTechnical) || 0
                    ],
                    backgroundColor: ['#4CAF50', '#2196F3']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    // Initialize Exam Results Chart
    function initExamResultsChart() {
        const canvas = document.getElementById('examResultsChart');
        if (!canvas) return;

        charts.examResults = new Chart(canvas, {
            type: 'line',
            data: {
                labels: canvas.dataset.labels ? JSON.parse(canvas.dataset.labels) : [],
                datasets: [{
                    label: 'Pass Rate (%)',
                    data: canvas.dataset.data ? JSON.parse(canvas.dataset.data) : [],
                    borderColor: '#4CAF50',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });
    }

    // Filter panel toggle
    window.toggleFilterPanel = function() {
        const filterPanel = document.getElementById('filterPanel');
        if (filterPanel) {
            filterPanel.style.display = filterPanel.style.display === 'none' ? 'block' : 'none';
        }
    };

    // Reset filters
    window.resetFilters = function() {
        const form = document.getElementById('filterForm');
        if (form) {
            form.reset();
            form.submit();
        }
    };

    // Export data
    window.exportData = function() {
        window.location.href = 'analytics.php?export=csv';
    };

    // Change exam function
    window.changeExam = function(examId) {
        window.location.href = 'analytics.php' + (examId ? '?exam_id=' + examId : '');
    };

    // Select all checkboxes functionality
    const selectAllCheckbox = document.getElementById('selectAll');
    const examCheckboxes = document.querySelectorAll('.examCheckbox');
    const downloadSelectedBtn = document.getElementById('downloadSelectedBtn');

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            examCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateDownloadButtonState();
        });
    }

    examCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateDownloadButtonState);
    });

    function updateDownloadButtonState() {
        const checkedBoxes = document.querySelectorAll('.examCheckbox:checked');
        if (downloadSelectedBtn) {
            downloadSelectedBtn.disabled = checkedBoxes.length === 0;
        }
    }

    // View details button functionality
    const viewDetailsButtons = document.querySelectorAll('.view-details-btn');
    const detailPanel = document.getElementById('exam-detail-panel');
    const closeDetailBtn = document.getElementById('close-detail-btn');

    viewDetailsButtons.forEach(button => {
        button.addEventListener('click', function() {
            const examId = this.dataset.examId;
            const examTitle = this.dataset.examTitle;
            
            // Update detail panel title
            document.getElementById('detail-exam-title').textContent = examTitle;
            
            // Show the detail panel
            if (detailPanel) {
                detailPanel.style.display = 'block';
            }
            
            // Here you would typically fetch and display the detailed exam data
            // This is a placeholder for the actual data fetching
            updateDetailPanelData({
                totalStudents: 100,
                passCount: 75,
                failCount: 25,
                passRate: 75,
                avgScore: 82.5
            });
        });
    });

    if (closeDetailBtn) {
        closeDetailBtn.addEventListener('click', function() {
            detailPanel.style.display = 'none';
        });
    }

    function updateDetailPanelData(data) {
        document.getElementById('detail-total-students').textContent = data.totalStudents;
        document.getElementById('detail-pass-count').textContent = data.passCount;
        document.getElementById('detail-fail-count').textContent = data.failCount;
        document.getElementById('detail-pass-rate').textContent = data.passRate + '%';
        document.getElementById('detail-avg-score').textContent = data.avgScore;
    }

    // Function to open the edit modal
    function openEditModal(questionId, questionText, questionType, examId) {
        const modal = document.getElementById('questionEditModal');
        if (!modal) return;

        // Set initial values
        document.getElementById('question_id').value = questionId;
        document.getElementById('exam_id').value = examId;
        document.getElementById('question_text').value = questionText;
        document.getElementById('question_type').value = questionType;

        // Fetch question details
        fetch(`analytics_get_question.php?question_id=${questionId}`)
            .then(response => response.json())
            .then(response => {
                if (!response.success) {
                    throw new Error(response.message || 'Failed to load question details');
                }

                const data = response.data;

                // Setup fields based on question type
                switch (questionType) {
                    case 'multiple-choice':
                        if (data.choices) {
                            setupMultipleChoiceFields(data.choices);
                        }
                        break;
                        
                    case 'true-false':
                        if (data.correct_answer) {
                            document.getElementById('correct_answer').value = data.correct_answer;
                        }
                        break;
                        
                    case 'programming':
                        if (data.programming) {
                            setupProgrammingFields(data);
                        }
                        break;
                }

                // Show appropriate fields
                toggleQuestionTypeFields();
                
                // Show the modal
                modal.style.display = 'block';
            })
            .catch(error => {
                console.error('Error loading question details:', error);
                alert('Error loading question details. Please try again.');
            });
    }

    function setupMultipleChoiceFields(choices) {
        const choiceList = document.getElementById('choice-list');
        const correctSelect = document.getElementById('correct_choice');
        
        // Clear existing choices
        choiceList.innerHTML = '';
        correctSelect.innerHTML = '';
        
        // Add each choice
        choices.forEach((choice, index) => {
            // Add choice input
            const choiceDiv = document.createElement('div');
            choiceDiv.className = 'choice-item';
            choiceDiv.innerHTML = `
                <input type="text" name="choices[]" value="${choice.text}" required>
                <button type="button" class="btn btn-sm btn-secondary" onclick="removeChoice(this)">
                    <span class="material-symbols-rounded">delete</span>
                </button>
            `;
            choiceList.appendChild(choiceDiv);
            
            // Add option to correct answer select
            const option = document.createElement('option');
            option.value = index;
            option.text = `Choice ${index + 1}`;
            option.selected = choice.is_correct;
            correctSelect.appendChild(option);
        });
    }

    function setupProgrammingFields(data) {
        if (!data.programming) return;

        const programmingData = data.programming;
        
        // Set programming language
        const languageSelect = document.getElementById('programming_language');
        if (languageSelect) {
            languageSelect.value = programmingData.language || 'python';
        }

        // Set starter code
        const starterCodeArea = document.getElementById('starter_code');
        if (starterCodeArea) {
            starterCodeArea.value = programmingData.starter_code || '';
        }

        // Setup test cases
        const testCasesContainer = document.getElementById('test-cases-container');
        if (testCasesContainer) {
            testCasesContainer.innerHTML = '';
            
            if (data.test_cases && Array.isArray(data.test_cases)) {
                data.test_cases.forEach(testCase => {
                    addTestCase(testCase);
                });
            }
        }
    }

    function toggleQuestionTypeFields() {
        const questionType = document.getElementById('question_type').value;
        
        // Hide all fields first
        document.querySelectorAll('.question-type-fields').forEach(field => {
            field.style.display = 'none';
        });
        
        // Show relevant fields
        switch (questionType) {
            case 'multiple-choice':
                document.getElementById('multiple-choice-fields').style.display = 'block';
                break;
            case 'true-false':
                document.getElementById('true-false-fields').style.display = 'block';
                break;
            case 'programming':
                document.getElementById('programming-fields').style.display = 'block';
                break;
        }
    }

    function addTestCase(testCase = null) {
        const container = document.getElementById('test-cases-container');
        const testCaseDiv = document.createElement('div');
        testCaseDiv.className = 'test-case';
        
        testCaseDiv.innerHTML = `
            <div class="test-case-header">
                <span>Test Case</span>
                <button type="button" class="btn btn-sm btn-secondary" onclick="removeTestCase(this)">
                    <span class="material-symbols-rounded">delete</span>
                </button>
            </div>
            <div class="test-case-inputs">
                <div class="form-group">
                    <label>Input:</label>
                    <textarea name="test_input[]" rows="2" required>${testCase ? testCase.input : ''}</textarea>
                </div>
                <div class="form-group">
                    <label>Expected Output:</label>
                    <textarea name="test_output[]" rows="2" required>${testCase ? testCase.expected_output : ''}</textarea>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_hidden[${container.children.length}]" ${testCase && testCase.is_hidden ? 'checked' : ''}>
                        Hidden Test Case
                    </label>
                </div>
                <div class="form-group">
                    <label>Description (for hidden test cases):</label>
                    <input type="text" name="hidden_description[]" value="${testCase ? testCase.description : ''}">
                </div>
            </div>
        `;
        
        container.appendChild(testCaseDiv);
    }

    function removeTestCase(button) {
        button.closest('.test-case').remove();
    }

    function closeEditModal() {
        document.getElementById('questionEditModal').style.display = 'none';
    }

    function saveQuestion() {
        const form = document.getElementById('questionEditForm');
        const formData = new FormData(form);
        
        fetch('save_question.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Question updated successfully!');
                closeEditModal();
                window.location.reload(); // Reload to show updated data
            } else {
                alert('Error updating question: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating the question');
        });
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('questionEditModal');
        if (event.target === modal) {
            closeEditModal();
        }
    }

    // Initialize edit buttons
    const editButtons = document.querySelectorAll('.edit-btn');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const questionId = this.getAttribute('data-question-id');
            const questionText = this.getAttribute('data-question-text');
            const questionType = this.getAttribute('data-question-type');
            const examId = this.getAttribute('data-exam-id');
            openEditModal(questionId, questionText, questionType, examId);
        });
    });
});
