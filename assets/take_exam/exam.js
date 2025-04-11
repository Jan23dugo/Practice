        // Initialize global variables at the very top of the file, outside any functions
        let totalQuestions = 0;
        let currentQuestion = 1;
        let answeredQuestions = {};
        let flaggedQuestions = {};
        let codeEditors = {};
        let examTimer = null;
        let timeRemaining = 0;

        // Security monitoring variables
        let warningCount = 0;
        const MAX_WARNINGS = 3;
        let lastWarningTime = 0;
        const WARNING_COOLDOWN = 5000; // 5 seconds
        let isSubmitting = false;
        let isAlertModalOpen = false; // Flag to track if an alert modal is open
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
            
            // Set appropriate button text based on alert type
            if (type === 'warning' || type === 'error') {
                confirmBtn.textContent = 'OK';
            } else if (type === 'confirm') {
                confirmBtn.textContent = 'Yes, Submit';
            } else {
                confirmBtn.textContent = 'OK';
            }
            
            // Set flag that modal is open
            isAlertModalOpen = true;
            
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
                    // Reset alert modal flag
                    isAlertModalOpen = false;
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
                            // Reset alert modal flag
                            isAlertModalOpen = false;
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

        // Enable Ace language tools
        ace.require("ace/ext/language_tools");

        // Initialize Ace editors
        function initializeCodeEditors() {
            codeEditors = {}; // Clear existing editors
            
            // Initialize each editor
            for (let i = 1; i <= totalQuestions; i++) {
                const aceContainer = document.getElementById(`ace-editor-${i}`);
                if (!aceContainer) continue;
                
                const hiddenTextarea = document.getElementById(`code-editor-${i}`);
                if (!hiddenTextarea) continue;
                
                // Create Ace editor instance
                const editor = ace.edit(aceContainer);
                editor.setTheme("ace/theme/dracula");
                editor.session.setMode("ace/mode/python");
                editor.setOptions({
                    fontSize: "14px",
                    enableBasicAutocompletion: true,
                    enableSnippets: true,
                    enableLiveAutocompletion: true,
                    showPrintMargin: false,
                    tabSize: 4,
                    useSoftTabs: true
                });
                
                // Using setters directly for most common options
                editor.renderer.setShowPrintMargin(false);
                editor.renderer.setShowGutter(true);
                editor.setHighlightActiveLine(true);
                
                // Get starter code from textarea
                const starterCode = hiddenTextarea.value || '';
                if (starterCode) {
                    editor.session.setValue(starterCode);
                }
                
                // Update hidden textarea when editor changes
                editor.session.on('change', function() {
                    hiddenTextarea.value = editor.session.getValue();
                });
                
                // Store editor in our global object
                codeEditors[i] = editor;
                
                console.log(`Initialized editor for question ${i}`);
            }
            
            console.log('All Ace editors initialized');
        }

        // Function to change editor language
        function changeEditorLanguage(questionNumber, language) {
            const editor = codeEditors[questionNumber];
            if (!editor) return;
            
            // Map language values to Ace modes
            const modeMap = {
                'python': 'python',
                'javascript': 'javascript',
                'java': 'java',
                'csharp': 'csharp',
                'cpp': 'c_cpp'
            };
            
            const mode = modeMap[language] || 'python';
            editor.session.setMode(`ace/mode/${mode}`);
            
            console.log(`Changed editor ${questionNumber} language to ${language}`);
        }

        // Add this helper function to force update all editors
        function refreshAllEditors() {
            Object.values(codeEditors).forEach(editor => {
                editor.resize();
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
                        editor.resize();
                        editor.focus();
                        editor.navigateFileEnd(); // Move cursor to end
                    }, 10);
                }
            }

            currentQuestion = questionNumber;
            updateNavigationButtons(questionNumber);
            updateQuestionNavigator(questionNumber);
        }

        // Update the updateQuestionNavigator function
        function updateQuestionNavigator(currentNumber) {
            const questionNumbers = document.querySelectorAll('.question-number');
            if (!questionNumbers.length) {
                console.warn('No question number elements found');
                return;
            }
            
            questionNumbers.forEach(q => {
                q.classList.remove('current');
            });
            
            const currentNav = document.getElementById(`nav-question-${currentNumber}`);
            if (currentNav) {
                currentNav.classList.add('current');
            }
        }

        // Update the updatePalette function
        function updatePalette() {
            if (!totalQuestions) {
                console.warn('No questions to update in palette');
                return;
            }
            
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

            const code = editor.session.getValue().trim();
            
            if (!code) {
                editor.session.setValue('');
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

            const testCasesSidebar = questionContainer.querySelector(`.test-cases-sidebar`);
            
            try {
                // Show loading state in sidebar
                if (testCasesSidebar) {
                    testCasesSidebar.innerHTML = `
                        <div class="sidebar-header">
                            <h3>Test Cases</h3>
                            <span class="loading-indicator">Running...</span>
                        </div>
                        <div class="test-cases-content">
                            <div class="loading">
                                <div class="spinner"></div>
                                Running test cases...
                            </div>
                        </div>
                        <div class="sidebar-footer">
                            <button type="button" class="validate-code" disabled>
                                <span class="material-symbols-rounded">code</span>
                                Running...
                            </button>
                        </div>
                    `;
                }

                const code = editor.session.getValue().trim();
                console.log('Sending code:', code);

                // Send directly to our backend for test case evaluation
                const formData = new FormData();
                formData.append('action', 'execute');
                formData.append('code', code);
                formData.append('language', language);
                formData.append('question_id', questionId);
                formData.append('programming_id', programmingId);

                // Update the hidden textarea for form submission
                const hiddenTextarea = document.getElementById(`code-editor-${questionNumber}`);
                if (hiddenTextarea) {
                    hiddenTextarea.value = code;
                }

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
                        <div class="test-cases-content">
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
                                            <div class="actual">Output: ${test.actual || 'No output'}</div>
                                            ${!test.passed && test.error ? `
                                                <div class="error">
                                                    <strong>Error:</strong> Your code doesn't meet the requirements for this test case.
                                                </div>` : ''}
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                        <div class="sidebar-footer">
                            <button type="button" class="validate-code" onclick="runCode(${questionNumber}, ${questionId}, ${programmingId})">
                                <span class="material-symbols-rounded">code</span>
                                Run Code
                            </button>
                        </div>
                    `;
                    testCasesSidebar.innerHTML = sidebarHTML;
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
                        <div class="test-cases-content">
                            <div class="error-message">
                                ${error.message}
                            </div>
                        </div>
                        <div class="sidebar-footer">
                            <button type="button" class="validate-code" onclick="runCode(${questionNumber}, ${questionId}, ${programmingId})">
                                <span class="material-symbols-rounded">code</span>
                                Run Code
                            </button>
                        </div>
                    `;
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

            const code = editor.session.getValue();
            
            // Update the hidden textarea for form submission
            const hiddenTextarea = document.getElementById(`code-editor-${questionNumber}`);
            if (hiddenTextarea) {
                hiddenTextarea.value = code;
            }
            
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
                            <strong>Error:</strong> Your code did not execute correctly. Please check your logic and try again.
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
                            <pre>${data.output || 'No output'}</pre>
                        </div>
                    </div>`;
            })
            .catch(error => {
                resultContainer.innerHTML = `
                    <div class="error">
                        <strong>Error:</strong> Could not execute your code. Please try again.
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
            // Check if we need to create a toggle button
            if (!document.querySelector('.navigator-toggle')) {
                const toggleBtn = document.createElement('button');
                toggleBtn.className = 'navigator-toggle';
                toggleBtn.innerHTML = '<span class="material-symbols-rounded">map</span>';
                toggleBtn.setAttribute('aria-label', 'Open question navigator');
                document.body.appendChild(toggleBtn);
                
                // Create overlay if it doesn't exist
                if (!document.querySelector('.navigator-overlay')) {
                    const overlay = document.createElement('div');
                    overlay.className = 'navigator-overlay';
                    document.body.appendChild(overlay);
                }
                
                const navigatorElement = document.querySelector('.question-navigator');
                const overlay = document.querySelector('.navigator-overlay');
                
                if (navigatorElement) {
                    // Check if we already have a close button
                    const existingCloseBtn = navigatorElement.querySelector('.navigator-close');
                    let closeBtn;
                    
                    if (!existingCloseBtn) {
                        // Create and add close button only if it doesn't exist
                        closeBtn = document.createElement('button');
                        closeBtn.className = 'navigator-close';
                        closeBtn.innerHTML = '<span class="material-symbols-rounded">close</span>';
                        closeBtn.setAttribute('aria-label', 'Close navigator');
                        navigatorElement.prepend(closeBtn);
                    } else {
                        closeBtn = existingCloseBtn;
                    }
                    
                    // Add event listeners
                    toggleBtn.addEventListener('click', toggleNavigator);
                    
                    // Remove any existing event listeners first
                    closeBtn.removeEventListener('click', toggleNavigator);
                    closeBtn.addEventListener('click', toggleNavigator);
                    
                    if (overlay) {
                        overlay.removeEventListener('click', toggleNavigator);
                        overlay.addEventListener('click', toggleNavigator);
                    }
                } else {
                    console.warn("Question navigator element not found");
                }
            }
        }

        // Toggle the navigator popup
        function toggleNavigator() {
            const navigator = document.querySelector('.question-navigator');
            const overlay = document.querySelector('.navigator-overlay');
            
            if (!navigator || !overlay) {
                console.warn('Navigator elements not found');
                return;
            }
            
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
                                // Get the code as plain text
                                const code = editor.session.getValue().trim();
                                
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

                    try {
                        // Using the simple form submission approach
                        const formData = document.getElementById('exam-submit-form');
                        const allAnswersInput = document.getElementById('all-answers');
                        
                        // Set the answers
                        allAnswersInput.value = JSON.stringify(answers);
                        
                        // Submit the form
                        formData.submit();
                    } catch (error) {
                        console.error('Error during submission:', error);
                        showAlert('Error submitting exam. Please try again.', 'error');
                        isSubmitting = false;
                    }
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
                
                // Set isSubmitting flag before showing the time's up alert
                isSubmitting = true;
                
                showAlert('Time is up! Your exam will be submitted.', 'warning', function() {
                    const examForm = document.getElementById('examForm');
                    if (examForm) {
                        examForm.submit();
                    } else {
                        console.warn('Exam form not found');
                        // Reset the flag if submission failed
                        isSubmitting = false;
                    }
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
            if (timerElement) {
                timerElement.textContent = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            }

            // Add warning classes based on time remaining
            const examTimer = document.querySelector('.exam-timer');
            if (examTimer) {
                if (timeRemaining <= 300) { // Last 5 minutes
                    examTimer.classList.remove('warning');
                    examTimer.classList.add('danger');
                } else if (timeRemaining <= 600) { // Last 10 minutes
                    examTimer.classList.add('warning');
                }
            }
        }

        // Add this to prevent refreshing or closing the page
        window.addEventListener('beforeunload', function(e) {
            // Only show warning if exam is in progress (not when submitting)
            if (!isSubmitting) {
                // This will be ignored in many browsers for security reasons,
                // but will still trigger the warning in others
                const message = 'Warning: Refreshing or closing the page during the exam is prohibited. This action will be recorded as a security violation.';
                
                // Record this as a security violation
                warningCount++;
                
                // Auto-submit if max warnings reached
                if (warningCount >= MAX_WARNINGS) {
                    // We can't call autoSubmitExam here directly because the page is unloading
                    // Instead, we'll store the count in localStorage to check on reload
                    localStorage.setItem('warningCount', warningCount);
                }
                
                e.returnValue = message;
                return message;
            }
        });

        // Check warning count on load (detect if someone refreshed after warnings)
        document.addEventListener('DOMContentLoaded', function() {
            // Check if there were previous warnings
            const storedWarningCount = localStorage.getItem('warningCount');
            if (storedWarningCount) {
                warningCount = parseInt(storedWarningCount);
                localStorage.removeItem('warningCount'); // Clear it after reading
                
                // If they've exceeded warnings, auto-submit
                if (warningCount >= MAX_WARNINGS) {
                    // Show a message then auto-submit
                    setTimeout(() => {
                        autoSubmitExam();
                    }, 1000); // Small delay to ensure page has loaded
                } else if (warningCount > 0) {
                    // Just show a warning about their remaining attempts
                    showAlert(`Warning: Refreshing the page is a violation. You have ${MAX_WARNINGS - warningCount} warnings remaining.`, 'warning');
                }
            }
        });

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
            // Add change event listeners to all editors
            Object.keys(codeEditors).forEach(questionNumber => {
                const editor = codeEditors[questionNumber];
                if (editor) {
                    editor.session.on('change', function() {
                        // We don't update progress here anymore - only when tests are run
                        console.log('Code changed - waiting for test run to mark as answered');
                    });
                }
            });
        }

        // Call this after editor initialization
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
            
            .ace-editor {
                width: 100%;
                height: 350px;
                font-size: 14px;
                border-radius: 4px;
            }
            
            .editor-controls {
                margin: 10px 0;
                display: flex;
                gap: 10px;
            }
        `;
        document.head.appendChild(style);

        // Add a cleanup function for when switching questions
        function cleanupEditor(questionNumber) {
            if (codeEditors[questionNumber]) {
                // Remove Ace editor
                codeEditors[questionNumber].destroy();
                delete codeEditors[questionNumber]; // Remove from tracking object
            }
        }

        // Modify the reset function to properly handle content reset
        function resetEditor(questionNumber) {
            const editor = codeEditors[questionNumber];
            if (editor) {
                const hiddenTextarea = document.getElementById(`code-editor-${questionNumber}`);
                const starterCode = hiddenTextarea ? hiddenTextarea.getAttribute('data-starter-code') || '' : '';
                
                // Clear everything
                editor.session.setValue('');
                
                // Set content based on saved answer or starter code
                if (answeredQuestions[questionNumber] && answeredQuestions[questionNumber].code) {
                    editor.session.setValue(answeredQuestions[questionNumber].code);
                } else {
                    editor.session.setValue(starterCode);
                }
                
                editor.resize();
                editor.focus();
            }
        }

        // Security monitoring function
        function handleSecurityViolation(violationType) {
            // Ignore violations if we're submitting or an alert modal is open
            if (isSubmitting || isAlertModalOpen) {
                return;
            }

            const currentTime = Date.now();
            
            // Check cooldown period
            if (currentTime - lastWarningTime < WARNING_COOLDOWN) {
                return;
            }
            
            lastWarningTime = currentTime;
            warningCount++;
            
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

            // Check if this is the final warning
            if (warningCount >= MAX_WARNINGS) {
                // Final warning - inform student their exam will be submitted
                showAlert(`${warningMessage} You have reached the maximum allowed warnings. Your exam will be submitted automatically when you close this message.`, 'error', function() {
                    // Call autoSubmit after they acknowledge the message
                    autoSubmitExam();
                });
            } else {
                // Regular warning with attempt counter
                showAlert(`${warningMessage} (Attempt ${warningCount}/${MAX_WARNINGS})`, 'warning');
            }
        }

        // Auto-submit function - MODIFIED to skip confirmation
        function autoSubmitExam() {
            // Set submitting flag to prevent further warnings
            isSubmitting = true;
            
            // Show a brief notification that the exam is being submitted
            showAlert('Maximum warnings reached. Your exam is being submitted automatically.', 'error', function() {
                // Continue with auto-submission even if they click OK
                // This keeps the submit process going while letting the user know what's happening
            });
            
            // Get all answers
            let answers = {};

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
                        // Get the code as plain text
                        const code = editor.session.getValue().trim();
                        
                        if (code && code !== '# Write your code here') {
                            answers[questionId] = {
                                code: code,
                                programming_id: container.dataset.programmingId,
                                question_type: 'programming'
                            };
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
                    }
                }
            });

            try {
                // Using the simple form submission approach
                const formData = document.getElementById('exam-submit-form');
                const allAnswersInput = document.getElementById('all-answers');
                
                // Set the answers
                allAnswersInput.value = JSON.stringify(answers);
                
                // Submit the form immediately, after a short delay to let the alert be seen
                setTimeout(() => {
                    formData.submit();
                }, 1500);
            } catch (error) {
                console.error('Error during auto-submission:', error);
                
                // Even if there's an error, prevent further interaction
                document.body.innerHTML = '<div style="text-align:center;margin-top:50px;"><h2>This exam has been terminated due to multiple violations.</h2><p>Please contact your proctor.</p></div>';
            }
        }

        // Add event listeners for security monitoring
        document.addEventListener('DOMContentLoaded', function() {
            // Monitor tab/window switches
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'hidden' && !isSubmitting && !isAlertModalOpen) {
                    handleSecurityViolation('visibility');
                }
            });

            // Monitor focus loss
            window.addEventListener('blur', () => {
                if (!isSubmitting && !isAlertModalOpen) {
                    handleSecurityViolation('blur');
                }
            });

            // Prevent copy/paste
            document.addEventListener('copy', (e) => {
                // Allow copy in Ace editor
                const targetIsEditor = e.target.closest('.ace_editor') || e.target.classList.contains('ace_editor');
                if (!targetIsEditor) {
                    e.preventDefault();
                    handleSecurityViolation('copy');
                }
            });

            document.addEventListener('paste', (e) => {
                // Allow paste in Ace editor
                const targetIsEditor = e.target.closest('.ace_editor') || e.target.classList.contains('ace_editor');
                if (!targetIsEditor) {
                    e.preventDefault();
                }
            });

            // Prevent right-click
            document.addEventListener('contextmenu', (e) => {
                // Allow right-click in Ace editor
                const targetIsEditor = e.target.closest('.ace_editor') || e.target.classList.contains('ace_editor');
                if (!targetIsEditor) {
                    e.preventDefault();
                }
            });

            // Add click event listener for submit button
            const submitButton = document.querySelector('.fixed-navigation .btn-primary');
            if (submitButton) {
                submitButton.addEventListener('click', (e) => {
                    // Set isSubmitting to true before the click is processed
                    // This prevents the blur event from triggering a false security violation
                    isSubmitting = true;
                    
                    // The submitExam function will be called now
                    // If the user cancels in the confirmation dialog, isSubmitting will be reset
                    
                    // We also reset after a timeout as a failsafe
                    setTimeout(() => {
                        isSubmitting = false;
                    }, 1000);
                });
            }

            // Add CSS for warning alerts
            const style = document.createElement('style');
            document.head.appendChild(style);
        });

