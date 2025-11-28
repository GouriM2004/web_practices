CREATE DATABASE IF NOT EXISTS resume_tailor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE resume_tailor;

-- users
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150),
  email VARCHAR(150) UNIQUE,
  password VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- resumes
CREATE TABLE IF NOT EXISTS resumes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  filename VARCHAR(255) NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  text LONGTEXT,
  parsed_json JSON DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- jobs (job descriptions stored or imported)
CREATE TABLE IF NOT EXISTS jobs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255),
  company VARCHAR(255),
  location VARCHAR(255),
  jd_text LONGTEXT,
  parsed_requirements JSON DEFAULT NULL,
  posted_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- job_requirements (optional normalized)
CREATE TABLE IF NOT EXISTS job_requirements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  job_id INT NOT NULL,
  requirement VARCHAR(255),
  weight INT DEFAULT 1,
  FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
);

-- resume_analysis (stores computed ATS score, skill gaps, suggestions)
CREATE TABLE IF NOT EXISTS resume_analysis (
  id INT AUTO_INCREMENT PRIMARY KEY,
  resume_id INT NOT NULL,
  job_id INT DEFAULT NULL,
  ats_score DECIMAL(5,2) DEFAULT NULL,
  matched_keywords JSON DEFAULT NULL,
  missing_skills JSON DEFAULT NULL,
  suggestions JSON DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (resume_id) REFERENCES resumes(id) ON DELETE CASCADE,
  FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE SET NULL
);

-- skills (canonical skill list)
CREATE TABLE IF NOT EXISTS skills (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) UNIQUE,
  synonyms JSON DEFAULT NULL
);

-- resume_skills (normalized mapping)
CREATE TABLE IF NOT EXISTS resume_skills (
  id INT AUTO_INCREMENT PRIMARY KEY,
  resume_id INT NOT NULL,
  skill_id INT NOT NULL,
  confidence DECIMAL(4,2) DEFAULT 1.0,
  FOREIGN KEY (resume_id) REFERENCES resumes(id) ON DELETE CASCADE,
  FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
);

-- applications / recommended jobs (history)
CREATE TABLE IF NOT EXISTS recommendations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  resume_id INT NOT NULL,
  job_id INT NOT NULL,
  match_score DECIMAL(5,2),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (resume_id) REFERENCES resumes(id) ON DELETE CASCADE,
  FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
);

-- audit logs
CREATE TABLE IF NOT EXISTS audit_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  action VARCHAR(255),
  meta JSON DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
