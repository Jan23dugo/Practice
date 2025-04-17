-- Drop the existing table
DROP TABLE IF EXISTS student_answers;

-- Create the table with proper structure
CREATE TABLE student_answers (
    answered_questionsID INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    exam_id INT NOT NULL,
    question_id INT NOT NULL,
    answer_id INT,
    programming_id INT,
    programming_answer TEXT,
    answer_id_selected INT,
    question_type VARCHAR(50),
    submission_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_correct TINYINT(1) DEFAULT NULL,
    FOREIGN KEY (student_id) REFERENCES register_studentsqe(student_id),
    FOREIGN KEY (exam_id) REFERENCES exams(exam_id),
    FOREIGN KEY (question_id) REFERENCES questions(question_id),
    UNIQUE KEY unique_student_answer (student_id, exam_id, question_id)
); 