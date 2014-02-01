GitExpertBundle
================

The FOGGitExpertBundle adds commands and other stuff to ease the use of git on Symfony project.
This bundle is in DEVELOPMENT, so any comment, help or review is greatly welcome !

##Installation

### Step 1 : Require the project in your composer.json
```javascript
//composer.json
{
    "require: {
        "tagexpert/gitexpert" : "*"
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
1. If ```--reset``` option is set, drop database & recreate the database
2. If ```--reset``` option is set, load fixtures (DoctrineFixturesBundle) and demo content (FakerBundle)
3. Check if doctrine schema is valid
    * If the schema is not valid, abort
    * Else if the schema is valid but not synced with database, update the database 
    * Else do nothing
4. If Elastica is used, it populates the indexes
5. Clear the caches (via symfony command if ```--hard``` option is not set, via rm -rf otherwise)
6. Install the assets