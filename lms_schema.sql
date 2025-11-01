-- SQL Schema for Simple Online Learning Management System

-- Users table: stores both parent/guardian/teacher and child accounts.
-- role is either 'parent' or 'child'.  child accounts reference the parent via parent_id.
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('parent','child') NOT NULL,
  parent_id INT DEFAULT NULL,
  name VARCHAR(100) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_parent_id FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Courses table: represents SCORM‑compatible learning modules.  Each course has an
-- optional difficulty classification and awards a number of tokens upon completion.
CREATE TABLE IF NOT EXISTS courses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  difficulty ENUM('easy','medium','hard') DEFAULT 'easy',
  tokens_awarded INT DEFAULT 10,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Quizzes table: a quiz is associated with a course.  Students must complete the
-- associated course before they can take the quiz.  A perfect score yields
-- additional bonus tokens.  quiz_interval_days specifies how long a child must
-- wait between successive attempts (defaults to one week).
CREATE TABLE IF NOT EXISTS quizzes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  course_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  max_score INT DEFAULT 100,
  tokens_perfect INT DEFAULT 5,
  quiz_interval_days INT DEFAULT 7,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_quiz_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Enrollment table: links children to courses.  When a child completes a course
-- the completed flag is set and completion_date recorded.
CREATE TABLE IF NOT EXISTS enrollments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  child_id INT NOT NULL,
  course_id INT NOT NULL,
  completed TINYINT(1) DEFAULT 0,
  completion_date DATETIME DEFAULT NULL,
  CONSTRAINT fk_enroll_child FOREIGN KEY (child_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_enroll_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Quiz attempts table: records each quiz attempt for a child along with the
-- attempt date and resulting score.  A passed flag notes whether the score
-- met or exceeded the passing threshold (50% by default—handled at the
-- application layer).
CREATE TABLE IF NOT EXISTS quiz_attempts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  child_id INT NOT NULL,
  quiz_id INT NOT NULL,
  attempt_date DATETIME NOT NULL,
  score INT NOT NULL,
  passed TINYINT(1) NOT NULL DEFAULT 0,
  CONSTRAINT fk_attempt_child FOREIGN KEY (child_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_attempt_quiz FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

-- Prize shop items: parents can customise a list of prizes for their children.
-- Each item belongs to a particular parent and has a cost in tokens.
CREATE TABLE IF NOT EXISTS prize_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  parent_id INT NOT NULL,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  cost INT NOT NULL,
  CONSTRAINT fk_prize_parent FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Prize orders: when a child spends tokens on an item the order is recorded.
-- The fulfilled flag can be used by the parent to mark when the reward has
-- been given outside of the system.
CREATE TABLE IF NOT EXISTS prize_orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  child_id INT NOT NULL,
  item_id INT NOT NULL,
  order_date DATETIME NOT NULL,
  fulfilled TINYINT(1) DEFAULT 0,
  CONSTRAINT fk_order_child FOREIGN KEY (child_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_order_item FOREIGN KEY (item_id) REFERENCES prize_items(id) ON DELETE CASCADE
);

-- Learning record store (LRS): stores xAPI‑style statements for each learning
-- activity.  The object_type and object_id fields allow referencing
-- courses, quizzes or other activities.  result can hold arbitrary JSON
-- describing outcomes (e.g. scores, completion state).
CREATE TABLE IF NOT EXISTS lrs_statements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  actor_id INT NOT NULL,
  verb VARCHAR(50) NOT NULL,
  object_type VARCHAR(50),
  object_id INT,
  result JSON NULL,
  timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_lrs_actor FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Token tracking: keeps track of the number of tokens a child has earned and
-- spent.  Tokens are incremented when a course is completed or when a quiz is
-- passed with a perfect score.  They are decremented when items are purchased
-- from the prize shop.
CREATE TABLE IF NOT EXISTS child_tokens (
  child_id INT PRIMARY KEY,
  tokens_earned INT DEFAULT 0,
  tokens_spent INT DEFAULT 0,
  CONSTRAINT fk_tokens_child FOREIGN KEY (child_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert some example courses and quizzes for demonstration purposes.  These
-- will help verify that the application is functioning immediately after
-- installation.  Feel free to remove or modify them.
INSERT INTO courses (title, description, difficulty, tokens_awarded) VALUES
  ('Introduction to Algebra', 'Basic algebra concepts and operations.', 'easy', 10),
  ('Advanced Geometry', 'Exploration of geometric proofs and theorems.', 'medium', 15),
  ('Physics Fundamentals', 'Introductory concepts in physics including motion and energy.', 'hard', 20);

INSERT INTO quizzes (course_id, title, max_score, tokens_perfect, quiz_interval_days) VALUES
  (1, 'Algebra Quiz', 100, 5, 7),
  (2, 'Geometry Quiz', 100, 10, 7),
  (3, 'Physics Quiz', 100, 15, 7);