# NYT BESTSELLER-API Wrapper
NYT API Wrapper is a Laravel-based application that provides an API wrapper for interacting with the New York Times API. It includes features such as fetching bestseller data and health checks for the application.

## Features

- Fetch bestseller data with filters like author, ISBN, title, and more.
- Health check endpoint to monitor database and cache connectivity.
- Dockerized environment for development and testing.
-----
## Prerequisites

Ensure you have the following installed on your system:
- [Docker](https://www.docker.com/).

-----

## Installation

1. **Clone the Repository**:
   ```bash
   git clone https://github.com/your-repo/nyt-api-wrapper.git
   cd nyt-api-wrapper

2. **Set Up Environment Variables**: Copy the `.env.example` file to `.env` and configure the necessary environment variables

3. **Start Docker Containers**:Use Laravel Sail to start the application ` ./vendor/bin/sail up -d `

4. **Install Dependencies**:Run the following command to install PHP dependencies: ` ./vendor/bin/sail composer install `

5. **Run Migrations**:Set up the database by running migrations: ` ./vendor/bin/sail artisan migrate `

6. **Generate Application Key**: ` ./vendor/bin/sail artisan key:generate `

-----

## Usage
API Endpoints

1. **Bestsellers Endpoint**:
- URL: /api/v1/bestsellers
- Method: GET
- Query Parameters:
    - author ` (string, optional) `: Filter by author.
    - isbn ` (string, optional) ` : Filter by ISBN.
    - title ` (string, optional) ` : Filter by title.
    - offset ` (integer, optional) ` : Pagination offset.
    - age-group ` (string, optional) ` : Filter by age group.
    - price ` (string, optional) ` : Filter by price.
    - publisher ` (string, optional) ` : Filter by publisher.
    - contributor ` (string, optional) ` : Filter by contributor.

2. **Bestsellers Endpoint**:
- URL: ` /api/health `
- Method: ` GET `
- Description: Returns the health status of the application, including database and cache connectivity.

-----

## Health Checks
- Health Checks
    - The application includes health checks for the following services:

- Database:
    - Verifies connectivity to the MySQL database.

- Cache:
    - Verifies connectivity to the Redis cache.

-----

## Swagger API Documentation
The application uses Swagger for API documentation. To view the documentation:

1. **Run Migrations**:Set up the database by running migrations: ` ./vendor/bin/sail artisan migrate `

2. **Generate Application Key**: ` ./vendor/bin/sail artisan key:generate `

