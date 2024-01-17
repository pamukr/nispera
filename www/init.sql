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

-- Gente de DAW2
INSERT INTO users (name, surname, role, user, password_hash, email)
VALUES ('Marcos', "Venteo", 'teacher', 'marcos', '16b517295f23b7c61cdce9797c435e53db985e17120c1a6c2eeed18e017f95e5', 'marcos@nispera.work');

INSERT INTO users (name, surname, role, user, password_hash, email)
VALUES ('Alex', "Marin", 'teacher', 'amarin', '16b517295f23b7c61cdce9797c435e53db985e17120c1a6c2eeed18e017f95e5', 'alex@nispera.work');

INSERT INTO users (name, surname, role, user, password_hash, email)
VALUES ('Guillem', "Torres", 'student', 'gurex', '16b517295f23b7c61cdce9797c435e53db985e17120c1a6c2eeed18e017f95e5', 'gurex@nispera.work');

INSERT INTO users (name, surname, role, user, password_hash, email)
VALUES ('Pau', "Murcia", 'student', 'tumse', '16b517295f23b7c61cdce9797c435e53db985e17120c1a6c2eeed18e017f95e5', 'tumse@nispera.work');

INSERT INTO users (name, surname, role, user, password_hash, email)
VALUES ('Marc', "Zaragoza", 'student', 'mazapan', '16b517295f23b7c61cdce9797c435e53db985e17120c1a6c2eeed18e017f95e5', 'mazapan@nispera.work');

INSERT INTO users (name, surname, role, user, password_hash, email)
VALUES ('Miguel', "Gallardo", 'student', 'chimpy', '16b517295f23b7c61cdce9797c435e53db985e17120c1a6c2eeed18e017f95e5', 'chimpy@nispera.work');

-- Gente de ASIX2
INSERT INTO users (name, surname, role, user, password_hash, email)
VALUES ('Sergi', "Andres", 'teacher', 'sand', '16b517295f23b7c61cdce9797c435e53db985e17120c1a6c2eeed18e017f95e5', 'sand@nispera.work');

INSERT INTO users (name, surname, role, user, password_hash, email)
VALUES ('Raúl', "Dearriba", 'teacher', 'raul', '16b517295f23b7c61cdce9797c435e53db985e17120c1a6c2eeed18e017f95e5', 'segma@nispera.work');

INSERT INTO users (name, surname, role, user, password_hash, email)
VALUES ('Iván', "Mariscal", 'student', 'spartan', '16b517295f23b7c61cdce9797c435e53db985e17120c1a6c2eeed18e017f95e5', 'spartan@nispera.work');

INSERT INTO `groups` (name) VALUES ('DAW2');
INSERT INTO `groups` (name) VALUES ('ASIX2');

INSERT INTO groupmembers (group_id, user_id) VALUES (1, 1);
INSERT INTO groupmembers (group_id, user_id) VALUES (2, 1);
INSERT INTO groupmembers (group_id, user_id) VALUES (1, 2);
INSERT INTO groupmembers (group_id, user_id) VALUES (1, 3);
INSERT INTO groupmembers (group_id, user_id) VALUES (1, 4);
INSERT INTO groupmembers (group_id, user_id) VALUES (1, 5);
INSERT INTO groupmembers (group_id, user_id) VALUES (1, 6);
INSERT INTO groupmembers (group_id, user_id) VALUES (1, 7);
INSERT INTO groupmembers (group_id, user_id) VALUES (1, 8);
INSERT INTO groupmembers (group_id, user_id) VALUES (2, 8);
INSERT INTO groupmembers (group_id, user_id) VALUES (2, 9);
INSERT INTO groupmembers (group_id, user_id) VALUES (2, 10);

