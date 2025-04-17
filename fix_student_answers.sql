-- Drop the primary key constraint from answer_id
ALTER TABLE student_answers DROP PRIMARY KEY;

-- Add a new auto-incrementing primary key column
ALTER TABLE student_answers ADD COLUMN id INT AUTO_INCREMENT PRIMARY KEY FIRST;

-- Add a unique constraint to prevent duplicate submissions
ALTER TABLE student_answers 
ADD CONSTRAINT unique_student_answer 
UNIQUE (student_id, exam_id, question_id); 