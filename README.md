# Shiza

Shiza is a server management tool for a web office.
It's still under development.

You can use it to:
- manage web environments and databases for your projects
- create and restore backups of your projects
- manage server access through SSH keys and SSH key chains
- keep track of your repositories
- perform repository build tasks on commits
- deploy repositories on commits

## Installation

Create the following _composer.json_ file:

```json
{
    "minimum-stability": "dev",
    "require": {
        "ride/setup-base": "*",
        "ride/app-queue-orm": "^0.1.1",
        "ride/cli-database": "*",
        "ride/web-security-orm": "dev-master",
        "smarty/smarty": "dev-master#d3e26fb679081bc5f4427d86cb8d4275e835e094",
        "kayalion/shiza": "*"
    },
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/kayalion/shiza"
        }
    ],
    "autoload": {
        "psr-0": { "": "application/src" }
    }
}
```

Install this file through with the command:

```
composer up --prefer-stable
```

Once installed, you have to initialize your database and tune Ride.

### Setup your database

Before we can do anything, we have to setup our database.

Run the following commands to register and initialize your database and needed classes:

```
# register your database with your own credentials
php application/cli.php database add shiza mysql://<username>:<password>@<host>/<database>
# optional, if the database does not exist, this might work if access rights are sufficient
php application/cli.php database create shiza 
# create tables in your database
php application/cli.php orm define
# generate classes for the data model
php application/cli.php orm generate
```

### Secure your installation

We're dealing with sensitive information so it's best to keep everything away from unwanted visitors.
Use HTTPS when possible.

Let's secure the installation and create a user for ourselves:

```
# secure non public paths
php application/cli.php path secure /admin**
php application/cli.php path secure /repositories**
php application/cli.php path secure /projects**
php application/cli.php path secure /activity**
php application/cli.php path secure /servers**
php application/cli.php path secure /ssh-keys**
# create and activate a super user for ourselves
php application/cli.php user add <username> <password> <email>
php application cli.php user edit <username> confirm 1
php application cli.php user edit <username> active 1
php application cli.php user edit <username> super 1
```

### Optimize Ride

Let's optimize Ride into a production environment to increase speed.

Edit the file _application/config/parameters.php_:

Uncomment the following section and replace _/path/to/production_ with the main directory of your installation:

```php
// detect environment based on path
switch (__DIR__) {
    case "/real/path/to/production":
        $environment = "prod";
        $willCacheConfig = true;

        break;
}
```

Let's optimize the autoloader through Composer, so disable the one from Ride:

```php
$parameters = array(
    "autoloader" => array(
        "enable" => false,
        "include_path" => false,
        "prepend" => false,
    ),
```

Save and exit the editor, then run the following command to optimize the autoloader from Composer:

```
composer dump-autoload --optimize
```

Next, we enable and warm up the caches:

```
php application/cli.php cache enable
php application/cli.php cache warm
```

And finish with deploying all assets to the public directory:

```
php application/cli.php assets deploy
```

### Setup queue worker

Shiza uses a queue to handle the tasks.
It's a server process which will do all the heavy lifting, and we need to set it up off course.

Copy the _worker.sh_ script from the _ride/cli-queue_ module:

```
cp vendor/ride/cli-queue/src/worker.sh application
```

Edit it and set the _$DIRECTORY_ variable to your main directory of your installation.
While your at it, you can tune the sleep time to your needs.

By now, You have seen there is a log file of your queue process in _application/data/log/worker.log_.

To make sure the queue worker is always running, you can create a Cron entry for it:

```
@reboot /real/path/to/production/application/worker.sh
```

To edit your crontab, run the following command:

```
crontab -e
```

This will start your worker when your server reboots.
For now, you can start it by running:

```
/real/path/to/production/application/worker.sh &
```

With an ampersand to throw it to the background.

### Setup Cron jobs

For the full feature set of this application, you need to setup a couple of Cron entries:

```
# create backups every four hours
0 */4 * * * php /real/path/to/production/application/cli.php server backup queue
# rotate the backups every day
0 3 * * * php /real/path/to/production/application/cli.php server backup rotate
# calculate disk usages of web environments and databases
0 6 * * * php /real/path/to/production/application/cli.php server calculate disk usage
# auto renew SSL certificates
0 5 * * * php /real/path/to/production/application/cli.php server renew certificates
# remove old sessions (optional)
0 3 * * * php /real/path/to/production/application/cli.php session clear
```

### Setup Web Skeleton

By default, a _index.php_ and a _robots.txt_ file are written to a newly created web environment. 
You can set your own source directory which can act as a skeleton for a new web environment.

Run the following commands to create and set the skeleton directory to your application directory:

```
php application/cli.php parameter set system.directory.skeleton %application%/data/skeleton
mkdir -p application/data/skeleton
```

All files you create in this directory are now copied to a newly created web environment.

## Appendix: Installation Web server Debian 8

The following commands will setup a basic web server with multiple versions of PHP.
All commands are assumed to be executed as _root_.

### Install the prequisites

```
apt-get install build-essential git apache2 libapache2-mod-fcgid apache2-suexec-custom
apt-get build-dep php5
```

### Setup PHP farm

```
git clone https://github.com/cweiske/phpfarm.git /opt/phpfarm
ln -s /usr/lib/libc-client.a /usr/lib/x86_64-linux-gnu/libc-client.a
```

### Compile a PHP version

Go into the source directory of PHP farm:
```
cd /opt/phpfarm/src
```

The first time, you should create the file _custom-options.sh_ file to define your compile options.
This file can be used for all versions you compile.

```
nano custom-options.sh
```

You can use the following and tweak to your needs:

```sh
configoptions="\
--enable-bcmatch \
--enable-cli \
--enable-calendar \
--enable-exif \
--enable-ftp \
--enable-mbstring \
--enable-pcntl \
--enable-soap \
--enable-sockets \
--enable-sqlite-utf8 \
--enable-wddx \
--enable-zip \
--enable-gd-native-ttf \
--enable-sysvsem \
--enable-sysvshm \
--enable-sysvmsg \
--enable-fpm \
--enable-intl \
--with-curl \
--with-mcrypt \
--with-openssl \
--with-gd \
--with-gettext \
--with-imap \
--with-imap-ssl \
--with-ldap \
--with-ldap-sasl \
--with-kerberos \
--with-mime-magic \
--with-pdo-mysql \
--with-pear \
--with-iconv \
--with-jpeg-dir=/usr \
--with-zlib \
--with-zlib-dir \
--with-png-dir=/usr \
--with-mhash \
--with-libdir=/lib/x86_64-linux-gnu \
--with-fpm-user=www-data \
--with-frm-group=www-data \
"
```

Now you can compile the PHP version you want by running the following command:

```
./compile.sh <php-version>
```

#### Compile a PECL module (Imagick)

The first time you want to compile Imagick, you need to install the library:

```
apt-get install libmagickwand-dev
```

Go into the _pecl/all_ directory, first time create it:

```
cd /opt/phpfarm/src
mkdir -p pecl/all
cd pecl/all
```

Download and extract the latest version

```
wget http://pecl.php.net/get/imagick
tar vxzf imagick
cd imagick-<version>
```

Configure, compile and install the module for your PHP version:

```
/opt/phpfarm/inst/bin/phpize-<php-version>
./configure --with-php-config=/opt/phpfarm/inst/bin/php-config-<php-version>
make
make install
make clean
```

### Setup Apache

Enable the needed modules by running:

```
a2enmod actions
a2enmod include
a2enmod rewrite
a2enmod suexec
```

Configure Suexec so the server will host files as each environment's user:

Edit or create a _/etc/apache2/suexec/www-data_ file and place the following in it:

```
/home
public/cgi-bin
```

You can now add this server in the _Servers_ tab of your Shiza application. 
