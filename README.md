# BasicWebApp

This project is a simple web application with a backend written in PHP and a backend written in Java. It allows users to create an account, login, logout, delete an account and rate some movies that are stored in a database.

The purpose of this project is to let me refresh some basic web development concepts and to also refresh my PHP, Java and Docker knowledge.

## Technology Used
- Visual Studio Code (For any code that was written.)
  - PHP
  - Java
  - HTML
  - CSS
  - JavaScript (Soon, probably.)
- Git Bash (For git related commands, I like using command line for these things.)
- Postman (For API endpoint / backend testing without having to develop a full front-end.)
- Docker Desktop / Docker Compose (For the containerization of the application. Also mostly used in order to stretch my Docker muscles as it had been a while.)
- MySQL / MySQL Workbench (Used for database management and other database related things.)
- Copilot (Mostly for front-end things, since the main point of this project was the back-end and PHP / Java refresher.)

## Structure
This project uses multiple Docker containers to fully containerize the webapp. There is 1 container for the front-end (HTML/CSS/JS), 2 containers for the back-end (1 for PHP, 1 for Java), 1 container for hosting the MySQL Database. This structure makes it easy to switch the back-end between the PHP and Java version as both implement the same REST API, meaning the front-end interacts with both in the exact same way.

- The front-end container is running Nginx.
- The back-end PHP container is running Apache.

## Docker Compose
The provided docker-compose.yml file is a template to create the different containers. Change the names, usernames, passwords, ports, etc... to whatever is applicable for your own local environment.

## The API

