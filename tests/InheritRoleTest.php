<?php

namespace Illuminatech\DbRole\Test;

use Illuminatech\DbRole\Test\Support\Human;
use Illuminatech\DbRole\Test\Support\Student;
use Illuminatech\DbRole\Test\Support\Instructor;
use Illuminatech\DbRole\Test\Support\StudentRole;

class InheritRoleTest extends TestCase
{
    public function testGetRoleRelationModel()
    {
        $model = new Student();
        $roleModel = $model->getRoleRelationModel();
        $this->assertTrue($roleModel instanceof StudentRole);
        $this->assertSame($roleModel, $model->studentRole);

        $model = Student::query()->first();
        $roleModel = $model->getRoleRelationModel();
        $this->assertTrue($roleModel instanceof StudentRole);
    }

    /**
     * @depends testGetRoleRelationModel
     */
    public function testFieldAccess()
    {
        $model = new Student();
        $model->study_group_id = 12;
        $this->assertEquals(12, $model->studentRole->study_group_id);
        $this->assertEquals(12, $model->study_group_id);
    }

    /**
     * @depends testFieldAccess
     */
    public function testInvokeRoleRelatedModelMethod()
    {
        $model = new Instructor();
        $this->assertEquals('Hello, John', $model->sayHello('John'));
    }

    /**
     * @depends testFieldAccess
     */
    public function testInsertRecord()
    {
        $model = new Student();

        $model->name = 'new name';
        $model->study_group_id = 12;

        $model->save();

        $this->assertEquals('student', $model->role);

        $roleModel = StudentRole::query()
            ->where(['human_id' => $model->id])
            ->first();

        $this->assertNotEmpty($roleModel);
        $this->assertEquals($model->study_group_id, $roleModel->study_group_id);
    }

    /**
     * @depends testInsertRecord
     */
    public function testUpdateRecord()
    {
        $model = new Student();
        $model->name = 'new name';
        $model->study_group_id = 12;
        $model->save();

        $model = Student::query()->where(['id' => $model->id])->first();
        $model->study_group_id = 14;
        $model->save();

        $roleModels = StudentRole::query()->where(['human_id' => $model->id])->get();
        $this->assertCount(1, $roleModels, 'No role model saved');
        $this->assertEquals($model->study_group_id, $roleModels[0]->study_group_id, 'Unable to save data for role model');

        /*$model = Student::query()->where(['id' => $model->id])->first();
        $model->name = 'updated name';
        $model->save();
        $this->assertFalse($model->relationLoaded('studentRole'), 'Role model saved, while its data not touched');*/
    }

    /**
     * @depends testUpdateRecord
     */
    public function testDelete()
    {
        $model = new Student();
        $model->name = 'new name';
        $model->study_group_id = 12;
        $model->save();

        $model = Student::query()->where(['id' => $model->id])->first();
        $model->delete();

        $this->assertFalse(StudentRole::query()->where(['human_id' => $model->id])->exists());
    }

    /**
     * @depends testFieldAccess
     */
    public function testInsertInverted()
    {
        $model = new Instructor();

        $model->name = 'new name';
        $model->rank_id = 15;

        $model->save();

        $roleModel = Human::query()->where(['id' => $model->human_id])->first();
        $this->assertNotEmpty($roleModel);
        $this->assertEquals($model->name, $roleModel->name);
        $this->assertEquals('instructor', $model->role);
    }

    /**
     * @depends testInsertInverted
     */
    public function testUpdateInverted()
    {
        $model = new Instructor();
        $model->name = 'new name';
        $model->rank_id = 15;
        $model->save();

        $model = Instructor::query()->where(['human_id' => $model->human_id])->first();
        $model->name = 'updated name';
        $model->save();

        $roleModel = Human::query()->where(['id' => $model->human_id])->first();
        $this->assertEquals($model->name, $roleModel->name, 'Unable to save data for role model');

        /*$model = Instructor::query()->where(['human_id' => $model->human_id])->first();
        $model->salary = 150;
        $model->save();
        $this->assertFalse($model->relationLoaded('human'), 'Role model saved, while its data not touched');*/
    }

    /**
     * @depends testUpdateInverted
     */
    public function testDeleteInverted()
    {
        $model = new Instructor();
        $model->name = 'new name';
        $model->rank_id = 15;
        $model->save();

        $model = Instructor::query()->where(['human_id' => $model->human_id])->first();

        $model->delete();

        $this->assertFalse(Human::query()->where(['id' => $model->human_id])->exists());
    }
}
