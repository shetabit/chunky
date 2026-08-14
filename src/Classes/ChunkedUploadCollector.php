<?php

namespace Shetabit\Chunky\Classes;

use Shetabit\Chunky\Contracts\TempFileInterface;

/**
 * Reads the attributes of a chunk out of the request it arrived in, which is how an
 * upload carries what `$_FILES` has no room for.
 *
 * A field holding a single value belongs to every chunk of the request, a field
 * holding a list belongs to the chunks one by one:
 *
 *     media: <bytes>          media[0]: <bytes>   media[1]: <bytes>
 *     range: 262144-524287    range[0]: 0-262143  range[1]: 262144-524287
 *     size:  1048576          size[0]:  1048576   size[1]:  524288
 */
class ChunkedUploadCollector extends Collector
{
    /**
     * `name` and `mime` are left out: `$_FILES` holds both, and a form with a field
     * called `name` next to its upload would otherwise rename what it uploads.
     *
     * @var list<string>
     */
    public const array DEFAULT_ATTRIBUTES = ['range', 'size'];

    /**
     * @var array<string, string>
     */
    protected array $attributes;

    /**
     * @param list<string>|array<string, string> $attributes the attributes to read, either by name or as
     *                                                       `attribute => request field`
     * @param array<string, mixed>|null          $source     where the fields are read from, `$_POST` by default
     */
    public function __construct(
        string|null $inputName = null,
        array $attributes = self::DEFAULT_ATTRIBUTES,
        protected array|null $source = null,
    ) {
        parent::__construct($inputName);

        $this->attributes = $this->normalizeAttributes($attributes);
    }

    /**
     * What the request says about a chunk wins over what the chunk says about itself.
     *
     * @return list<TempFileInterface>
     */
    public function collect() : array
    {
        $files = parent::collect();

        foreach ($files as $index => $file) {
            foreach ($this->attributes as $attribute => $field) {
                $value = $this->option($field, $index);

                if ($value !== null) {
                    $file->{$attribute} = $value;
                }
            }
        }

        return $files;
    }

    /**
     * @return array<string, string>
     */
    public function getOptions() : array
    {
        return $this->attributes;
    }

    protected function option(string $field, int $index) : string|null
    {
        $value = $this->source()[$field] ?? null;

        if (is_array($value)) {
            $value = $value[$index] ?? null;
        }

        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * @return array<string, mixed>
     */
    protected function source() : array
    {
        return $this->source ?? $_POST;
    }

    /**
     * @param list<string>|array<string, string> $attributes
     *
     * @return array<string, string>
     */
    protected function normalizeAttributes(array $attributes) : array
    {
        $normalized = [];

        foreach ($attributes as $attribute => $field) {
            $normalized[is_int($attribute) ? $field : $attribute] = $field;
        }

        return $normalized;
    }
}
