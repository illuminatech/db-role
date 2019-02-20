<?php

namespace Illuminatech\DbRole\Test\Support;

use Illuminatech\DbRole\InheritRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $human_id
 * @property int $rank_id
 * @property float $salary
 *
 * @property Human $human
 *
 * @property int $id
 * @property string $role
 * @property string $name
 * @property string $address
 */
class Instructor extends Model
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
     * {@inheritdoc}
     */
    public $timestamps = false;

    public function human(): BelongsTo
    {
        return $this->belongsTo(Human::class);
    }

    protected function roleRelationName(): string
    {
        return 'human';
    }

    protected function roleMarkingAttributes(): array
    {
        return [
            'role' => 'instructor',
        ];
    }
}
