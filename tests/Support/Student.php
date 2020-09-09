<?php

namespace Illuminatech\DbRole\Test\Support;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminatech\DbRole\InheritRole;

/**
 * @property StudentRole $studentRole
 *
 * @property int $study_group_id
 * @property bool $has_scholarship
 */
class Student extends Human
{
    use InheritRole;

    public function studentRole(): HasOne
    {
        return $this->hasOne(StudentRole::class, 'human_id');
    }

    protected function roleRelationName(): string
    {
        return 'studentRole';
    }

    protected function roleMarkingAttributes(): array
    {
        return [
            'role' => 'student',
        ];
    }
}
