# Create an API Server with Authentication.

Laravel is a popular PHP framework and can be used as a API server as well as a fully fledged website with bells and whistles.

For this I will concentrate on using it as an API server.

I make the assumption that you have a local environment for development seup already. If you don’t, I recommend MAMP or XAMMP. The Laravel docs list other options for you set up a local environment such as Homestead or Valet, your mileage may vary.

* Quickstart: Docker
* Quickstart: TLDR

* Installing Laravel
* Setup and Configure Database
* Installing and Configuring Passport
* Database Seeding
* Create Test for OAuth Token Endpoint
* Create a Proxy for User Login
* Create a Register Endpoint
* Create a Reset Password and Password Update Endpoints
* Create an Endpoint to Retrieve and Update User Details
* Set up CORS on API
* Deploy the Code to Heroku


## Quickstart: Docker

[Docker](https://www.docker.com/), using [Laradock](http://laradock.io/).

```bash
git clone --recursive -j8 git@github.com:wonkenstein/laravel-api.git
cd laravel-api/laradock
docker-compose up -d nginx mariadb
docker-compose exec --user=laradock workspace composer install
docker-compose exec --user=laradock workspace cp .env.example .env
docker-compose exec --user=laradock workspace php artisan key:generate
docker-compose exec --user=laradock workspace php artisan migrate 
docker-compose exec --user=laradock workspace php artisan db:seed --class=UsersTableSeeder
docker-compose exec --user=laradock workspace php artisan passport:keys
docker-compose exec --user=laradock workspace php artisan passport:client --password --name=laravel-api
```

Manually copy the generated **Client ID** and **Client Secret** values into the `.env` file, at: 

```bash
AUTH_CLIENT_ID=...
AUTH_CLIENT_SECRET=...
```

_(**@todo** edit the `.env` file automatically, using e.g. sed)_

Finally, run the **Unit Tests**:

```bash
docker-compose exec --user=laradock workspace php ./vendor/bin/phpunit
# cURL error 7: Failed to connect to localhost port 80: Connection refused
# It's possibly a Docker network traffic restriction issue that should be fixed.
```

_This install could be reduced to a few lines of code as most of the commands could be scripted, using something like [Docker Sync](https://github.com/EugenMayer/docker-sync/wiki/7.-Scripting-with-docker-sync) or a `.sh` script._

## Quickstart: TLDR

If don't want to read all of this and just want to get this running, follow the steps below. Otherwise go to [Installing Laravel](#installing-laravel)

* Clone the repo
* Install laravel and required packages
    * `composer install`
*  Create a `.env` for your application
    * `cp .env.example .env`
* Generate and update .env with the app key
    * `php artisan key:generate`
* Create a database in your mysql installation
* Update `.env` with database credentials
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```
* Run database migration scripts
    * `php artisan migrate`
* Run database seed script to populate the users table
    * `php artisan db:seed --class=UsersTableSeeder`
* Add the encryption keys for passport
    * `php artisan passport:keys`
* Run passport client setup to create a new OAuth client and accept the default options
    * `php artisan passport:client --password`
* Update `.env` with the OAuth client, localhost and testing details
```
AUTH_CLIENT_ID=Client_ID_in_previous_step
AUTH_CLIENT_SECRET=Client_Secret_in_previous_step
AUTH_PROXY_BASE_URL=http://localhost/path/to/laravel

PASSWORD_RESET_BASE_URL=http://frontend.com/password/reset
TEST_API_URL=http://localhost/path/to/laravel
TEST_EMAIL=mail@example.com
```
* Check laravel installation is working by visiting it in a browser
* Update `.env` with smtp server details
    * Create an account on [mailtrap.io](https://mailtrap.io) if you do not have one
* Run phpunit tests to check the api is up and running correctly

## Installing Laravel

Check that your local environment satisfies the server requirements as detailed here - https://laravel.com/docs/5.5#server-requirements

There are a number of ways of installing laravel and I like to do it via composer.
`cd /path/to/project`
`composer create-project --prefer-dist laravel/laravel api`

As part of the composer installation process, it will automatically generate the app key and update your.env file. You should see something like this during the installation.
`APP_KEY=base64:1ztNIHCF1bcVXhVCBUFP1dH1oCX5h9CAp3pr7QcYTY8=`

Go to the laravel web root, which should be at http://localhost or wherever your set up as and you should see the laravel home page.

Check the `/storage/log` directory permissions are writable if laravel doesn’t work. The laravel documentation is really good so if you have problems with getting Laravel running follow the configuration steps, particularly the section on pretty urls to debug it.

## Setup and Configure Database
I have made the assumption that you have mysql installed on your system, alternatively you can use postgres as Laravel also supports it.

Create a new database and update your .env file with your database credentials.
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravelapi
DB_USERNAME=username
DB_PASSWORD=password
```

## Installing and Configuring Passport

Next we will need to install and configure passport that will allow users to authenticate with your API.

We will install passport using composer and use artisan to set up the database tables required by passport

`composer require laravel/passport`
`php artisan migrate`

Check that the database tables `users`, `password_resets` and the `oauth_*` tables were created.

We want to use the password grant so users can get authentication tokens by using a username/password. For this, we need to create a oauth password client.

`php artisan passport:client --password`

The default settings can be kept or edit them as if you need to. Keep a note of the Client Secret as we will need this later on.

Then we need to do the following:

* Update the User model `app/User.php` so it has the `hasApiTokens` trait
* Add the Passport routes to the `AuthServiceProvider` `app/AuthServiceProvider.php`
* Update `config/auth.php` so the Passport `TokenGuard` is used to authenticate the api requests, by updating the api `driver` option to `passport`.

These steps are outlined here https://laravel.com/docs/5.5/passport#installation

Finally, you will have to generate the keys required by passport to encrypt the tokens. The following command will generate a public and private key on `/storage`
`php artisan passport:keys`

## Database Seeding
Next we will seed the `users` table with users that we can test authentication with.

We will use `artisan` to create script a seed script to seed the users table.

`php artisan make:seeder UsersTableSeeder`

This will create a UsersTableSeeder class in `/app/database/seeds`. Modify this class and add your own test users.

`composer dump-autoload`

The above composer command needs to be run so the autoloader is regenerated and the new UsersTableSeeder class is loaded. Next, run the new users seeding script to add the entries to the users table.

`php artisan db:seed --class=UsersTableSeeder`

More information about writing seeders is here https://laravel.com/docs/5.5/seeding#writing-seeders

## Create Test for OAuth Token Endpoint

With passport setup and test users, we can now test authentication. We will write some tests and use phpunit to run these tests. I've made the assumption that PHPUnit will be set up on your system

Artisan can be used to create a skeleton test.

`php artisan make:test Api/OAuthTest`

This will create a skeleton phpunit test in `/tests/Feature/Api/OAuthTest.php`.

We can now update the test so it posts a request to the `/oauth/token` endpoint with the client_id, client_secret and user credentials that we have seeded the user table with and check that it returns a 200 response and the access and refresh tokens.

Something else we will do is to add the client_id and client_secret to the `.env` file so we do not have to hardcode it into the tests. This also comes in handy for the next step.


## Create a Proxy for User Login
During the installing and configuration of passport, we created a oauth client. This means that to authenticate with our API, the client must provide the client secret. This **must** be kept confidential and not be deployed to the client side where people can see it.

As a Single-Page Application or other client side front-end may be accessing this API and we want to keep the client secret confidential, we can create an api endpoint that will proxy into the oauth token endpoint. This article explains it bit better - http://esbenp.github.io/2017/03/19/modern-rest-api-laravel-part-4/#hide-the-client-credentials-in-a-proxy

So we do the following:

* Create a `/app/Http/Api/User/AuthController` which will have `login`, `refreshToken` and `logout` methods
    * `php artisan make:controller Api/User/AuthController` will create a base controller
    * The client_id and client_secret will be accessed by calling the env variables which we added to the `.env` file earlier
* Create a couple of classes which we use to proxy into the token endpoint
    * `/app/Lib/AuthProxy.php` this manages talking to the endpoint
    * `/app/Lib/AuthProxyClient.php` wrapper around [GuzzlePhp](https://github.com/guzzle/guzzle).
* Update `/app/routes/api.php` to  map `/user/login`, `/user/logout`, `/user/refreshToken` to the relevant `AuthController` methods.
* Create tests that will check the login, logout and token refreshing of our api
    * `php artisan make:test Api/LoginTest`
    * Creates a skeleton test in `/tests/Feature/LoginTest.php`

## Create a Register Endpoint
Next, we want our api to allow someone to register for the service. Laravel already has a bunch of code that handles registration and we want to reuse that. But we also want to have the ability to modify the registration actions if required.

* Create a RegisterController
    * Copy `/app/Http/Controllers/Auth/RegisterController.php` to `/app/Http/Controllers/Auth/RegisterController.php`.
    * This uses the `RegistersUsers` trait and we want to override the default functionality of `register()`.
    * If required, further customisation can be done such as adding extra fields to the registration process or user verification emails by updating this controller and the `app/User` class but we won't be doing this here.
* Update `/app/routes/api.php` to map `/user/register` to the `RegisterController` methods.
* Create tests that will check the registration process and validation process
    * `php artisan make:test Api/RegisterTest`
    * Creates a skeleton test in `/tests/Feature/RegisterTest.php`
    * I did some refactoring and added a `/tests/Lib/HttpClient` class that the tests use to make a HTTP requests to the api.

## Create a Reset Password and Password Update Endpoints
Next we create endpoints to allow users to reset their passwords by receiving an email with that contains a one-time use token to allow them to reset and update their passwords. Again Laravel does this out of the box, and we want to reuse this code as much as possible.

* Create ForgotPasswordController
    * Copy `/app/Http/Controllers/Auth/ForgotPasswordController.php` to `/app/Http/Controllers/Auth/ForgotPasswordController.php`.
    * This class uses the `SendsPasswordResetEmails` trait, and we override a couple of these methods as we need to modify the responses to what the api should return when sending a password reset email.
* Create a new Notification class which creates password reset email
    * `/app/Lib/Notifications/ApiResetPassword.php`
    * Modifiy `/app/User.php` to use this new Notification class
* Create ResetPasswordController
    * Copy `/app/Http/Controllers/Auth/ResetPasswordController.php` to `/app/Http/Controllers/Auth/ResetPasswordController.php`.
    * This class uses the `ResetsPasswords` trait, and we override the `reset()` method to modify the responses that the api should return when sending a resetting a user's password.
* For email testing, the `MAIL` settings in `.env` will need to be updated so the password reset emails can be tested.
    * If you do not have a smtp server available, the simplest way is to sign up for a mailtrap.io and set the `.env` variables to your account details for testing.
* Update `/app/routes/api.php` to map `/user/password/forgot`, `/user/password/reset` to the `ForgotPasswordController` and `ResetPasswordController` methods.
* Create tests that to check the password reset and update process.
    * `php artisan make:test Api/ForgotPasswordTest`
    * The response during a password reset will normally not return the password reset token. But for testing, it needs to be returned so we can test the full password reset process without processing emails. In our `ForgotPasswordController`, if the `APP_ENV` variable is set to `local` the API will return the reset token in the response, otherwise it will not.

## Create an Endpoint to Retrieve and Update User Details

We want an endpoint to retrieve and update a user's own details.

* Create AccountController
    * `php artisan make:controller Api/User/AccountController`
    * Add an `index()` method which will retrieve a users details
    * Add an `update()` method which will update a user's details
* Update `/app/routes/api.php` to map `/user/account` GET and POST requests to the `AccountController` methods.
* Create tests that to check the user retrieval and update process.
    * `php artisan make:test Api/UserAccountTest`
    * Update the skeleton phpunit class with the tests

## Set up CORS on API

To allow user-agents to use this api if they exist on different domains, we need to setup and configure CORS for out API. Fortunately, this is relatively simple to setup with this [CORS Middleware package for Laravel 5](https://github.com/barryvdh/laravel-cors).

Install it with composer and follow the readme.me for that package.

## Deploy the Code to Heroku
To be updated




