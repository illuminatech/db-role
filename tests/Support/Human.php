<?php

namespace Illuminatech\DbRole\Test\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $role
 * @property string $name
 * @property string $address
 */
class Human extends Model
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'humans';

    /**
     * {@inheritdoc}
     */
    public $timestamps = false;

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'role',
        'name',
        'address',
    ];

    /**
     * {@inheritdoc}
     */
    protected $guarded = [
        'id',
    ];

    public function sayHello(string $name): string
    {
        return 'Hello, '.$name;
    }
}
