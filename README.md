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

