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
It's a fairly simple and small API with few endpoints.
- /api/health
  - Returns an OK 200 code message. Essentially used to test connectivity to the back-end.
- /api/register (POST)
  - Allows you to register a new user account. Requires a valid JSON body with a non-empty "username" and "password" variable limited to 255 characters.
- /api/login (POST)
  - Allows you to login to an existing user account. Requires a valid JSON body with a non-empty "username" and "password" variable limited to 255 characters.
- /api/logout (POST)
  - Allows you to terminate an active user session by logging out. Requires no JSON body.
- /api/user (GET / DELETE)
  - GET: allows you to retrieve user information. Requires a valid user session. Returns only the username of the current user.
  - DELETE: allows you to remove your user account. Requires a valid user session.
- /api/movies (GET)
  - Allows you to retrieve all movies stored in the database, including movie_id, titles, descriptions, average rating and amount of ratings.
- /api/movie?id=x (GET)
  - Allows you to retrieve all the information of a specified movie using movie_id as provided by id.
- /api/movie/ratings?id=x (GET)
  - Allows you to retrieve all individual user ratings that have been given to a movie.
- /api/ratings (GET / POST / PUT / DELETE) (All of these require a valid user session.)
  -  GET: Allows you to retrieve all your ratings.
  -  POST: Allows you to add a new rating to a movie you haven't rated yet. Requires a valid JSON body with a valid movie_id and a valid score between 1 to 5.
  -  PUT: Allows you to update a movie rating you have made. Requires a valid JSON body with a valid movie_id of a movie you have a rating for and a valid score between 1 to 5.
  -  DELETE: Allows you to delete a movie rating you have made. Requires a valid JSON body with a valid movie_id of a movie you have a rating for.
