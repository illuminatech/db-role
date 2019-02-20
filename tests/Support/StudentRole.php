<?php

namespace Illuminatech\DbRole\Test\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $human_id
 * @property int $study_group_id
 * @property bool $has_scholarship
 */
class StudentRole extends Model
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'students';

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

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'study_group_id',
        'has_scholarship',
    ];

    /**
     * {@inheritdoc}
     */
    protected $guarded = [
        'human_id',
    ];
}
