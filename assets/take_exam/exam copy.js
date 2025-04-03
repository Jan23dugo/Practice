        // Initialize global variables at the very top of the file, outside any functions
        let totalQuestions = 0;
        let currentQuestion = 1;
        let answeredQuestions = {};
        let flaggedQuestions = {};
        let codeEditors = {};
        let examTimer = null;
        let timeRemaining = 0;

        // Security monitoring variables
        // let warningCount = 0;
        // const MAX_WARNINGS = 3;
        // let lastWarningTime = 0;
        // const WARNING_COOLDOWN = 5000; // 5 seconds
        let isSubmitting = false;
        // let isLegitimateAction = false;

        // Custom alert modal functionality
        function showAlert(message, type = 'info', confirmCallback = null, cancelCallback = null) {
            const alertModal = document.getElementById('alert-modal');
            const alertMessage = document.getElementById('alert-message');
            const alertIcon = document.querySelector('.alert-icon');
            const alertIconSymbol = document.getElementById('alert-icon-symbol');
            const confirmBtn = document.getElementById('alert-confirm-btn');
            const cancelBtn = document.getElementById('alert-cancel-btn');
            
            // Set the message
            alertMessage.textContent = message;
            
            // Set the appropriate icon based on type
            alertIcon.className = 'alert-icon';
            
            switch(type) {
                case 'success':
                    alertIconSymbol.textContent = 'check_circle';
                    break;
                case 'error':
                    alertIconSymbol.textContent = 'error';
                    break;
                case 'warning':
                    alertIconSymbol.textContent = 'warning';
                    break;
                case 'confirm':
                    alertIconSymbol.textContent = 'help';
                    break;
                default: // info
                    alertIconSymbol.textContent = 'info';
                    break;
            }
            
            // Show the modal with animation
            alertModal.style.display = 'flex';
            setTimeout(() => {
                alertModal.classList.add('show');
            }, 10);
            
            // Handle the confirm button
            confirmBtn.onclick = function() {
                // Hide the modal with animation
                alertModal.classList.remove('show');
                setTimeout(() => {
                    alertModal.style.display = 'none';
                    // Call the callback if provided
                    if (confirmCallback && typeof confirmCallback === 'function') {
                        confirmCallback();
                    }
                }, 300);
            };
            
            // Handle the cancel button
            if (cancelBtn) {
                if (cancelCallback) {
                    cancelBtn.style.display = 'block';
                    cancelBtn.onclick = function() {
                        alertModal.classList.remove('show');
                        setTimeout(() => {
                            alertModal.style.display = 'none';
                            if (cancelCallback && typeof cancelCallback === 'function') {
                                cancelCallback();
                            }
                        }, 300);
                    };
                } else {
                    cancelBtn.style.display = 'none';
                }
            }
            
            // Close when clicking outside
            alertModal.onclick = function(e) {
                if (e.target === alertModal && cancelCallback) {
                    cancelBtn.click();
                }
            };
        }

        // Debug logging
        console.log("Starting exam initialization...");
        console.log("Total questions: " + totalQuestions);

        // Wait for the DOM to be fully loaded before initializing
        document.addEventListener('DOMContentLoaded', function() {
            console.log("DOM Content Loaded - Starting initialization");
            
            // Get totalQuestions from window object set by PHP
            totalQuestions = window.totalQuestions || 0;
            console.log("Total questions:", totalQuestions);
            console.log("Current question:", currentQuestion);

            // Initialize the exam interface
            initializeExam();
            initNavigatorPopup();

            // Initialize editors with a delay to ensure DOM is ready
            setTimeout(() => {
                initializeCodeEditors();
                console.log('Editors initialized after delay');
                console.log('Available editors:', Object.keys(codeEditors));
            }, 500);

            // Initialize timer
            initializeTimer();

            // Comment out window/tab switching detection
            /*
            document.addEventListener('visibilitychange', function() {
                if (!isSubmitting && document.visibilityState === 'hidden') {
                    warningCount++;
                    if (warningCount >= MAX_WARNINGS) {
                        autoSubmitExam();
                    } else {
                        alert(`Warning: Switching tabs is not allowed during the exam. Warning ${warningCount}/${MAX_WARNINGS}`);
                    }
                }
            });
            */
        });

        // Main initialization function
        function initializeExam() {
            try {
                // Initialize exam state
                updatePalette();
                showQuestion(1);
                
                console.log("Exam initialization complete");
            } catch (err) {
                console.error('Error in exam initialization:', err);
            }
        }
        // First, keep track of editors globally

        // Initialize CodeMirror editors
        function initializeCodeEditors() {
            const editorElements = document.querySelectorAll('.code-editor');
            
            editorElements.forEach(editorElement => {
                const editor = CodeMirror.fromTextArea(editorElement, {
                    mode: 'python',
                    theme: 'dracula',
                    lineNumbers: true,
                    autoCloseBrackets: true,
                    matchBrackets: true,
                    indentUnit: 4,
                    tabSize: 4,
                    indentWithTabs: false,
                    lineWrapping: true,
                    viewportMargin: Infinity,
                    extraKeys: {
                        "Tab": function(cm) {
                            if (cm.somethingSelected()) {
                                cm.indentSelection("add");
                            } else {
                                cm.replaceSelection("    ", "end");
                            }
                        }
                    }
                });

                // Get starter code if it exists
                const starterCode = editorElement.getAttribute('data-starter-code');
                if (starterCode) {
                    // Set the starter code
                    editor.setValue(starterCode);
                    // Move cursor to the end of the starter code
                    editor.setCursor(editor.lineCount(), 0);
                }

                // Store editor instance in the DOM element for later access
                editorElement.editor = editor;

                // Fix cursor position issues
                editor.on('focus', function() {
                    // Ensure the cursor stays where clicked
                    setTimeout(() => {
                        editor.refresh();
                    }, 1);
                });

                // Prevent unwanted cursor jumps
                editor.on('cursorActivity', function() {
                    editor.refresh();
                });

                // Ensure editor is properly sized
                editor.refresh();
            });
        }

        // Update editor mode when language is changed
        document.querySelector('.programming-language').addEventListener('change', function(e) {
            const editorElements = document.querySelectorAll('.code-editor');
            const mode = e.target.value;
            
            editorElements.forEach(editorElement => {
                if (editorElement.editor) {
                    editorElement.editor.setOption('mode', mode);
                    editorElement.editor.refresh();
                }
            });
        });

        // Add this function to fetch starter code from the database
        async function fetchStarterCode(programmingId) {
            try {
                const response = await fetch('fetch_starter_code.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `programming_id=${programmingId}`
                });

                const data = await response.json();
                if (data.success) {
                    return data.starter_code;
                } else {
                    console.error('Error fetching starter code:', data.error);
                    return null;
                }
            } catch (error) {
                console.error('Error fetching starter code:', error);
                return null;
            }
        }

        // Add this helper function to force update all editors
        function refreshAllEditors() {
            Object.values(codeEditors).forEach(editor => {
                editor.refresh();
                if (!editor.getValue().trim()) {
                    editor.setValue('');
                    editor.clearHistory();
                    editor.refresh();
                }
            });
        }

        // Show question function
        function showQuestion(questionNumber) {
            console.log(`Showing question ${questionNumber}`);
            
            // Hide all questions
            document.querySelectorAll('.question-container').forEach(q => q.style.display = 'none');
            
            // Show the selected question
            const questionToShow = document.getElementById(`question-${questionNumber}`);
            if (questionToShow) {
                const isProgramming = questionToShow.querySelector('.programming-question') !== null;
                questionToShow.style.display = isProgramming ? 'flex' : 'block';
                
                // Handle editor refresh
                if (isProgramming && codeEditors[questionNumber]) {
                    const editor = codeEditors[questionNumber];
                    setTimeout(() => {
                        editor.refresh();
                        // Ensure cursor is visible
                        editor.focus();
                        editor.setCursor(editor.lineCount(), 0);
                    }, 10);
                }
            }

            currentQuestion = questionNumber;
            updateNavigationButtons(questionNumber);
            updateQuestionNavigator(questionNumber);
        }

        // Update the updateQuestionNavigator function
        function updateQuestionNavigator(currentNumber) {
            document.querySelectorAll('.question-number').forEach(q => {
                q.classList.remove('current');
            });
            
            const currentNav = document.getElementById(`nav-question-${currentNumber}`);
            if (currentNav) {
                currentNav.classList.add('current');
            }
        }

        // Update the updatePalette function
        function updatePalette() {
            for (let i = 1; i <= totalQuestions; i++) {
                const paletteItem = document.getElementById(`nav-question-${i}`);
                if (!paletteItem) continue;
                
                paletteItem.classList.remove('answered', 'flagged', 'current');
                
                if (answeredQuestions[i]) paletteItem.classList.add('answered');
                if (flaggedQuestions[i]) paletteItem.classList.add('flagged');
                if (i === currentQuestion) paletteItem.classList.add('current');
            }
        }

        // Navigation functions
        function nextQuestion() {
            if (currentQuestion < totalQuestions) {
                currentQuestion++;
                showQuestion(currentQuestion);
            }
        }

        function prevQuestion() {
            if (currentQuestion > 1) {
                currentQuestion--;
                showQuestion(currentQuestion);
            }
        }

        function navigateToQuestion(questionNumber) {
            if (questionNumber >= 1 && questionNumber <= totalQuestions) {
                currentQuestion = questionNumber;
                showQuestion(currentQuestion);
            }
        }

        // Flag question function
        function flagQuestion(questionNumber) {
            flaggedQuestions[questionNumber] = !flaggedQuestions[questionNumber];
            
            const flagBtn = event.target.closest('.btn-flag');
            if (flagBtn) {
                flagBtn.classList.toggle('flagged');
            }
            
            updatePalette();
        }

        // Submit confirmation
        function confirmSubmit() {
            if (confirm("Are you sure you want to submit your exam?")) {
                document.getElementById('examForm').submit();
            }
        }

        // Modify the existing function that handles code saving
        function saveCode(questionNumber, programmingId) {
            const editor = codeEditors[questionNumber];
            if (!editor) return;

            const code = editor.getValue().trim();
            
            if (!code) {
                editor.setValue('');
                delete answeredQuestions[questionNumber];
                updateProgress();
                return;
            }

            answeredQuestions[questionNumber] = {
                type: 'programming',
                code: code,
                programmingId: programmingId
            };

            updatePalette();
            updateProgress();

            const saveIndicator = document.getElementById(`save-indicator-${questionNumber}`);
            if (saveIndicator) {
                saveIndicator.textContent = 'Code saved ✓';
                setTimeout(() => saveIndicator.textContent = '', 2000);
            }
        }

        // Add this to track which programming questions have been tested
        let testedQuestions = {};

        // Modify the updateProgress function
        function updateProgress() {
            let answeredCount = 0;
            const total = window.totalQuestions;

            // Get all question containers
            document.querySelectorAll('.question-container').forEach(container => {
                const questionType = container.getAttribute('data-question-type');
                const questionNumber = container.id.replace('question-', '');
                
                if (questionType === 'programming') {
                    // Only count programming questions if they've been tested
                    if (testedQuestions[questionNumber]) {
                        answeredCount++;
                        console.log(`Programming question ${questionNumber} is counted (has been tested)`);
                    } else {
                        console.log(`Programming question ${questionNumber} not counted (not tested yet)`);
                    }
                } else {
                    // Check multiple choice/true-false questions
                    const hasAnswer = container.querySelector('input[type="radio"]:checked');
                    if (hasAnswer) {
                        answeredCount++;
                        console.log(`Multiple choice question ${questionNumber} is answered`);
                    }
                }
            });

            // Update the progress display
            const progressText = document.querySelector('.progress-text');
            if (progressText) {
                progressText.textContent = `${answeredCount} of ${total} answered`;
            }
            
            const progressFill = document.getElementById('progress-fill');
            if (progressFill) {
                const percentage = (answeredCount / total) * 100;
                progressFill.style.width = `${percentage}%`;
            }

            console.log(`Total answered: ${answeredCount} out of ${total}`);
        }

        // Modify the runCode function to update testedQuestions
        async function runCode(questionNumber, questionId, programmingId) {
            console.log(`Running code for question ${questionNumber}`);
            
            const editor = codeEditors[questionNumber];
            if (!editor) {
                console.error('Editor not found');
                return;
            }

            // Get the programming language from the question container
            const questionContainer = document.getElementById(`question-${questionNumber}`);
            const languageElement = questionContainer.querySelector('.programming-language');
            const language = languageElement ? languageElement.value : 'python'; // default to python

            const testCasesSidebar = document.querySelector(`.test-cases-sidebar`);
            const testResults = document.getElementById(`test-results-${questionNumber}`);
            
            try {
                // Show loading state in sidebar
                if (testCasesSidebar) {
                    testCasesSidebar.innerHTML = `
                        <div class="sidebar-header">
                            <h3>Test Cases</h3>
                            <span class="loading-indicator">Running...</span>
                        </div>
                    `;
                }

                // Show loading state
                if (testResults) {
                    testResults.className = 'status-indicator running';
                    testResults.innerHTML = '<span class="material-symbols-rounded">autorenew</span>';
                }

                const code = editor.getValue().trim();
                console.log('Sending code:', code);

                // Send directly to our backend for test case evaluation
                const formData = new FormData();
                formData.append('action', 'execute');
                formData.append('code', code);
                formData.append('language', language);
                formData.append('question_id', questionId);
                formData.append('programming_id', programmingId);

                const response = await fetch('test_exam.php', {
                    method: 'POST',
                    body: formData
                });

                // Log the raw response
                const rawResponse = await response.text();
                console.log('Raw response:', rawResponse);

                // Try to parse the response as JSON
                let data;
                try {
                    data = JSON.parse(rawResponse);
                } catch (e) {
                    console.error('Failed to parse JSON response:', e);
                    throw new Error('Invalid response from server');
                }

                if (!data.success) {
                    throw new Error(data.error || 'Failed to execute code');
                }

                // Mark this question as tested regardless of the test results
                testedQuestions[questionNumber] = true;
                
                // Update progress since we've run tests
                updateProgress();

                // Update the sidebar with results
                if (testCasesSidebar && data.result) {
                    let sidebarHTML = `
                        <div class="sidebar-header">
                            <h3>Test Cases</h3>
                            <span class="test-status ${data.result.every(r => r.passed) ? 'passed' : 'failed'}">
                                ${data.result.every(r => r.passed) ? 'All Passed' : 'Tests Failed'}
                            </span>
                        </div>
                        <div class="test-cases-list">
                            ${data.result.map((test, index) => `
                                <div class="test-case-item ${test.passed ? 'passed' : 'failed'}">
                                    <div class="test-case-header">
                                        <span class="material-symbols-rounded">
                                            ${test.passed ? 'check_circle' : 'error'}
                                        </span>
                                        <span>Test Case ${index + 1}</span>
                                    </div>
                                    <div class="test-case-details">
                                        <div class="input">Input: ${test.input}</div>
                                        <div class="expected">Expected: ${test.expected}</div>
                                        <div class="actual">Output: ${test.actual}</div>
                                        ${test.error ? `<div class="error">Error: ${test.error}</div>` : ''}
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    `;
                    testCasesSidebar.innerHTML = sidebarHTML;
                }

                // Update the main test results area
                if (testResults) {
                    const result = data.result;
                    const allTestsPassed = result.every(test => test.passed);
                    
                    let resultsHTML = `
                        <div class="test-results ${allTestsPassed ? 'all-passed' : ''}">
                            ${result.map(test => `
                                <div class="test-case ${test.passed ? 'passed' : 'failed'}">
                                    <div class="test-case-header">
                                        <span class="status-icon material-symbols-rounded">
                                            ${test.passed ? 'check_circle' : 'error'}
                                        </span>
                                        <span class="test-case-title">Test Case ${test.test_case_id}</span>
                                    </div>
                                    <div class="test-details">
                                        <div class="input-output">
                                            <div class="input">
                                                <strong>Input:</strong>
                                                <pre>${test.input}</pre>
                                            </div>
                                            <div class="expected">
                                                <strong>Expected:</strong>
                                                <pre>${test.expected}</pre>
                                            </div>
                                            <div class="actual">
                                                <strong>Your Output:</strong>
                                                <pre>${test.actual}</pre>
                                            </div>
                                        </div>
                                        ${test.error ? `
                                            <div class="error-details">
                                                <strong>Error:</strong>
                                                <pre>${test.error}</pre>
                                            </div>
                                        ` : ''}
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    `;

                    testResults.innerHTML = resultsHTML;
                    testResults.className = `status-indicator ${allTestsPassed ? 'success' : 'error'}`;
                }

                // Store test results for later use
                storeTestResults(questionNumber, data.result);

            } catch (error) {
                console.error('Error running code:', error);
                
                // Still mark as tested even if there was an error
                testedQuestions[questionNumber] = true;
                updateProgress();
                
                // Show error in sidebar
                if (testCasesSidebar) {
                    testCasesSidebar.innerHTML = `
                        <div class="sidebar-header">
                            <h3>Test Cases</h3>
                            <span class="test-status error">Error</span>
                        </div>
                        <div class="error-message">
                            ${error.message}
                        </div>
                    `;
                }

                if (testResults) {
                    testResults.innerHTML = `
                        <div class="test-case error">
                            <div class="test-case-header">
                                Error Running Code
                                <span class="material-symbols-rounded">error</span>
                            </div>
                            <div class="test-details">
                                <pre>${error.message}</pre>
                            </div>
                        </div>
                    `;
                    testResults.className = 'status-indicator error';
                }
            }
        }

        // Add new function to handle manual testing
        function runManualTest(questionNumber, programmingId) {
            const editor = codeEditors[questionNumber];
            const input = document.getElementById(`manual-input-${questionNumber}`).value;
            const resultContainer = document.getElementById(`manual-result-${questionNumber}`);
            
            if (!input.trim()) {
                resultContainer.innerHTML = `
                    <div class="error">
                        Please enter input values
                    </div>`;
                return;
            }

            // Show loading state
            resultContainer.innerHTML = `
                <div class="loading">
                    <div class="spinner"></div>
                    Running test...
                </div>`;

            const code = editor.getValue();
            
            // Send to server for execution
            fetch('run_code.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `programming_id=${programmingId}&code=${encodeURIComponent(code)}&manual_input=${encodeURIComponent(input)}&is_manual=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    resultContainer.innerHTML = `
                        <div class="error">
                            ${data.error}
                        </div>`;
                    return;
                }

                resultContainer.innerHTML = `
                    <div class="manual-test-result">
                        <div class="result-item">
                            <strong>Input:</strong> 
                            <pre>${input}</pre>
                        </div>
                        <div class="result-item">
                            <strong>Output:</strong> 
                            <pre>${data.output}</pre>
                        </div>
                    </div>`;
            })
            .catch(error => {
                resultContainer.innerHTML = `
                    <div class="error">
                        Error: ${error.message}
                    </div>`;
            });
        }

        // Display test results
        function displayTestResults(questionNumber, data) {
            const resultsContainer = document.querySelector(`#test-results-${questionNumber}`);
            if (!resultsContainer) return;
            
            if (!data.testCases || data.testCases.length === 0) {
                resultsContainer.innerHTML = '<div class="test-case">No test cases available</div>';
                return;
            }
            
            let allPassed = true;
            let html = '';
            
            data.testCases.forEach((test, index) => {
                if (!test.passed) {
                    allPassed = false;
                }
                
                // Only show details for non-hidden tests
                if (!test.hidden) {
                    const resultClass = test.passed ? 'passed' : 'failed';
                    html += `
                        <div class="test-case ${resultClass}">
                            <div class="test-name">Test Case: ${test.name}</div>
                            ${test.passed ? 
                                `<div class="test-success">✓ Passed</div>` :
                                `<div class="test-error">✗ Failed</div>
                                 <div class="test-details">
                                    <div>Expected output: <pre>${test.expected}</pre></div>
                                    <div>Your output: <pre>${test.actual}</pre></div>
                                    ${test.error ? `<div>Error: <pre>${test.error}</pre></div>` : ''}
                                 </div>`
                            }
                        </div>
                    `;
                } else {
                    // For hidden test cases, just show pass/fail
                    html += `
                        <div class="test-case ${test.passed ? 'passed' : 'failed'} hidden-test">
                            <div class="test-name">Hidden Test: ${test.name}</div>
                            <div>${test.passed ? '✓ Passed' : '✗ Failed'}</div>
                        </div>
                    `;
                }
            });
            
            // Add summary
            html += `
                <div class="test-summary ${allPassed ? 'passed' : 'failed'}">
                    ${allPassed ? 'All tests passed!' : 'Some tests failed. Check your code.'}
                </div>
            `;
            
            resultsContainer.innerHTML = html;
            
            // Mark as answered if all tests passed
            if (allPassed) {
                answeredQuestions[questionNumber] = true;
                updatePalette();
                updateProgress();
            }
        }

        // Add this function right after your existing JavaScript functions
        function updateNavigationButtons(questionNumber) {
            // Get the navigation buttons from the current question container
            const currentQuestionContainer = document.getElementById(`question-${questionNumber}`);
            if (!currentQuestionContainer) return;

            const prevButton = currentQuestionContainer.querySelector('.btn-outline');
            const nextButton = currentQuestionContainer.querySelector('.btn-primary');

            // Update previous button
            if (prevButton) {
                if (questionNumber === 1) {
                    prevButton.disabled = true;
                } else {
                    prevButton.disabled = false;
                }
            }

            // Update next button
            if (nextButton) {
                if (questionNumber === totalQuestions) {
                    nextButton.disabled = true;
                } else {
                    nextButton.disabled = false;
                }
            }
        }

        // Add event listeners for radio buttons to track answered questions
        document.addEventListener('DOMContentLoaded', function() {
            const radioButtons = document.querySelectorAll('input[type="radio"]');
            radioButtons.forEach(radio => {
                radio.addEventListener('change', function() {
                    const questionNumber = parseInt(this.name.replace('q', ''));
                    answeredQuestions[questionNumber] = true;
                    updatePalette();
                    updateProgress();
                });
            });
        });

        // Function to initialize the popup navigator
        function initNavigatorPopup() {
            // Create the toggle button if it doesn't exist
            if (!document.querySelector('.navigator-toggle')) {
                const toggleBtn = document.createElement('button');
                toggleBtn.className = 'navigator-toggle';
                toggleBtn.innerHTML = '<span class="material-symbols-rounded">map</span>';
                toggleBtn.setAttribute('aria-label', 'Open question navigator');
                document.body.appendChild(toggleBtn);
                
                // Create overlay
                const overlay = document.createElement('div');
                overlay.className = 'navigator-overlay';
                document.body.appendChild(overlay);
                
                // Add close button to navigator
                const closeBtn = document.createElement('button');
                closeBtn.className = 'navigator-close';
                closeBtn.innerHTML = '<span class="material-symbols-rounded">close</span>';
                closeBtn.setAttribute('aria-label', 'Close navigator');
                document.querySelector('.question-navigator').prepend(closeBtn);
                
                // Event listeners
                toggleBtn.addEventListener('click', toggleNavigator);
                closeBtn.addEventListener('click', toggleNavigator);
                overlay.addEventListener('click', toggleNavigator);
            }
        }

        // Toggle the navigator popup
        function toggleNavigator() {
            const navigator = document.querySelector('.question-navigator');
            const overlay = document.querySelector('.navigator-overlay');
            
            if (navigator.classList.contains('active')) {
                navigator.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            } else {
                navigator.classList.add('active');
                overlay.classList.add('active');
                document.body.style.overflow = 'hidden'; // Prevent scrolling when popup is open
            }
        }

        // Add this function to help debug
        function debugAnswerCollection() {
            const questions = document.querySelectorAll('.question-container');
            questions.forEach(container => {
                const questionId = container.getAttribute('data-question-id');
                const questionType = container.getAttribute('data-question-type');
                const selectedRadio = container.querySelector('input[type="radio"]:checked');
                
                console.log({
                    questionId: questionId,
                    questionType: questionType,
                    hasSelectedAnswer: !!selectedRadio,
                    selectedValue: selectedRadio ? selectedRadio.value : null,
                    radioButtons: container.querySelectorAll('input[type="radio"]').length
                });
            });
        }

        // Function to submit exam
        function submitExam(examId) {
            // Use our custom alert instead of the browser's default
            showAlert('Are you sure you want to submit your exam? This action cannot be undone.', 'confirm', 
                // Confirm callback
                function() {
                    isSubmitting = true;
                    
                    // Get all answers
                    let answers = {};
                    let hasAnswers = false;

                    // Get all question containers
                    const questionContainers = document.querySelectorAll('.question-container');
                    questionContainers.forEach(container => {
                        const questionId = container.dataset.questionId;
                        const questionType = container.dataset.questionType;

                        if (questionType === 'programming') {
                            // Handle programming questions
                            const questionNumber = container.id.replace('question-', '');
                            const editor = codeEditors[questionNumber];
                            
                            if (editor) {
                                const code = editor.getValue().trim();
                                if (code && code !== '# Write your code here') {
                                    answers[questionId] = {
                                        code: code,
                                        programming_id: container.dataset.programmingId,
                                        question_type: 'programming'
                                    };
                                    hasAnswers = true;
                                }
                            }
                        } else {
                            // Handle multiple choice/true-false questions
                            const selectedRadio = container.querySelector('input[type="radio"]:checked');
                            if (selectedRadio) {
                                answers[questionId] = {
                                    answer_id: selectedRadio.value,
                                    question_type: questionType
                                };
                                hasAnswers = true;
                            }
                        }
                    });

                    // Check if we have any answers
                    if (!hasAnswers) {
                        showAlert('Please answer at least one question before submitting.', 'error');
                        isSubmitting = false;
                        return;
                    }

                    // Prepare the data for submission
                    const submissionData = {
                        exam_id: examId,
                        answers: answers
                    };

                    console.log('Submitting data:', submissionData);

                    // Submit the exam
                    fetch('submit_exam.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(submissionData)
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log('Submission response:', data);
                        if (data.success) {
                            window.location.href = 'exam_complete.php';
                        } else {
                            showAlert('Error submitting exam: ' + (data.message || 'Unknown error'), 'error');
                            isSubmitting = false;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showAlert('Error submitting exam. Please try again.', 'error');
                        isSubmitting = false;
                    });
                },
                // Cancel callback
                function() {
                    // Do nothing, just close the modal
                }
            );
        }

        // Add event listener for the submit button
        document.addEventListener('DOMContentLoaded', function() {
            const submitButton = document.querySelector('.fixed-navigation .btn-primary');
            if (submitButton) {
                submitButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    const examId = document.getElementById('current_exam_id').value;
                    submitExam(examId);
                });
            }
        });

        // Add this function to store test results when running code
        function storeTestResults(questionNumber, results) {
            const questionContainer = document.querySelector(`#question-${questionNumber}`);
            if (questionContainer) {
                questionContainer.dataset.testResults = JSON.stringify(results);
            }
        }

        // Modify the isCodeModified function to be more precise
        function isCodeModified(code) {
            return code && code.trim() !== '';
        }

        // Update the progress tracking for programming questions
        function updateProgrammingProgress(cm) {
            const textarea = cm.getTextArea();
            const questionContainer = textarea.closest('.question-container');
            if (questionContainer) {
                const code = cm.getValue().trim();
                if (isCodeModified(code)) {
                    questionContainer.dataset.answered = 'true';
                } else {
                    questionContainer.dataset.answered = 'false';
                }
                updateProgress();
            }
        }

        // Initialize CodeMirror with change tracking
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.code-editor').forEach(editor => {
                const cm = CodeMirror.fromTextArea(editor, {
                    mode: "python",
                    theme: "dracula",
                    lineNumbers: true,
                    autoCloseBrackets: true,
                    matchBrackets: true,
                    indentUnit: 4,
                    tabSize: 4,
                    indentWithTabs: true,
                    lineWrapping: true
                });

                // Track changes in the editor
                cm.on('change', function(instance) {
                    updateProgrammingProgress(instance);
                });
            });
        });

        function initializeTimer() {
            const timerElement = document.getElementById('timer');
            if (!timerElement) return;

            // Get duration from data attribute (in minutes)
            const durationInMinutes = parseInt(timerElement.dataset.duration) || 60;
            timeRemaining = durationInMinutes * 60; // Convert to seconds

            // Update timer every second
            examTimer = setInterval(updateTimer, 1000);
        }

        function updateTimer() {
            if (timeRemaining <= 0) {
                clearInterval(examTimer);
                showAlert('Time is up! Your exam will be submitted.', 'warning', function() {
                    document.getElementById('examForm').submit();
                });
                return;
            }

            timeRemaining--;
            
            // Calculate hours, minutes, seconds
            const hours = Math.floor(timeRemaining / 3600);
            const minutes = Math.floor((timeRemaining % 3600) / 60);
            const seconds = timeRemaining % 60;

            // Update timer display
            const timerElement = document.getElementById('timer');
            timerElement.textContent = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;

            // Add warning classes based on time remaining
            const examTimer = document.querySelector('.exam-timer');
            if (timeRemaining <= 300) { // Last 5 minutes
                examTimer.classList.remove('warning');
                examTimer.classList.add('danger');
            } else if (timeRemaining <= 600) { // Last 10 minutes
                examTimer.classList.add('warning');
            }
        }

        // Add this to your window.onbeforeunload handler
        window.onbeforeunload = function() {
            if (timeRemaining > 0) {
                return "Are you sure you want to leave? Your exam progress will be lost.";
            }
        };

        // Initialize CodeMirror for all programming questions
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.code-editor').forEach(editor => {
                CodeMirror.fromTextArea(editor, {
                    mode: "python",
                    theme: "dracula",
                    lineNumbers: true,
                    autoCloseBrackets: true,
                    matchBrackets: true,
                    indentUnit: 4,
                    tabSize: 4,
                    indentWithTabs: true,
                    lineWrapping: true
                });
            });
        });

        // Remove the code change tracking for programming questions since we only care about test runs
        function initializeCodeTracking() {
            document.querySelectorAll('.code-editor').forEach(editor => {
                if (editor.CodeMirror) {
                    editor.CodeMirror.on('change', function(cm) {
                        // We don't update progress here anymore - only when tests are run
                        console.log('Code changed - waiting for test run to mark as answered');
                    });
                }
            });
        }

        // Call this after CodeMirror initialization
        document.addEventListener('DOMContentLoaded', function() {
            initializeCodeTracking();
        });

        // Add this to your CSS
        const style = document.createElement('style');
        style.textContent = `
            .question-container.with-sidebar {
                display: flex;
                gap: 30px;
                align-items: flex-start;
            }

            .question-content {
                flex: 1;
            }

            .test-cases-sidebar {
                width: 400px;
                flex-shrink: 0;
                position: sticky;
                top: 20px;
            }

            .programming-question {
                width: 100%;
            }
        `;
        document.head.appendChild(style);

        // Add a cleanup function for when switching questions
        function cleanupEditor(questionNumber) {
            if (codeEditors[questionNumber]) {
                codeEditors[questionNumber].toTextArea(); // Convert back to textarea
                delete codeEditors[questionNumber]; // Remove from tracking object
            }
        }

        // Modify the reset function to properly handle content reset
        function resetEditor(questionNumber) {
            const editor = codeEditors[questionNumber];
            if (editor) {
                const textarea = editor.getTextArea();
                const starterCode = textarea.getAttribute('data-starter-code') || '';
                
                // Clear everything
                editor.setValue('');
                editor.clearHistory();
                
                // Set content based on saved answer or starter code
                if (answeredQuestions[questionNumber] && answeredQuestions[questionNumber].code) {
                    editor.setValue(answeredQuestions[questionNumber].code);
                } else {
                    editor.replaceRange(starterCode, {line: 0, ch: 0});
                }
                
                editor.refresh();
                editor.focus();
            }
        }

        // Security monitoring function
        function handleSecurityViolation(violationType) {
            // Ignore violations if we're submitting or performing a legitimate action
            if (isSubmitting) {
                return;
            }

            const currentTime = Date.now();
            
            // Check cooldown period
            // if (currentTime - lastWarningTime < WARNING_COOLDOWN) {
            //     return;
            // }
            
            // lastWarningTime = currentTime;
            // warningCount++;
            
            // Create warning message
            let warningMessage = '';
            switch(violationType) {
                case 'visibility':
                    warningMessage = 'Warning: Switching tabs or windows is not allowed during the exam.';
                    break;
                case 'blur':
                    warningMessage = 'Warning: Clicking outside the exam window is not allowed.';
                    break;
                case 'copy':
                    warningMessage = 'Warning: Copying exam content is not allowed.';
                    break;
                default:
                    warningMessage = 'Warning: Unauthorized action detected.';
            }

            // Use custom alert instead of popup
            showAlert(warningMessage, 'warning');
            
            // Auto-submit if max warnings reached
            // if (warningCount >= MAX_WARNINGS) {
            //     autoSubmitExam();
            // }
        }

        // Auto-submit function
        function autoSubmitExam() {
            showAlert('Maximum warnings reached. Your exam will be submitted automatically.', 'error', function() {
                submitExam(document.getElementById('current_exam_id').value);
            });
        }

        // Add event listeners for security monitoring
        document.addEventListener('DOMContentLoaded', function() {
            // Monitor tab/window switches
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'hidden' && !isSubmitting) {
                    handleSecurityViolation('visibility');
                }
            });

            // Monitor focus loss
            window.addEventListener('blur', () => {
                if (!isSubmitting) {
                    handleSecurityViolation('blur');
                }
            });

            // Prevent copy/paste
            document.addEventListener('copy', (e) => {
                // Allow copy in code editor
                if (!e.target.closest('.CodeMirror')) {
                    e.preventDefault();
                    handleSecurityViolation('copy');
                }
            });

            document.addEventListener('paste', (e) => {
                // Allow paste in code editor
                if (!e.target.closest('.CodeMirror')) {
                    e.preventDefault();
                }
            });

            // Prevent right-click
            document.addEventListener('contextmenu', (e) => {
                // Allow right-click in code editor
                if (!e.target.closest('.CodeMirror')) {
                    e.preventDefault();
                }
            });

            // Add click event listener for submit button
            const submitButton = document.querySelector('.btn-primary');
            if (submitButton) {
                submitButton.addEventListener('click', () => {
                    // Reset the flag after a short delay
                    setTimeout(() => {
                        isSubmitting = false;
                    }, 1000);
                });
            }

            // Add CSS for warning alerts
            const style = document.createElement('style');
            style.textContent = `
                .exam-warning-alert {
                    position: fixed;
                    top: 20px;
                    left: 50%;
                    transform: translateX(-50%);
                    background-color: #fff3cd;
                    border: 1px solid #ffeeba;
                    color: #856404;
                    padding: 12px 20px;
                    border-radius: 4px;
                    z-index: 9999;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    animation: slideDown 0.3s ease-out;
                }
                
                .exam-warning-alert.final-warning {
                    background-color: #f8d7da;
                    border-color: #f5c6cb;
                    color: #721c24;
                }
                
                .warning-content {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }
                
                .warning-count {
                    margin-left: auto;
                    font-size: 0.9em;
                    opacity: 0.8;
                }
                
                @keyframes slideDown {
                    from {
                        transform: translate(-50%, -100%);
                        opacity: 0;
                    }
                    to {
                        transform: translate(-50%, 0);
                        opacity: 1;
                    }
                }
            `;
            document.head.appendChild(style);
        });

