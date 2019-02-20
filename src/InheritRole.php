<?php
/**
 * @link https://github.com/illuminatech
 * @copyright Copyright (c) 2015 Illuminatech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace Illuminatech\DbRole;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * InheritRole allows its owner .
 *
 * @see \Illuminate\Database\Eloquent\Relations\BelongsTo
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
trait InheritRole
{
    /**
     * @var bool whether retrieving of the attribute from role model is in process.
     * This flag exists to avoid possible infinite recursion.
     * @see getRoleRelationModel()
     * @see getAttribute()
     */
    private $resolvingRoleRelationModel = false;

    abstract protected function roleRelationName(): string;

    protected function roleMarkingAttributes(): array
    {
        return [];
    }

    /**
     * Returns the record related via [[roleRelation]] relation.
     * If no related record exists - new one will be instantiated.
     *
     * @return \Illuminate\Database\Eloquent\Model role related model.
     */
    public function getRoleRelationModel(): Model
    {
        $this->resolvingRoleRelationModel = true;

        $roleRelationName = $this->roleRelationName();

        $model = $this->getRelationValue($roleRelationName);
        if (! is_object($model)) {
            /* @var $relation BelongsTo|HasOne */
            $relation = $this->{$roleRelationName}();
            $model = $relation->make();

            $this->setRelation($roleRelationName, $model);
        }

        $this->resolvingRoleRelationModel = false;

        return $model;
    }

    /**
     * Boots this trait in the scope of the owner model, attaching necessary event handlers.
     * @see \Illuminate\Database\Eloquent\Model::bootTraits()
     */
    protected static function bootInheritRole()
    {
        static::saving(function ($model) {
            /* @var $model \Illuminate\Database\Eloquent\Model|static */

            $roleRelationName = $model->roleRelationName();

            $relation = $model->{$roleRelationName}();

            // Master :
            if ($relation instanceof HasOne) {
                $model->resolvingRoleRelationModel = true;

                foreach ($model->roleMarkingAttributes() as $name => $value) {
                    $model->{$name} = $value;
                }

                $model->resolvingRoleRelationModel = false;

                return;
            }

            // Slave :
            if ($relation instanceof BelongsTo) {
                if (! $model->relationLoaded($roleRelationName) && $model->{$relation->getForeignKey()} !== null) {
                    return;
                }

                $roleModel = $model->getRoleRelationModel();

                foreach ($model->roleMarkingAttributes() as $name => $value) {
                    $roleModel->{$name} = $value;
                }

                $roleModel->save();

                $model->{$relation->getForeignKey()} = $roleModel->{$relation->getOwnerKey()};

                return;
            }

        });

        static::saved(function ($model) {
            /* @var $model \Illuminate\Database\Eloquent\Model|static */

            $roleRelationName = $model->roleRelationName();

            $relation = $model->{$roleRelationName}();

            // Slave :
            if ($relation instanceof BelongsTo) {
                return;
            }

            // Master :
            if ($relation instanceof HasOne) {
                if (! $model->relationLoaded($roleRelationName)) {
                    return;
                }

                $roleModel = $model->getRoleRelationModel();

                $relation->save($roleModel);

                return;
            }
        });

        static::deleting(function ($model) {
            $model->resolvingRoleRelationModel = true;

            /* @var $model \Illuminate\Database\Eloquent\Model|static */
            $roleRelationName = $model->roleRelationName();

            $relation = $model->{$roleRelationName}();

            // Master :
            if ($relation instanceof HasOne) {
                $relation->delete();
            }

            $model->resolvingRoleRelationModel = false;
        });

        static::deleted(function ($model) {
            $model->resolvingRoleRelationModel = true;

            /* @var $model \Illuminate\Database\Eloquent\Model|static */
            $roleRelationName = $model->roleRelationName();

            $relation = $model->{$roleRelationName}();

            // Slave :
            if ($relation instanceof BelongsTo) {
                $relation->delete();
            }

            $model->resolvingRoleRelationModel = false;
        });
    }

    /**
     * Set a given attribute on the model.
     * @see \Illuminate\Database\Eloquent\Model::setAttribute()
     *
     * @param  string  $key attribute name.
     * @param  mixed  $value attribute value.
     * @return \Illuminate\Database\Eloquent\Model|static|mixed
     */
    public function setAttribute($key, $value)
    {
        if ($key !== $this->getKeyName() && ! $this->resolvingRoleRelationModel) {
            $roleModel = $this->getRoleRelationModel();

            if (array_key_exists($key, $roleModel->getAttributes()) || in_array($key, $roleModel->getFillable(), true) || in_array($key, $roleModel->getGuarded(), true)) {
                return $roleModel->setAttribute($key, $value);
            }
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * Get an attribute from the model.
     * @see \Illuminate\Database\Eloquent\Model::getAttribute()
     *
     * @param  string  $key attribute name.
     * @return mixed attribute value.
     */
    public function getAttribute($key)
    {
        if ($key !== $this->getKeyName() && ! $this->resolvingRoleRelationModel) {
            $roleModel = $this->getRoleRelationModel();

            if (array_key_exists($key, $roleModel->getAttributes()) || $this->hasGetMutator($key)) {
                return $roleModel->getAttribute($key);
            }

            if (method_exists($roleModel, $key)) {
                return $roleModel->getRelationValue($key);
            }
        }

        return parent::getAttribute($key);
    }

    /**
     * Unset the value for a given offset.
     * @see \Illuminate\Database\Eloquent\Model::offsetUnset()
     *
     * @param  mixed  $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        if (! $this->resolvingRoleRelationModel) {
            $this->getRoleRelationModel()->offsetUnset($offset);
        }

        parent::offsetUnset($offset);
    }

    /**
     * Handle dynamic method calls into the model.
     * @see \Illuminate\Database\Eloquent\Model::__call()
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (! $this->resolvingRoleRelationModel) {
            $roleModel = $this->getRoleRelationModel();
            if (method_exists($roleModel, $method)) {
                return $this->forwardCallTo($roleModel, $method, $parameters);
            }
        }

        return parent::__call($method, $parameters);
    }
}
