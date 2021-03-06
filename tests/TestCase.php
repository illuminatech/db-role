<?php

namespace Illuminatech\DbRole\Test;

use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Events\Dispatcher;

/**
 * Base class for the test cases.
 */
class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $db = new Manager;

        $db->addConnection([
            'driver'    => 'sqlite',
            'database'  => ':memory:',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();

        $this->createSchema();

        $this->seedData();

        Model::setEventDispatcher(new Dispatcher());
        Model::clearBootedModels();
    }

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    protected function getConnection()
    {
        return Model::getConnectionResolver()->connection();
    }

    /**
     * Get a schema builder instance.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected function getSchemaBuilder()
    {
        return $this->getConnection()->getSchemaBuilder();
    }

    /**
     * Setup the database schema.
     *
     * @return void
     */
    protected function createSchema()
    {
        $this->getSchemaBuilder()->create('humans', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('role')->nullable();
            $table->string('address')->nullable();
        });

        $this->getSchemaBuilder()->create('students', function (Blueprint $table) {
            $table->unsignedInteger('human_id')->primary();
            $table->unsignedInteger('study_group_id')->nullable();
            $table->boolean('has_scholarship')->nullable();
        });

        $this->getSchemaBuilder()->create('instructors', function (Blueprint $table) {
            $table->unsignedInteger('human_id')->primary();
            $table->unsignedInteger('rank_id')->nullable();
            $table->decimal('salary')->nullable();
        });
    }

    /**
     * Seeds the database.
     *
     * @return void
     */
    protected function seedData()
    {
        $this->getConnection()->table('humans')->insert([
            'name' => 'Mark',
            'address' => 'Wall Street',
            'role' => 'student',
        ]);
        $this->getConnection()->table('humans')->insert([
            'name' => 'Michael',
            'address' => '1st Avenue',
            'role' => 'student',
        ]);
        $this->getConnection()->table('humans')->insert([
            'name' => 'John',
            'address' => '2st Avenue',
            'role' => 'instructor',
        ]);
    }
}
