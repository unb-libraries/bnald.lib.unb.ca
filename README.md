# bnald.lib.unb.ca
## Lean Instance Repository

## Build Status
| Branch | Status |
|--------|--------|
| dev | [![Build Status](https://travis-ci.org/unb-libraries/bnald.lib.unb.ca.svg?branch=dev)](https://travis-ci.org/unb-libraries/bnald.lib.unb.ca) |
| prod | [![Build Status](https://travis-ci.org/unb-libraries/bnald.lib.unb.ca.svg?branch=prod)](https://travis-ci.org/unb-libraries/bnald.lib.unb.ca) |

### Requirements
The following packages are required to be globally installed on your development instance:

* [PHP7](https://php.org/) - Install instructions [are here for OSX](https://gist.github.com/JacobSanford/52ad35b83bcde5c113072d5591eb89bd).
* [Composer](https://getcomposer.org/)
* [docker](https://www.docker.com)/[docker-compose](https://docs.docker.com/compose/) - An installation HowTo for OSX and Linux [is located here, in section 2.](https://github.com/unb-libraries/docker-drupal/wiki/2.-Setting-Up-Prerequisites).
* [dockworker](https://gist.github.com/JacobSanford/1448fece856be371060d0f16ccb1b194) - Install the dockworker alias.

### 1. Initial Setup
#### A. Configure Local Development
In the ```env/drupal.env``` file, change the environment settings to match your local development environment.

```
DOCKER_ENDPOINT_IP=localhost
LOCAL_USER_GROUP=20
```

* ```DOCKER_ENDPOINT_IP``` - This is the IP of your docker daemon, likely 'localhost'.
* ```LOCAL_USER_GROUP``` - The [group id](https://kb.iu.edu/d/adwf) of your local user. This is used to change permissions when deploying locally.

### 2. Deploy Instance
```
composer install --prefer-dist
```

If you are intending to migrate legacy data you may jump to 3.1 and provide the data source for the migration. Come back here before (re-)starting the 
container (the last step of 3.1).

Continue with the deploy process.
```
dockworker start-over
```

### 3. Prepare BNALD

SSH into your Drupal container and change into the Drupal root directory
```
dockworker shell
cd html/
```
The ```bnald_core``` module provides the ```Legislation``` and ```SourceDocument``` custom entities, as well as views, taxonomy vocabularies, a search index,
 and other config related to BNALD. If it is not already installed, run:
```
drush en --yes bnald_core
```

#### 3.1 Provide a Migration Data Source
If you don't need to migrate any legacy data, you may skip this step and start using the instance.

On the legacy DB server, dump the database:
```
mysqldump -u root -p --add-drop-database --add-drop-table --databases bnald > ~/bnald/bnald_d7.sql
```
Copy the SQL file into the ```content/mysqldump-migrate``` folder of your BNALD working tree.
```
scp <LEGACY_SERVER>:<LEGACY_FOLDER>/bnald_d7.sql ~/<YOUR_BNALD_REPOSITORY_DIR>/content/mysqldump-migrate/bnald_d7.sql
```
Restart the container.
```
docker-compose restart bnaldlibunbca_mysqlimport_1
```

#### 3.2 Migrate Data
Install the ```bnald_migrate``` module.
```
drush en --yes bnald_migrate
```
Migrate the data. This will take a while.
```
drush mim --group=bnald
```
Please note: Users will only be imported if their status was set to ```active``` inside the legacy data source. If possible, accounts will be associated with
 an LDAP entry. Pages will only be imported with their title, the body content will need to be edited manually. Menus will have to be created manually, too.


### 4. Other useful commands
* Run ```dockworker``` to get a list of available commands.
* Run ```drush ms --group=bnald``` inside the Drupal container to get a list of BNALD migrations.

### 5. Known Issues
When uninstalling ```bnald_core```, not all configuration provided by the module will also be uninstalled, which results into an error after re-installing 
```bnald_core```.
The following sequence of commands will uninstall all config ```bnald_core``` provides.
```
drush cdel pathauto.pattern.concepts
drush cdel pathauto.pattern.jurisdictional_relevance
drush cdel pathauto.pattern.legislation
drush cdel pathauto.pattern.print_locations
drush cdel pathauto.pattern.printers
drush cdel pathauto.pattern.provinces
drush cdel pathauto.pattern.source_document
drush cdel search_api.index.legislation_bnald_lib_unb_ca
drush cdel search_api.server.drupal_solr_lib_unb_ca
drush cdel taxonomy.vocabulary.concepts
drush cdel taxonomy.vocabulary.jurisdictional_relevance
drush cdel taxonomy.vocabulary.print_locations
drush cdel taxonomy.vocabulary.printers
drush cdel taxonomy.vocabulary.provinces
drush cdel tvi.taxonomy_vocabulary.concepts
drush cdel tvi.taxonomy_vocabulary.jurisdictional_relevance
drush cdel tvi.taxonomy_vocabulary.print_locations
drush cdel tvi.taxonomy_vocabulary.printers
drush cdel tvi.taxonomy_vocabulary.provinces
drush cdel user.role.bnald_editor
drush cdel views.view.concepts
drush cdel views.view.jurisdictional_relevance
drush cdel views.view.legislation_search
drush cdel views.view.print_locations
drush cdel views.view.printers
drush cdel views.view.provinces
drush cdel views.view.recently_added
```

## Repository Branches
* `dev` - Core development branch. Deployed to dev when pushed.
* `prod` - Deployed to prod when pushed.
