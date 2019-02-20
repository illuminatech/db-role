<p align="center">
    <a href="https://github.com/illuminatech" target="_blank">
        <img src="https://avatars1.githubusercontent.com/u/47185924" height="100px">
    </a>
    <h1 align="center">Eloquent Role Inheritance Extension</h1>
    <br>
</p>

This extension provides support for Eloquent relation role (table inheritance) composition.

For license information check the [LICENSE](LICENSE.md)-file.

[![Latest Stable Version](https://poser.pugx.org/illuminatech/db-role/v/stable.png)](https://packagist.org/packages/illuminatech/db-role)
[![Total Downloads](https://poser.pugx.org/illuminatech/db-role/downloads.png)](https://packagist.org/packages/illuminatech/db-role)
[![Build Status](https://travis-ci.org/illuminatech/db-role.svg?branch=master)](https://travis-ci.org/illuminatech/db-role)


Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist illuminatech/db-role
```

or add

```json
"illuminatech/db-role": "*"
```

to the require section of your composer.json.


Usage
-----

This extension provides support for Eloquent relation role composition, which is also known as table inheritance.

For example: assume we have a database for the University. There are students studying in the University, and there are
instructors teaching the students. Student has a study group and scholarship information, while instructor has a rank
and salary. However, both student and instructor have name, address, phone number and so on. Thus we can split
their data in the three different tables:

 - 'humans' - stores common data
 - 'students' - stores student special data and reference to the 'humans' record
 - 'instructors' - stores instructor special data and reference to the 'humans' record

DDL for such solution may look like following:

```sql
CREATE TABLE `humans`
(
   `id` integer NOT NULL AUTO_INCREMENT,
   `role` varchar(20) NOT NULL,
   `name` varchar(64) NOT NULL,
   `address` varchar(64) NOT NULL,
   `phone` varchar(20) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE InnoDB;

CREATE TABLE `students`
(
   `human_id` integer NOT NULL,
   `study_group_id` integer NOT NULL,
   `has_scholarship` integer(1) NOT NULL,
    PRIMARY KEY (`human_id`)
    FOREIGN KEY (`human_id`) REFERENCES `humans` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
) ENGINE InnoDB;

CREATE TABLE `instructors`
(
   `human_id` integer NOT NULL,
   `rank_id` integer NOT NULL,
   `salary` integer NOT NULL,
    PRIMARY KEY (`human_id`)
    FOREIGN KEY (`human_id`) REFERENCES `humans` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
) ENGINE InnoDB;
```

This extension introduces [[\Illuminatech\DbRole\InheritRole]] trait, which allows role relation based Eloquent inheritance.
In oder to make it work, first of all, you should create an Eloquent class for the base table, in our example it
will be 'humans':

```php
<?php

use Illuminate\Database\Eloquent\Model;

class Human extends Model
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'humans';
    
    // ...
}
```

Then you will be able to compose Eloquent classes, which implements role-based inheritance using [[\Illuminatech\DbRole\InheritRole]].
There are 2 different ways for such classes composition:
 - Master role inheritance
 - Slave role inheritance


## Master role inheritance <span id="master-role-inheritance"></span>

This approach assumes role Eloquent class be descendant of the base role class, using 'has-one' relation to the slave one.

```php
<?php

use Illuminatech\DbRole\InheritRole;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Human // extending `Human` - not `ActiveRecord`!
{
    use InheritRole;

    /**
     * Defines name of the relation to the slave table
     * 
     * @return string
     */
    protected function roleRelationName(): string
    {
        return 'studentRole';
    }

    /**
     * Defines attribute values, which should be automatically saved to 'humans' table
     * 
     * @return array
     */
    protected function roleMarkingAttributes(): array
    {
        return [
            'role_id' => 'student', // mark 'Human' record as 'student'
        ];
    }
    
    public function studentRole(): HasOne
    {
        // Here `StudentRole` is an Eloquent, which uses 'students' table :
        return $this->hasOne(StudentRole::class, 'human_id');
    }
}
```

The main benefit of this approach is that role class directly inherits all methods and logic from the base one.
However, you'll need to declare an extra Eloquent class, which corresponds the role table.
Yet another problem is that you'll need to separate 'Student' records from 'Instructor' ones for the search process.
Without following code, it will return all 'Human' records, both 'Student' and 'Instructor':

```php
<?php

$students = Student::query()->get();
```

The solution for this could be introduction of special column 'role' in the 'humans' table and usage of the default
scope:

```php
<?php

use Illuminate\Database\Eloquent\Builder;

class Student extends Human
{
    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('role', function (Builder $builder) {
            $builder->where('role', '=', 'student');
        });
    }
    
    // ...
}
```

This approach should be chosen in case most functionality depends on the 'Human' attributes.


## Slave role inheritance <span id="slave-role-inheritance"></span>

This approach assumes role Eloquent class does not extends the base one, but relates to it via 'belongs-to':

```php
<?php

use Illuminatech\DbRole\InheritRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Instructor extends Model // do not extend `Human`!
{
    use InheritRole;

    /**
     * {@inheritdoc}
     */
    protected $primaryKey = 'human_id';

    /**
     * {@inheritdoc}
     */
    public $incrementing = false;

    /**
     * Defines name of the relation to the master table
     * 
     * @return string
     */
    protected function roleRelationName(): string
    {
        return 'human';
    }

    /**
     * Defines attribute values, which should be automatically saved to 'humans' table
     * 
     * @return array
     */
    protected function roleMarkingAttributes(): array
    {
        return [
            'role' => 'instructor',
        ];
    }
    
    public function human(): BelongsTo
    {
        return $this->belongsTo(Human::class);
    }
}
```

This approach does not require extra Eloquent class for functioning, and it does not need default scope specification.
It does not directly inherit logic declared in the base ActiveRecord, however any custom method declared in the related
class will be available via magic method `__call()` mechanism. Thus, if class `Human` has method `sayHello()`, you are
able to invoke it through `Instructor` instance.

This approach should be chosen in case most functionality depends on the 'Instructor' attributes.


## Accessing role attributes <span id="accessing-role-attributes"></span>

After being attached, [[\Illuminatech\DbRole\InheritRole]] provides access to the properties of the model bound by relation,
which is specified via [[\Illuminatech\DbRole\InheritRole::roleRelationName()]], as they were the main one:

```php
<?php

$model = Student::query()->first();
echo $model->study_group_id; // equals to $model->studentRole->study_group_id

$model = Instructor::query()->first();
echo $model->name; // equals to $model->human->name
```

However, this will work only for the attributes, which have been explicitly defined at related model via [[\Illuminate\Database\Eloquent\Model::$fillable]]
or [[\Illuminate\Database\Eloquent\Model::$guarded]]. Thus in order to make example from above function classes used for
the relations should be defined in following way:

```php
<?php

use Illuminate\Database\Eloquent\Model;

class StudentRole extends Model
{
    protected $table = 'students';

    protected $primaryKey = 'human_id';

    public $incrementing = false;

    /**
     * All attributes listed here will be postponed to the role model
     */
    protected $fillable = [
        'study_group_id',
        'has_scholarship',
    ];

    /**
     * All attributes listed here will be postponed to the role model
     */
    protected $guarded = [
        'human_id',
    ];
    
    // ...
}

class Human extends Model
{
    protected $table = 'humans';

     /**
      * All attributes listed here will be postponed to the role model
      */
    protected $fillable = [
        'role',
        'name',
        'address',
    ];
    
    /**
     * All attributes listed here will be postponed to the role model
     */
    protected $guarded = [
        'id',
    ];
    
    // ...
}
```

If the related model does not exist, for example, in case of new record, it will be automatically instantiated on the
first attempt to set role attribute:

```php
<?php

$model = new Student();
$model->study_group_id = 12;
var_dump($model->studentRole); // outputs object

$model = new Instructor();
$model->name = 'John Doe';
var_dump($model->human); // outputs object
```


## Accessing role methods <span id="accessing-role-methods"></span>

Any non-static method declared in the model related via [[\Illuminatech\DbRole\InheritRole::roleRelationName()]] can be accessed
from the owner model:

```php
<?php

use Illuminatech\DbRole\InheritRole;
use Illuminate\Database\Eloquent\Model;

class Human extends Model
{
    // ...

    public function sayHello($name)
    {
        return 'Hello, ' . $name;
    }
}

class Instructor extends Model
{
    use InheritRole;
    
    // ...
}

$model = new Instructor();
echo $model->sayHello('John'); // outputs: 'Hello, John'
```

This feature allows to inherit logic from the base role model in case of using 'slave' behavior setup approach.
However, this works both for the 'master' and 'slave' role approaches.


## Saving role data <span id="saving-role-data"></span>

When main model is saved the related role model will be saved as well:

```php
<?php

$model = new Student();
$model->name = 'John Doe';
$model->address = 'Wall Street, 12';
$model->study_group_id = 14;
$model->save(); // insert one record to the 'humans' table and one record - to the 'students' table
```

When main model is deleted related role model will be deleted as well:

```php
<?php

$student = Student::query()->first();
$student->delete(); // Deletes one record from 'humans' table and one record from 'students' table
```


## Querying role records <span id="querying-role-records"></span>

[[\Illuminatech\DbRole\InheritRole] works through relations. Thus, in order to make role attributes feature work,
it will perform an extra query to retrieve the role slave or master model, which may produce performance impact
in case you are working with several models. In order to reduce number of queries you may use `with()` on the
role relation:

```php
<?php

$students = Student::query()->with('studentRole')->get(); // only 2 queries will be performed
foreach ($students as $student) {
    echo $student->study_group_id . '<br>';
}

$instructors = Instructor::query()->with('human')->get(); // only 2 queries will be performed
foreach ($instructors as $instructor) {
    echo $instructor->name . '<br>';
}
```

You may apply 'with' for the role relation as default scope for the Eloquent query:

```php
<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Instructor extends Model
{
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('with-role', function (Builder $builder) {
            $builder->with('human');
        });
    }
    
    // ...
}
```

> Tip: you may name slave table primary key same as master one: use 'id' instead of 'human_id' for it.
  In this case conditions based on primary key will be always the same. However, this trick may cause extra
  troubles in case you are using joins for role relations at some point.

If you need to specify search condition based on fields from both entities and you are using relational database,
you can use `join()` method.


## Creating role setup web interface <span id="creating-role-setup-web-interface"></span>

Figuratively speaking, [[\Illuminatech\DbRole\InheritRole]] merges 2 Eloquent classes into a single one.
This means you don't need anything special, while creating web interface for their editing.
However, you should remember to add role attributes to the [[\Illuminate\Database\Eloquent\Model::$fillable]] list
in order to make them available for mass assignment.

```php
<?php

use Illuminatech\DbRole\InheritRole;
use Illuminate\Database\Eloquent\Model;

class Instructor extends Model
{
    use InheritRole;

    protected $fillable = [
        // own fillable attributes:
        'rank_id',
        'salary',
        // role fillable attributes:
        'name',
        'address',
    ];

    // ...
}
```

Then controller, which performs the data storage may look like following:

```php
<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class InstructorController extends Controller
{
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'salary' => ['required', 'number', 'min:0'],
            'rank_id' => ['required', 'string'],
            // role attributes
            'name' => ['required', 'string'],
            'address' => ['required', 'string'],
        ]);
        
        $item = new Instructor;
        $item->fill($validatedData); // single assignment covers both main model and role model
        $item->save(); // role model saved automatically
        
        // return response
    }
}
```
