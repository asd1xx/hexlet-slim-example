### linter status:
[![Maintainability](https://api.codeclimate.com/v1/badges/45afac86715070bf1d03/maintainability)](https://codeclimate.com/github/asd1xx/hexlet-slim-example/maintainability)

# CRUD on the Slim framework

### Demo:
(Project will be deployed at site Render)  
https://hexlet-slim-example-7ynn.onrender.com

## About the project

The system supports the following actions:
- View a list of all users.
- Creating a user.
- Viewing user information.
- Editing a user.
- Deleting a user.

## System requirements

- Composer 2.6.6
- PHP 8.3

## Installation instructions

Perform the following steps in sequence:

1. Clone the repository:
    
    ```bash
    git@github.com:asd1xx/hexlet-slim-example.git crud-slim
    ```
    
2. Go to the project directory:
    
    ```bash
    cd crud-slim
    ```
    
3. Installing dependencies:
    
    ```bash
    composer install
    ```
    
4. Start a project:
    
    ```bash
    make start
    ```

5. Open in browser:
    
    ```bash
    http://localhost:8000
    ```

## Information about routes and methods

| Method | Route            | Info                             |
|--------|------------------|----------------------------------|
| GET    | /                | main page                        |
| GET    | /users           | getting a list of all users      |
| GET    | /users/new       | output of the user сreation form |
| GET    | /users/{id}/edit | output of the user edit form     |
| POST   | /users           | creating a user                  |
| PATCH  | /users/{id}      | user update                      |
| DELETE | /users/{id}      | deleting a user                  |