## SafariChat - AI Based Customer Engagement Platform

Welcome to **safarichat**, a Laravel-based platform designed to simplify and streamline worldwide communications. This project leverages Laravel's robust features to provide a scalable, maintainable, and developer-friendly environment.

---

### Table of Contents

- [Project Overview](#project-overview)
- [Features](#features)
- [Getting Started](#getting-started)
- [Packages Used](#packages-used)
- [Development Workflow](#development-workflow)
- [Contributing](#contributing)
- [License](#license)

---

## Project Overview

DikoDiko is a cloud platform for managing local events. It provides tools to handle event creation, scheduling, participant management, and more, all built on top of the Laravel framework.

## theme used
http://metrica.laravel.themesbrand.com/login 

## Features

- Simple, fast routing engine
- Powerful dependency injection container
- Multiple back-ends for session and cache storage
- Expressive, intuitive database ORM (Eloquent)
- Database-agnostic schema migrations
- Robust background job processing
- Real-time event broadcasting

## Getting Started

### Prerequisites

- PHP >= 8.1
- Composer
- MySQL or compatible database
- Node.js & npm (for frontend assets)

### Installation

1. **Clone the repository:**
    ```bash
    git clone https://github.com/yourusername/dikodiko.git
    cd dikodiko
    ```

2. **Install dependencies:**
    ```bash
    composer install
    npm install
    ```

3. **Copy and configure environment:**
    ```bash
    cp .env.example .env
    # Edit .env with your database and app settings
    ```

4. **Generate application key:**
    ```bash
    php artisan key:generate
    ```

5. **Run migrations:**
    ```bash
    php artisan migrate
    ```

6. **Serve the application:**
    ```bash
    php artisan serve
    ```

## Packages Used

- [`krlove/eloquent-model-generator`](https://github.com/krlove/eloquent-model-generator): For generating Eloquent models from existing database tables.

**Example usage:**
```bash
php artisan krlove:generate:model User --table-name=user
```

## Development Workflow

- Follow PSR-12 coding standards.
- Use feature branches for new features or bug fixes.
- Run tests before submitting pull requests.

## Contributing

Contributions are welcome! Please fork the repository and submit a pull request.

## License

This project is open-sourced under the MIT license.

---
## key prompt ai to simplify tasks

# extract keywords from file into a language file
extract values inside the laravel language translation function in this file #file:index.blade.php and write them here as key for json , while the value of the json needs to be this word but removing underscore between words

# convert text from html elements into laravel language translate 
extract all text inside html tags and replace them with a laravel language translate function, and ensure on each word with space, join it with underscore (_) to form a contatenated word