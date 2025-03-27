# WE4A/WE4B project

This is the project for the WE4A/WE4B course.
The goal is to build a simplified version of the Moodle web app.

## Team members


## Project setup

Install the required libraries:

- [PHP 8.4](https://www.php.net/downloads.php)
- [Composer](https://getcomposer.org/download/)
- [Node.js](https://nodejs.org/en/download/)
- [NPM](https://www.npmjs.com/get-npm)

To install the project dependencies, please run:

```bash
composer install && npm install
```

Then to start the project for the first time, please run:

```bash
npm run build
php artisan migrate
php artisan serve
```

Those commands will install the dependencies, build the assets, create the database tables and start the server.