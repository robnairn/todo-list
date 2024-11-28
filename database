CREATE DATABASE todo_db;
USE todo_db;

CREATE TABLE lists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL
);

CREATE TABLE todos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    list_id INT NOT NULL,
    task VARCHAR(255) NOT NULL,
    position INT NOT NULL,
    completed BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (list_id) REFERENCES lists(id) ON DELETE CASCADE
);

-- Insert a default list
INSERT INTO lists (name) VALUES ('Default List');
