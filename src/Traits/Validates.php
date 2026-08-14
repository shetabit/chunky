<?php

namespace Shetabit\Chunky\Traits;

use Shetabit\Chunky\Contracts\FileInterface;
use Shetabit\Chunky\Contracts\ValidationInterface;
use Shetabit\Chunky\Exceptions\ValidationFailedException;

trait Validates
{
    /**
     * @var list<ValidationInterface>
     */
    protected array $validations = [];

    public function addValidation(ValidationInterface $validation) : static
    {
        $this->validations[] = $validation;

        return $this;
    }

    /**
     * @param list<ValidationInterface> $validations
     */
    public function addValidations(array $validations) : static
    {
        foreach ($validations as $validation) {
            $this->addValidation($validation);
        }

        return $this;
    }

    /**
     * @return list<ValidationInterface>
     */
    public function getValidations() : array
    {
        return $this->validations;
    }

    /**
     * A validation that fails does not stop the ones behind it, so a client is told
     * everything that is wrong at once.
     *
     * @return list<string>
     */
    protected function validateFile(FileInterface $file) : array
    {
        $errors = [];

        foreach ($this->validations as $validation) {
            try {
                $validation->validate($file);
            } catch (ValidationFailedException $exception) {
                $errors[] = sprintf('%s: %s', $this->describeFile($file), $exception->getMessage());
            }
        }

        return $errors;
    }

    protected function describeFile(FileInterface $file) : string
    {
        $name = $file->name;

        return is_string($name) && trim($name) !== '' ? trim($name) : 'The file';
    }
}
