# BasicWebApp

This project is a simple web application with a backend written in PHP and a backend written in Java. It allows users to create an account, login, logout, delete an account and rate some movies that are stored in a database.

The purpose of this project is to let me refresh some basic web development concepts and to also refresh my PHP, Java and Docker knowledge.

## Technology Used
- Visual Studio Code
  - PHP
  - Java
  - HTML
  - CSS
  - JavaScript
- Git Bash
- Postman
- Docker Desktop / Docker Compose
- MySQL
- Copilot

## Structure
This project uses multiple Docker containers to fully containerize the webapp. There is 1 container for the front-end (HTML/CSS/JS), 2 containers for the back-end (1 for PHP, 1 for Java), 1 container for hosting the MySQL Database. This structure makes it easy to switch the back-end between the PHP and Java version as both implement the same REST API, meaning the front-end interacts with both in the exact same way.
