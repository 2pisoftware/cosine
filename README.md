# Cosine

Welcome! Cosine is a framework designed for fast ERP and CRM business software solutions. This project is a modular platform that allows you to build custom solutions for your business through the use of modules.

The full documentation is located at [cmfive.com](https://cmfive.com).

## Installing

See [Deploying](#deploying).

## Development

### Quick Start

Follow these steps to get up and running with Cosine development.

Cosine development requires Docker and Docker Compose. If you don't have these installed you can download them or follow the instructions from the [Docker website](https://docs.docker.com/get-docker/).

First clone this repository and navigate to the directory in a terminal.

Then run the following command to start the development environment:

```sh
docker compose up -d --wait --pull=always
```

This will start the development environment and run it in the background. Once it's running, you can access the Cosine installation at [http://localhost:3000](http://localhost:3000). 

The development username is `admin` and the password is `admin`.

The files in this repository will now be mounted to **/var/www/html** in the container. You can make changes to the files in the repository and they will be reflected in the container.

To stop the development environment, run:

```sh
# to stop the containers
docker compose down

# or to stop and remove all volumes
docker compose down -v
```

### Contributing

For further information on developing and contributing to Cosine please refer to [CONTRIBUTING.md](CONTRIBUTING.md). 

### Accessing the Cosine installation tools menu

The installation tools menu contains maintenance, setup and testing tools for Cosine. You can access it with the following command:

```sh
docker compose exec -it -u cmfive cosine tools
```

Here is some detail on the menu options provided:

- **Install Core Libraries**: This will install any third party libraries Cosine requires via Composer
- **Install Database Migrations**: This will install all Cosine database migrations
- **Seed Admin User**: Sets up an administrator user which is needed to log in to a production Cosine install
- **Generate Encryption Keys**: Generate new encryption keys used by Cosine for secure Database fields
- **Tests**: Runs a chosen test in the Cosine test suite

### Theme development

The theme is located in this directory:

```sh
cd system/templates/base
```

As part of the development environment there is a container which compiles the theme as you change it. To view the output of the theme development container run the following command:

```sh
docker compose logs -f compiler
```

### Accessing the Cosine container shell

You can access the Cosine container shell with the following command:

```sh
# for standard web user access
docker compose exec -it -u cmfive cosine sh
# or for root access
docker compose exec -it -u root cosine sh
```

### Debugging and testing

Ensure you have installed the dev tools first. You can do this by running the following command:

```sh
./.codepipeline/docker/install_dev_tools.sh
```

#### Xdebug

Once you have the dev tools installed you can start debugging in VS Code by running the `Listen for Xdebug` configuration. This will start the debugger and you can set breakpoints in your code.
-rf .codepipeline                                                                                              0.0s
 => CACHED [17/23] RUN ln 
#### Playwright

To set up and test with playwright, follow the instructions in the [Playwright README](test/playwright/README.md).

#### PHPUnit

To run the PHPUnit tests, you can run the following command:

```sh
docker compose exec -u cmfive cosine tools tests unit all
```

### Mailpit (Email Testing)

The development stack includes [Mailpit](https://github.com/axllent/mailpit) for capturing outgoing emails. You can use it to view emails sent by Cosine, such as password resets.

- **Access Mailpit UI:** Open [http://localhost:8025](http://localhost:8025) in your browser.
- **Test password reset:** 
  1. Go to the Cosine login page at [http://localhost:3000](http://localhost:3000).
  2. Click "Forgot password?" and enter the username `admin`.
  3. Check Mailpit for the password reset email and follow the instructions in the email.

## Deploying

### Docker

A docker image for Cosine is available on [GitHub Container Registry](https://github.com/orgs/2pisoftware/packages/container/package/cosine). 

You will need to run a mysql or compatible container and link it to the Cosine container. See more information about the mysql container on the [Docker Hub page](https://hub.docker.com/_/mysql).

Here is an example of how to run a Cosine container with docker:

```sh
# Define the configuration details
export DB_DATABASE=cosine
export DB_USERNAME=cosine
export DB_PASSWORD=cosine
export DB_ROOT_PW=root
export COSINE_IMAGE=ghcr.io/2pisoftware/cosine:latest

# Create a network
docker network create cosine

# Run the mysql container
docker run --name mysql-8 -d -p 3306:3306 \
    -e MYSQL_ROOT_PASSWORD=$DB_ROOT_PW \
    -e MYSQL_DATABASE=$DB_DATABASE \
    -e MYSQL_USER=$DB_USERNAME \
    -e MYSQL_PASSWORD=$DB_PASSWORD \
    --network=cosine \
    mysql:8.0

# Create some directories for data persistence
mkdir -p cosine/storage cosine/uploads cosine/backups

# Run the cosine container
docker run --name cosine -d -p 3000:80 \
    -v ./cosine/storage:/var/www/html/storage \
    -v ./cosine/uploads:/var/www/html/uploads \
    -v ./cosine/backups:/var/www/html/backups \
    -e DB_HOST=mysql-8 \
    -e DB_USERNAME=$DB_USERNAME \
    -e DB_PASSWORD=$DB_PASSWORD \
    -e DB_DATABASE=$DB_DATABASE \
    -e ENVIRONMENT=production \
    --network=cosine \
    $COSINE_IMAGE
```

You can then proceed to set up an admin user with:

```sh
docker exec -it -u cmfive cosine tools seed admin
```

You can access the cosine installation at [http://localhost:3000](http://localhost:3000).

The following options can be used with the Docker image. You may choose to use for example vanilla docker, docker-compose or Kubernetes. Please consult the documentation for these tools for more information on how to use the options below.

#### Environment variables

Cosine container environment variables:

- **DB_HOST:** The hostname of the MySQL database server
- **DB_DATABASE:** The name of the database
- **DB_USERNAME:** The username to connect to the database
- **DB_PASSWORD:** The password to connect to the database
- **CUSTOM_COFIG:** (optional) Custom configuration to add to the config.php file.
- **ENVIRONMENT:** (optional) The environment to run in (development, production). Defaults to production.
- **REDIRECT_HTTP_TO_HTTPS:** (optional) If set to `true`, configures Nginx to redirect HTTP traffic to HTTPS.
- **REDIRECT_HOST:** (optional) The hostname to use for the HTTPS redirect. Defaults to the same hostname as the request.

Development environment variables (stored in .env):

- **COSINE_IMAGE**: Custom cosine base image to use
- **COMPILER_IMAGE**: Custom compiler image to use
- **MYSQL_IMAGE**: Custom mysql image to use

#### Build args

The following build args are optional and can be used to customise the Docker image if you are building a custom one:

- **PHP_VERSION:** The version of PHP to use. See alpine linux packages for available versions. Defaults to the version in the Dockerfile (eg 81).
- **UID:** The user ID to use for the cmfive user. Defaults to 1000.
- **GID:** The group ID to use for the cmfive user. Defaults to 1000.

#### Volumes

**Data persistence**:

Here are the directories of the container that should be mounted to volumes if you want to persist data:

- **/var/www/html/storage**: Sessions and logs
- **/var/www/html/uploads**: Uploaded files
- **/var/www/html/backups**: Database backups

**HTTPS**:

A self-signed SSL/TLS certificate is included in the image. If you require a certificate for your domain you can mount your key and certificate files to the following paths:

- **/etc/nginx/nginx.key** - The SSL/TLS key
- **/etc/nginx/nginx.crt** - The SSL/TLS certificate

**Modules**

If you have custom modules you can mount them to the following directory:

- **/var/www/html/modules/name-of-module**

**PHP Configuration**

If you need to customise the PHP configuration you can mount a file to the path `/etc/php/conf.d/` for example:

- **/etc/php/conf.d/99-custom.ini**

If you want to configure PHP-FPM entirely, you can override:

- **/etc/php/php-fpm.conf**, and/or
- **/etc/php/php-fpm.d/www.conf**

**Nginx Configuration**

If you need to customise the Nginx configuration you can mount a file to the path `/etc/nginx/conf.d/` for example:

- **/etc/nginx/conf.d/99-custom.conf**

If you want to customise Nginx entirely, you can override:

- **/etc/nginx/nginx.conf**, and/or
- **/etc/nginx/conf.f/default.conf**

#### Ports

The following ports are exposed by the container, you can map them to different ports on the host:

- **80** - HTTP
- **443** - HTTPS

### Manual setup

Here are the steps to set up Cosine without Docker. Please note that your environment may differ and you may need to adjust these steps accordingly.

Install the following software

- PHP
- MySQL
- Nginx
- NodeJS

Clone the repository.

Set up a cmfive database and user on MySQL. Consult the MySQL documentation for more information.

Copy config.php.example to config.php and update the database details.

Run `php cmfive.php` and:

- Install Core Libraries
- Install Database Migrations
- Seed Admin User
- Generate Encryption Keys

Navigate to the theme directory (system/templates/base) and run `npm install`.

After that, you can build the production theme with `npm run production`.

## Developing Modules

#### The modules
A typical module layout will look like this (bold entries are required):
* **actions**
* assets
* **install**
 * **migrations**
* **models**
* **templates**
* **config.php**

##### Actions
An action is a function that is executed from web(.php) as a result of matching the current URL against the modular structure of cmfive. The path of a URL consists of:
```
[HEAD|GET|POST] https://localhost/<module>/<action>
or
[HEAD|GET|POST] https://localhost/<module>-<submodule>/<action>
```

A submodule is just another folder inside the actions folder and serves primarily as a way to organise multiple actions in a module.

The function in the action follows the following naming convention:
```php
<?php

function <action name>_<verb>(Web $w) { // Where "verb" is either HEAD, GET or POST

}
```
e.g:
```php
<?php

function listsongs_GET(Web $W){
	$songs = $w->Music->getSongs();
	
	// Do something with songs
	
	$w->ctx('song_list', $songs); // ctx() exposes the $songs to the template now as the variable "$song_list"
}
```

#### Assets

A place to keep static assets, can be called anything that suits you.

#### Install

The install folder can be used to keep report code and templates, but its main purpose is to house the migrations and database seeds. Inside the install folder needs to be a folder called "migrations". To create a new migration, goto admin -> migrations -> Individual (tab). Go to your module in the list and click "Create a new migration". Enter a name for the migration and click save. See migrations in the system/modules folder for easy ways to create migrations (documentation coming soon).

#### Models

The models folder is used to store Cmfives database ORM objects called "DbObject"(s). A DbObject class name should relate to it's matching table name, but without underscores, and camel cased. E.g:

| Table | DbObject |
|-------|----------|
|user   | User     |
|task_group_member|TaskGroupMember|

This conversion is done automatically by Cmfive, but you can override this by setting by adding the static propery "$\_db_table" to your DbObject and setting it to the name of the responsible table.

The models folder can also store Service classes, called "DbService". These classes provide a global interface to your module via the Web class. Generally, each module has at least one, named after the module itself, e.g. if your module was called "music":
```php
<?php

class MusicService extends DbService { // The "Service" suffix is required
	public function getSong($id) {
		return $this->getObject("Song", $id);
	}
	
	public function getSongs() {
		return $this->getObjects("Song", ['is_deleted' => 0]);
	}
}
```

Everything in the models folder gets autoloaded, so all you need to do to invoke this function from anywhere (via an instance of Web), is to call:
```php
$my_song = $w->Music->getSong($the_id);
```

Any other classes that you want autoloaded, like generic interfaces, static helper classes etc., can be put in the models folder.

#### Templates

Templates act as a compliment to an action. For Cmfive to match a template to an action, it should have the same submodule layout as its action counterpart and follow this naming convention:
```
<action name>.tpl.php
```
e.g.
```html
<!-- /music/templates/listsongs.tpl.php -->
<ul>
	<?php foreach($song_list as $song): ?>
		<li><?php echo $song; ?></li>
	<?php endforeach; ?>
</ul>
```

#### config.php

The most cruical part to a module, Cmfive first looks for a config file to load the module, if this is missing then your module won't be used at all by Cmfive. The config.php file uses a static class called Config which is essentially a key value store. A module config requires three values, here is a full example to explain each one:
```php
<?php

Config::set('music', [
	'active' 	=> true,		// the active flag lets us disable modules that we don't want to use
	'path'		=> 'modules',	// tells Cmfive exactly where this module can be found (Config values are cached)
	'topmenu'	=> 'My music'	// Set to false to not show in the top menu, or set to true to infer the menu name from the name of the module (in this case "Music")
]);
```

