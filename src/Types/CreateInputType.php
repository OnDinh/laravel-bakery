<?php

namespace Bakery\Types;

use Bakery\Utils\Utils;
use Illuminate\Support\Collection;

class CreateInputType extends MutationInputType
{
    /**
     * Get the name of the Create Input Type.
     *
     * @return string
     */
    protected function name(): string
    {
        return 'Create'.$this->schema->typename().'Input';
    }

    /**
     * Return the fields for the Create Input Type.
     *
     * @return array
     */
    public function fields(): array
    {
        $fields = array_merge(
            $this->getFillableFields()->toArray(),
            $this->getRelationFields()->toArray()
        );

        Utils::invariant(
            count($fields) > 0,
            'There are no fields defined for '.class_basename($this->model)
        );

        return $fields;
    }

    /**
     * Get the fillable fields of the model.
     *
     * @return Collection
     */
    protected function getFillableFields(): Collection
    {
        $fields = parent::getFillableFields();

        return $fields->map(function ($field, $key) {
            $defaults = $this->model->getAttributes();

            if (in_array($key, array_keys($defaults))) {
                return Utils::nullifyField($field);
            }

            return $field;
        });
    }
}
