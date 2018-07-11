# Database activator for flagception
Manage feature flags for [Flagception](https://packagist.org/packages/flagception/flagception) with a sql database (mysql, postgres, sqlite, ...)!

Download the library
---------------------------

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this library:

```console
$ composer require flagception/database-activator
```

Usage
---------------------------

Just create a new `DatabaseActivator` instance and commit it to your feature manager:

```
// YourClass.php

class YourClass
{
    public function run()
    {
        $activator = new DatabaseActivator(['url' => 'mysql://user:secret@localhost/mydb']);
        
        $manager = new FeatureManager($activator);
        if ($manager->isActive('your_feature_name')) {
            // do something
        }
    }
}
```

#### Connection

This activator use [dbal](https://packagist.org/packages/doctrine/dbal) under the hood. We redirect the first argument
directly to dbal - so you can use all known connection options (see [documentation](https://www.doctrine-project.org/projects/doctrine-dbal/en/2.7/reference/configuration.html)):

###### User and password

```
// YourClass.php

class YourClass
{
    public function run()
    {
        $activator = new DatabaseActivator([
            'dbname' => 'mydb',
            'user' => 'user',
            'password' => 'secret',
            'host' => 'localhost',
            'driver' => 'pdo_mysql'
        ]);
        
        // ...
    }
}
```

###### Connection string

```
// YourClass.php

class YourClass
{
    public function run()
    {
        $activator = new DatabaseActivator([
            'url' => 'mysql://user:secret@localhost/mydb'
        ]);
        
        // ...
    }
}
```

###### PDO instance

```
// YourClass.php

class YourClass
{
    public function run()
    {
        $activator = new DatabaseActivator([
            'pdo' => $this->myPdoInstance
        ]);
        
        // ...
    }
}
```

###### DBAL instance

```
// YourClass.php

class YourClass
{
    public function run()
    {
        $activator = new DatabaseActivator($this->myDbalInstance);
        
        // ...
    }
}
```

#### Table

The activator will create the sql table if it does not already exist. The default table name is `flagception_features` which contains
a `feature` and a `state` column. You can change the table and columns names by the second argument. It expects
an array with values for `db_table`, `db_column_feature` and `db_column_state`. Setting one of the fields in optional.

Example:

```
// YourClass.php

class YourClass
{
    public function run()
    {
        $activator = new DatabaseActivator(['url' => 'mysql://user:secret@localhost/mydb'], [
            'db_table' => 'my_feature_table',
            'db_column_feature' => 'foo_feature_name',
            'db_column_state' => 'foo_is_active'
        ]);
        
        // The activator create a table 'my_feature_table' with the column 'foo_feature_name' and 'foo_is_active'.
        
        $manager = new FeatureManager($activator);
        if ($manager->isActive('your_feature_name')) {
            // do something
        }
    }
}
```