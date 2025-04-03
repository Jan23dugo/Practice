        // Initialize global variables at the very top of the file, outside any functions
        let totalQuestions = 0;
        let currentQuestion = 1;
        let answeredQuestions = {};
        let flaggedQuestions = {};
        let codeEditors = {};
        let examTimer = null;
        let timeRemaining = 0;

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
                initializeEditors();
                console.log('Editors initialized after delay');
                console.log('Available editors:', Object.keys(codeEditors));
            }, 500);

            // Initialize timer
            initializeTimer();
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
        function initializeEditors() {
            console.log('Initializing editors...');
            
            document.querySelectorAll('.programming-question').forEach(programmingQuestion => {
                const editor = programmingQuestion.querySelector('.code-editor');
                if (!editor) return;
        
                const idParts = editor.id.split('-');
                if (idParts.length < 3) return;
                const questionNumber = idParts[2];
        
                // Only initialize if not already initialized
                if (!codeEditors[questionNumber]) {
                    // Get starter code from the data attribute
                    const starterCode = editor.getAttribute('data-starter-code') || '';
                    const programmingId = editor.getAttribute('data-programming-id');
        
                    // Create editor with empty initial value
                    const cmEditor = CodeMirror.fromTextArea(editor, {
                        mode: 'python',
                        theme: 'dracula',
                        lineNumbers: true,
                        indentUnit: 4,
                        tabSize: 4,
                        indentWithTabs: false,
                        lineWrapping: true,
                        matchBrackets: true,
                        autoCloseBrackets: true,
                        readOnly: false,
                        value: '', // Start with empty value
                        extraKeys: {
                            "Tab": function(cm) {
                                cm.replaceSelection("    ", "end");
                            }
                        }
                    });
        
                    // First clear any existing content
                    cmEditor.setValue('');
                    
                    // Then set the content
                    if (answeredQuestions[questionNumber] && answeredQuestions[questionNumber].code) {
                        cmEditor.setValue(answeredQuestions[questionNumber].code);
                    } else {
                        // Insert starter code as regular text
                        cmEditor.replaceRange(starterCode, {line: 0, ch: 0});
                    }
        
                    cmEditor.setSize(null, '400px');
                    codeEditors[questionNumber] = cmEditor;
        
                    // Simple change handler that treats all content as user input
                    cmEditor.on('change', function(cm) {
                        const content = cm.getValue();
                        if (content.trim()) {
                            answeredQuestions[questionNumber] = {
                                type: 'programming',
                                code: content,
                                programmingId: programmingId
                            };
                        } else {
                            delete answeredQuestions[questionNumber];
                        }
                        updatePalette();
                        updateProgress();
                    });
        
                    // Make sure the editor is ready for input
                    setTimeout(() => {
                        cmEditor.refresh();
                        cmEditor.focus();
                        // Place cursor at the end of the content
                        const lastLine = cmEditor.lineCount() - 1;
                        const lastCh = cmEditor.getLine(lastLine).length;
                        cmEditor.setCursor(lastLine, lastCh);
                    }, 100);
                }
            });
        }

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

        // Modify the runCode function to just do basic syntax check
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

                const formData = new FormData();
                formData.append('action', 'execute');
                formData.append('code', code);
                formData.append('language', language); // Add language to request
                formData.append('question_id', questionId);
                formData.append('programming_id', programmingId);

                // Log the request
                console.log('Sending request with:', {
                    code: code,
                    language: language,
                    questionId: questionId,
                    programmingId: programmingId
                });

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

                // Update the main test results area (optional - you can remove this if you want results only in sidebar)
                if (testResults) {
                    testResults.innerHTML = ''; // Clear the main test results area
                }

                // Update UI with results
                if (testResults) {
                    const result = data.result;
                    const allTestsPassed = result.every(test => test.passed);
                    
                    // Update the sample test case UI
                    const sampleTestCase = document.querySelector('.sample-test-case');
                    if (sampleTestCase) {
                        sampleTestCase.className = `sample-test-case ${allTestsPassed ? 'passed' : 'failed'}`;
                    }

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

                    // Update the status indicator
                    if (testResults) {
                        testResults.className = `status-indicator ${allTestsPassed ? 'success' : 'error'}`;
                        testResults.innerHTML = `
                            <span class="material-symbols-rounded">
                                ${allTestsPassed ? 'check_circle' : 'error'}
                            </span>`;
                    }

                    // If all tests passed, mark the question as answered
                    if (allTestsPassed) {
                        answeredQuestions[questionNumber] = true;
                        updatePalette();
                        updateProgress();
                        
                        // Add visual feedback
                        const questionContainer = document.getElementById(`question-${questionNumber}`);
                        if (questionContainer) {
                            questionContainer.classList.add('passed');
                        }
                    }
                }

                // After receiving test results, store them
                storeTestResults(questionNumber, data.result);

            } catch (error) {
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
                }

                if (testResults) {
                    testResults.className = 'status-indicator error';
                    testResults.innerHTML = '<span class="material-symbols-rounded">error</span>';
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

        // Also add this function to update the progress bar
        function updateProgress() {
            let answeredCount = 0;
            const total = window.totalQuestions;

            // Count multiple choice/true-false answers
            document.querySelectorAll('input[type="radio"]:checked').forEach(() => {
                answeredCount++;
            });

            // Count programming answers - only check if code exists
            document.querySelectorAll('.programming-question').forEach(questionDiv => {
                const questionNumber = questionDiv.closest('.question-container').id.replace('question-', '');
                const editor = codeEditors[questionNumber];
                if (editor) {
                    const code = editor.getValue().trim();
                    if (code && code !== '# Write your code here') {
                        answeredCount++;
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

        // Modify submitExam to include debug
        function submitExam(examId) {
            if (!confirm('Are you sure you want to submit your exam? This action cannot be undone.')) {
                return;
            }

            console.log('Starting exam submission...');
            let answers = {};
            let hasAnswers = false;

            // Get all question containers
            const questionContainers = document.querySelectorAll('.question-container');
            console.log('Found questions:', questionContainers.length);

            questionContainers.forEach(container => {
                const questionId = container.dataset.questionId;
                const questionType = container.dataset.questionType;

                console.log('Processing question:', questionId, 'Type:', questionType);

                if (questionType === 'programming') {
                    // Handle programming questions
                    const questionNumber = container.id.replace('question-', '');
                    const editor = codeEditors[questionNumber];
                    
                    if (editor) {
                        const code = editor.getValue().trim();
                        console.log('Programming answer code:', code);
                        
                        // Consider any non-empty code that's not the default as an answer
                        if (code && code !== '# Write your code here') {
                            answers[questionId] = {
                                code: code,
                                programming_id: container.dataset.programmingId,
                                question_type: 'programming'
                            };
                            hasAnswers = true;
                            console.log('Added programming answer for question:', questionId);
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
                        console.log('Added choice answer for question:', questionId);
                    }
                }
            });

            console.log('Collected answers:', answers);

            // Check if we have any answers
            if (!hasAnswers) {
                let message = 'Please answer at least one question before submitting.\n';
                alert(message);
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
                    alert('Exam submitted successfully! The results will be released by the administrator.');
                    window.location.href = 'stud_dashboard.php';
                } else {
                    alert('Error submitting exam: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error submitting exam. Please try again.');
            });
        }

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
                alert('Time is up! Your exam will be submitted.');
                document.getElementById('examForm').submit();
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

        // Add this to track changes in CodeMirror editors
        function initializeCodeTracking() {
            document.querySelectorAll('.code-editor').forEach(editor => {
                if (editor.CodeMirror) {
                    editor.CodeMirror.on('change', function(cm) {
                        const questionContainer = cm.getTextArea().closest('.question-container');
                        if (questionContainer) {
                            const code = cm.getValue().trim();
                            // Mark as answered if there's any non-default code
                            if (code && code !== '# Write your code here') {
                                questionContainer.dataset.answered = 'true';
                            } else {
                                questionContainer.dataset.answered = 'false';
                            }
                            updateProgress();
                        }
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
            .CodeMirror {
                font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', 'Consolas', monospace;
                font-size: 14px;
                line-height: 1.5;
                height: auto;
            }
            .CodeMirror-scroll {
                min-height: 100px;
            }
            .CodeMirror pre {
                padding: 0 4px;
            }
            .CodeMirror-lines {
                padding: 10px 0;
            }
            .CodeMirror-linenumber {
                padding: 0 3px 0 5px;
                min-width: 20px;
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

