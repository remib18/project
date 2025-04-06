# WE4A/WE4B project — Simplified Moodle Clone: Mooodle

This is our project for the WE4A/WE4B course at [UTBM](https://www.utbm.fr/) in our 2nd semester of 2024-2025.
The goal of this project is to create a simplified version of the Moodle web app.

## ⚠️ Important notice ⚠️

This project is under active development and is not yet finished. ***It is not ready for production use***.

As of now, we are mainly working on the frontend part of the project. The backend is not yet fully implemented.
A noticeable exemple is the fact that we use a lot of **hardcoded data**, and we **disabled access checks** in order to be able to work on the frontend part of the project.

## Team members

- [Rémi Bernard](https://github.com/remib18)
- [Mathys Kerjean](https://github.com/Mathmout)

## Project setup

### Install the required libraries:

- [PHP 8.4](https://www.php.net/downloads.php): You can choose to install the latest version of PHP, but we recommend using the same version as the one used in production.
- [Composer](https://getcomposer.org/download/): A dependency manager for PHP.
- [Node.js](https://nodejs.org/en/download/): A JavaScript runtime built on Chrome's V8 JavaScript engine. Used for building the assets.
- [NPM](https://www.npmjs.com/get-npm): A package manager for JavaScript. It comes with Node.js.
- [Symfony CLI](https://symfony.com/download): A command line tool to manage Symfony applications.

We are planning on removing the need for the installation of Node.js and NPM by providing compiled assets, but for now, you need to install them.

#### On macOS or Linux, you can use [Homebrew](https://brew.sh/) to install the required libraries:

One Linux, you can use the package manager of your choice (apt, dnf, etc.) to install the required libraries.
But we do not cover the documentation to do so. Hence, we recommend using Homebrew.

```bash
brew install php composer node symfony-cli/tap/symfony-cli
```

#### On Windows, you can use [Scoop](https://scoop.sh/) to install the required libraries:

```bash
scoop install php composer nodejs symfony-cli
```

### To install the project dependencies, please run:

```bash
composer install
npm install
```

### Set up your database connection in the `.env` file:

You can use SQLite for development purposes, but you can also use PostgresQL or MySQL. We recommend using PostgresQL for production.

```dotenv
DATABASE_URL="postgresql://[user]:[password]@[host]:[port]/[database]?serverVersion=[version]&charset=utf8"
```

Or eventually, you can just use our Docker compose file (`compose.yaml`) to run a database in a containerized environment.

```dotenv
POSTGRES_DB=[database]
POSTGRES_USER=[user]
POSTGRES_PASSWORD=[password]
```

Then run the following command to start the database:

```bash
docker compose up -d
```

Check the port of the database. It can change if there are multiple containers running on your machine. The default port is `5432`. You can check the port by running the following command:

```bash
docker compose ps
```

### Then to start the project for the first time, please run:

```bash
npm run build
php bin/console doctrine:migrations:migrate
php symfony serve
```

Those commands will install the dependencies, build the assets, create the database tables and start the development server.