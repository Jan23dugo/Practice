<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Question</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style>
/* Scoped styles for the question builder */
.question-builder {
    padding: 20px;
    font-family: Arial, sans-serif;
}

.question-header {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #f8f9fa;
    padding: 10px 20px;
    border-radius: 8px;
    box-shadow: 0px 3px 10px rgba(0, 0, 0, 0.1);
}

.back-btn, .save-btn {
    background: #6c63ff;
    color: white;
    padding: 8px 15px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.toolbar button {
    background: none;
    border: none;
    font-size: 16px;
    cursor: pointer;
    padding: 5px;
}

.container-wrapper {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    margin-top: 20px;
}

textarea {
    width: 100%;
    height: 80px;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.choice {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

#addChoice {
    background: #28a745;
    color: white;
    padding: 5px 10px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    margin-top: 10px;
}

    </style>
</head>
<body>
<div class="container">
    <?php include 'sidebar.php'; ?>

    <div class="main">

    <div class="question-builder">
    <!-- Header with back button, question type, and save button -->
    <div class="question-header">
        <a href="create_exam.php">
        <button class="back-btn">←</button>
    </a>
        <select class="question-type">
            <option value="multiple-choice">Multiple Choice</option>
            <option value="true-false">True/False</option>
        </select>
        <select class="question-points">
            <option value="1">1 point</option>
            <option value="2">2 points</option>
        </select>
        <div class="toolbar">
            <button class="bold-btn"><b>B</b></button>
            <button class="italic-btn"><i>I</i></button>
            <button class="underline-btn"><u>U</u></button>
        </div>
        <button class="save-btn">Save Question</button>
    </div>

       <div class="container-wrapper">
        <h2>Add a New Question</h2>
        <form id="questionForm" action="save_question.php" method="POST">
            <label for="question">Question:</label>
            <textarea id="question" name="question" required></textarea>

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
            <button type="button" id="addChoice">+ Add Choice</button>
        </form>
    </div>
</div>
<script src="assets/js/side.js"></script>
<script> 
document.addEventListener("DOMContentLoaded", function () {
    const addChoiceBtn = document.getElementById("addChoice");
    const choicesContainer = document.getElementById("choices");

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
});

</script>
</body>
</html>
