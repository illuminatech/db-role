<?php

namespace Illuminatech\DbRole\Test\Support;

use Illuminate\Database\Eloquent\Model;

class Human extends Model
{
    public function sayHello(string $name): string
    {
        return 'Hello, '.$name;
    }
}
