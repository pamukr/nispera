-- Tables drop
DROP TABLE IF EXISTS teammembers;
DROP TABLE IF EXISTS teams;
DROP TABLE IF EXISTS act_skills_marks;
DROP TABLE IF EXISTS act_skills;
DROP TABLE IF EXISTS activities;
DROP TABLE IF EXISTS project_skills;
DROP TABLE IF EXISTS skills;
DROP TABLE IF EXISTS project_users;
DROP TABLE IF EXISTS projects;
DROP TABLE IF EXISTS groupmembers;
DROP TABLE IF EXISTS `groups`;
DROP TABLE IF EXISTS users;

-- Users
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50),
    surname VARCHAR(100),
    role VARCHAR(50),
    user VARCHAR(50),
    password_hash CHAR(64),
    email VARCHAR(50)
);

-- Groups
CREATE TABLE `groups` (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50)
);

CREATE TABLE groupmembers (
    group_id INT,
    user_id INT,
    PRIMARY KEY (group_id, user_id),
    FOREIGN KEY (group_id) REFERENCES `groups`(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Projects
CREATE TABLE projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50),
    status VARCHAR(50)
);

CREATE TABLE project_users (
    project_id INT,
    user_id INT,
    mark FLOAT,
    PRIMARY KEY (project_id, user_id),
    FOREIGN KEY (project_id) REFERENCES projects(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Skills
CREATE TABLE skills (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50),
    src VARCHAR(50)
);

CREATE TABLE project_skills (
    project_id INT,
    skill_id INT,
    percentage INT,
    PRIMARY KEY (project_id, skill_id),
    FOREIGN KEY (project_id) REFERENCES projects(id),
    FOREIGN KEY (skill_id) REFERENCES skills(id)
);

-- Activities
CREATE TABLE activities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50),
    description VARCHAR(50),
    status VARCHAR(50),
    project_id INT,
    FOREIGN KEY (project_id) REFERENCES projects(id)
);

CREATE TABLE act_skills (
    id INT PRIMARY KEY AUTO_INCREMENT,
    activity_id INT,
    skill_id INT,
    percentage INT,
    FOREIGN KEY (activity_id) REFERENCES activities(id),
    FOREIGN KEY (skill_id) REFERENCES skills(id)
);

CREATE TABLE act_skills_marks (
    act_skill_id INT,
    user_id INT,
    mark FLOAT,
    PRIMARY KEY (act_skill_id, user_id),
    FOREIGN KEY (act_skill_id) REFERENCES act_skills(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Teams
CREATE TABLE teams (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50),
    activity_id INT,
    FOREIGN KEY (activity_id) REFERENCES activities(id)
);

CREATE TABLE teammembers (
    team_id INT,
    user_id INT,
    PRIMARY KEY (team_id, user_id),
    FOREIGN KEY (team_id) REFERENCES teams(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);



-- Usuario admin hardcodeado
INSERT INTO users (name, surname, role, user, password_hash, email)
VALUES ('Nispera', NULL, 'admin', 'nispera', '2e5eafa70dcac9ee2ad984b319530890404ec303b0e93d37ad6852ff41b32285', 'admin@nispera.work');

-- Usuario teacher hardcodeado
INSERT INTO users (name, surname, role, user, password_hash, email)
VALUES ('Testing', "Teacher", 'teacher', 'prof', '03ac674216f3e15c761ee1a5e255f067953623c8b388b4459e13f978d7c846f4', 'profe@nispera.work');

INSERT INTO users (name, surname, role, user, password_hash, email)
VALUES ('Testing', "Student", 'student', 'test', '03ac674216f3e15c761ee1a5e255f067953623c8b388b4459e13f978d7c846f4', 'student1@nispera.work');

INSERT INTO users (name, surname, role, user, password_hash, email)
VALUES ('Testing2', "Student", 'student', 'test2', '03ac674216f3e15c761ee1a5e255f067953623c8b388b4459e13f978d7c846f4', 'student2@nispera.work');

INSERT INTO users (name, surname, role, user, password_hash, email)
VALUES ('Testing3', "Student", 'student', 'test3', '03ac674216f3e15c761ee1a5e255f067953623c8b388b4459e13f978d7c846f4', 'student3@nispera.work');

INSERT INTO users (name, surname, role, user, password_hash, email)
VALUES ('Testing4', "Student", 'student', 'test4', '03ac674216f3e15c761ee1a5e255f067953623c8b388b4459e13f978d7c846f4', 'student4@nispera.work');

INSERT INTO projects(name, status) VALUES ('Projecte 1', 'open');

INSERT INTO project_users (project_id, user_id) VALUES (1, 1);
INSERT INTO project_users (project_id, user_id) VALUES (1, 3);

INSERT INTO `groups` (name) VALUES ('DAW2');
INSERT INTO `groups` (name) VALUES ('ASIX2');
INSERT INTO groupmembers (group_id, user_id) VALUES (1, 1); 
INSERT INTO groupmembers (group_id, user_id) VALUES (1, 2); 
INSERT INTO groupmembers (group_id, user_id) VALUES (2, 2); 
INSERT INTO groupmembers (group_id, user_id) VALUES (1, 3);
INSERT INTO groupmembers (group_id, user_id) VALUES (2, 5);
INSERT INTO groupmembers (group_id, user_id) VALUES (1, 6);
