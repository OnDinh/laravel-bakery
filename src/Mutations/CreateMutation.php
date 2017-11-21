<?php

namespace Bakery\Mutations;

use Bakery\Support\Field;
use GraphQL\Type\Definition\Type;
use Bakery\Support\Facades\Bakery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CreateMutation extends Field
{
    use AuthorizesRequests;

    /**
     * A reference to the model.
     */
    protected $model;

    /**
     * The class of the model.
     *
     * @var string
     */
    protected $class;

    /**
     * The name of the mutation.
     *
     * @var string
     */
    public $name;

    /**
     * Construct a new create mutation.
     *
     * @param string $class
     * @param string $name
     */
    public function __construct(string $class)
    {
        $this->class = $class;
        $this->name = $this->formatName($class);
        $this->model = app()->make($class);
    }

    /**
     * Format the class name to the name for the create mutation.
     *
     * @param string $class
     * @return string
     */
    protected function formatName(string $class): string
    {
        return 'create' . title_case(str_singular(class_basename($class)));
    }

    /**
     * Get the return type of the mutation.
     *
     * @return Type
     */
    public function type()
    {
        return Bakery::getType(title_case(class_basename($this->class)));
    }

    /**
     * Get the arguments of the mutation.
     *
     * @return array
     */
    public function args()
    {
        $name = 'Create' . title_case(str_singular(class_basename($this->class))) . 'Input';

        return [
            'input' => Bakery::nonNull(Bakery::getType($name)),
        ];
    }

    /**
     * Convert the create mutation to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->getAttributes();
    }

    /**
     * Resolve the mutation.
     *
     * @param  mixed $root
     * @param  array $args
     * @return Model
     */
    public function resolve($root, $args = []): Model
    {
        $input = $args['input'];
        $this->authorize('create', $this->class);

        $this->validate($input);

        $model = $this->model->createWithGraphQLInput($input);
        return $model;
    }

    /**
     * Validate the input of the mutation.
     *
     * @param  array $input
     * @return void
     */
    private function validate(array $input)
    {
        $rules = collect($this->model->fields())->mapWithKeys(function ($value, $key) {
            return [$key => array_key_exists('validation', $value) ? $value['validation'] : []];
        })->toArray();

        app('validator')->make($input, $rules)->validate();
    }
}
