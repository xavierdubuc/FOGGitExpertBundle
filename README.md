GitExpertBundle
================

The FOGGitExpertBundle adds commands and other stuff to ease the use of git on Symfony project.
This bundle is in DEVELOPMENT, so any comment, help or review is greatly welcome !

##Installation

### Step 1 : Require the project in your composer.json
```javascript
//composer.json
{
    "require": {
        "friendsofgit/gitexpert-bundle" : "*"
    }
}
...
```

### Step 2 : Enable the bundle

```php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new FOG\GitExpertBundle\FOGGitExpertBundle(),
    );
}
```
## Usage

When you work in a team on a Symfony project, when you pull some modifications
made by an other member of the team, you often have to run some commands to
continue your work. For example, in case of database modification, you have to
run ```doctrine:update:schema``` in order to update your database with the changes
done by your team mate. Another example is when you deploy your project on a remote
server and you need to later update the remote server via a git pull. After this,
while the server is in prod mode, you have to clear the cache and maybe install
the assets.

The ```git:after:pull``` command allow to do all the needed verification and
run all the needed commands in order to let you continue your work. It accepts
two options : 

* ```--hard``` Hard clear the caches, in other words, it rm -rf all the cache/prod
and cache/dev directories
* ```--reset``` Reset the database

The command workflow is :
* If ```--reset``` option is set, drop database & recreate the database
* If ```--reset``` option is set, load fixtures (<a href="https://github.com/doctrine/DoctrineFixturesBundle" target="_blank">DoctrineFixturesBundle</a>)
  and demo content (<a href="https://github.com/willdurand/BazingaFakerBundle" target="_blank">BazingaFakerBundle</a>)
* Check if doctrine schema is valid
    - If the schema is not valid, abort
    - Else if the schema is valid but not synced with database, update the database 
    - Else do nothing
* If <a href="https://github.com/FriendsOfSymfony/FOSElasticaBundle" target="_blank">FOSElasticaBundle</a> is installed, it populates the indexes
* Clear the caches (via symfony command if ```--hard``` option is not set, via rm -rf otherwise)
* Install the assets <a href="http://www.xavierdubuc.com" target="_blank">My site</a>