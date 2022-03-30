<p align="center">
    <a href="https://www.yiiframework.com/" target="_blank">
        <img src="https://www.yiiframework.com/files/logo/yii.png" width="400" alt="Yii Framework Website" />
    </a>
</p>

This project contains the source code for the [yiiframework.com](https://yiiframework.com/) Website.

If you want to contribute please get in touch with us using the [issue tracker](https://github.com/yiisoft-contrib/yiiframework.com/issues).

![Build Status](https://github.com/yiisoft-contrib/yiiframework.com/actions/workflows/build.yml/badge.svg)

## Prerequisites

Install [Docker](https://docs.docker.com/get-docker/) and [Docker Compose](https://docs.docker.com/compose/install/).

```
cp docker-compose.override.example.yml docker-compose.override.yml
```

Fill with actual values.

```
docker-compose build
docker-compose up
```

The site will be available at http://0.0.0.0:8080.

### Generating screenshots

You can use the script `run_pageres.sh` at the root of the source directory to generate screenshots.

## Initial setup

Adjust `config/params-local.php`. Make sure to configure the following properties:

```php
'components.db' => [
    // copy from params.php and adjust to your environment
],
// only if you want to test github auth:
'authclients' => [
    'github' => [
        'class' => 'yii\authclient\clients\GitHub',
        'clientId' => '...', // register an oauth app on github and enter details here
        'clientSecret' => '...',
        'scope' => 'user:email',
    ],
],
'siteAbsoluteUrl' => 'http://yiiframework.local',

// https://apps.twitter.com/app/new
// After creating an app you need to fill accessToken and accessTokenSecret:
// Open App -> Keys and Access Tokens -> You Access Token -> Create my access token
'twitter.consumerKey' => '',
'twitter.consumerSecret' => '',
'twitter.accessToken' => '',
'twitter.accessTokenSecret' => '',
```

Generate a personal Github token (from your Github profile settings section). Paste it in a file in the `data` directory 
(@app/data) called `github.token` (one line, no line-break).

Continue with the following commands:

```sh
# run migrations
./yii migrate

# fill RBAC
./yii rbac/up

# build contributors page (this may take some time as it downloads a lot of user avatars from github)
./yii contributors/generate

# If you're on Windows you have to manually symlink or copy
# %appdata%/npm/node_modules/browser-sync to your app's node_modules

# The next step is for building the API documentation and the Guide files.
# It is optional for the site to be working but you will have no API docs and Guide.
# This step includes cloning the Yii 1 and Yii 2 repositories and a lot of computation,
# so you might want to skip it on the first install.
#
# This also requires an instance of elasticsearch to be configured and running
# (if you do not have it, it will still run, but the site search will not work).
# It also assumes you have pdflatex installed for building PDF guide docs.
#
# You may also build only parts of the docs, run  make help  for the available commands.
make docs

# If you are using Docker image, you need to additionally pass VENDOR_DIR:
make docs VENDOR_DIR=$VENDOR_DIR

# Yii 1.0 API docs generation. They are already included in VCS. Run this only if layout has changed.
docker build -f Dockerfile.yii-1.0 -t yiiframeworkcom-yii-1.0 .
docker run -it -v $PWD/data/api-1.0:/code/data/api-1.0 yiiframeworkcom-yii-1.0

# populate the search index by running
./yii search/rebuild
```

### Data import

For importing data from the old website, the following steps are necessary:

- import data by running `./yii import` command
- rebuild user badges by running `./yii badge/rebuild` 
- calculate user ranking `./yii user/ranking`.

If you don't have that data, you can work with dummy content:

- To fill the database with dummy content, you may run the command `./yii fake-data`.
  You may run it multiple times to generate more data.
- rebuild user badges by running `./yii badge/rebuild`.
- calculate user ranking `./yii user/ranking`.

To assign users extra permissions use `./yii rbac/assign`.

### Cron jobs

The following commands need to be set up to run on a regular basis:

| command                   | interval | Purpose
| --------------------      | -------- | --
| yii sitemap/generate      | daily    | regenerate sitemap.xml
| yii contributors/generate | weekly   | update contributors list on team page
| yii badge/update          | hourly   | update badges for users in badge_queue
| yii cron/update-packagist | hourly   | update packagist extension data
| yii user/ranking          | daily    | update user ranking
| yii github-progress       | hourly   | update Github progress data

Additionally, `queue/listen` should run as a daemon or `queue/run` as a cronjob.

### Deployment

This section covers notes for deployment on a server, you may not need this for your dev env. OS is assumed to be Debian 
"bullseye".

```sh
apt-get install texlive-full python3-pygments git nodejs make
```

## Maintenance

The contributors list and the avatar thumbnails is generated by a console command:

```sh
./yii contributors/generate
```

It will connect to Github via the API and fetch a list of contributors, generate `data/contributors.json` and thumbnail images of the user avatars in `data/avatars` and finally invoke Gulp to generate a sprite image and Sass code.

It would be a good idea to set up a Cron job to run that once in a while - perhaps once each month.

## Directory structure

      commands/           contains console commands (controllers)
      config/             contains application configurations
      controllers/        contains Web controller classes
      data/               contains important data generated by different commands
      env/                contains environment-dependent files
      assets/
          src/
              fonts/      contains fonts
              scss/       contains Sass source files
              js/         contains JS source files
      mail/               contains view files for e-mails
      models/             contains model classes
      node_modules/       contains installed NPM packages
      runtime/            contains files generated during runtime
      scripts/            contains shell scripts
      vendor/             contains dependent 3rd-party packages
      views/              contains view files for the Web application
      web/                contains the entry script and Web resources


## Development

### Build

* During development, run `gulp` to watch view, Sass and JS file changes and automatically build target CSS/JS files. This command will also launch a browser window which is connected to browsersync.
* At any time, run `gulp build` to manually rebuild target CSS/JS files from source Sass/JS files.
* If you only want to watch for changes, you can issue the command `gulp watch`
* To build the assets for production, specify the `production` flag: `gulp build --production` or run `npm run build`

### CSS Files

* Use Sass files to define CSS styles.
* All Sass files should be put under `assets/src/scss` and listed in `assets/src/scss/all.scss`.
* Usually each controller corresponds to a single Sass file whose name is the same as the controller ID.
  For example, the `GuideController` has a Sass file named `_guide.scss`.
* All Sass source files, except `all.scss` should have a leading underscore in the name. Sass will ignore files starting with an underscore so that only one CSS file will be produced (all.css).
* For information about where each file should be put, please consult the master include file `all.scss`.

### JS Files

* All JS files should be put under `assets/src/js` and listed in `config.yml`.
* Usually each controller corresponds to a single JS file whose name is the same as the controller ID.
  For example, the `GuideController` has a JS file named `guide.js`.

## Links

* [Gulp](https://gulpjs.com/)
* [Browsersync](https://www.browsersync.io/)
* [Sass](https://sass-lang.com/)