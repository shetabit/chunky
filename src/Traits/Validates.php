<?php

namespace Shetabit\Chunky\Traits;

use Shetabit\Chunky\Contracts\FileInterface;
use Shetabit\Chunky\Contracts\ValidationInterface;
use Shetabit\Chunky\Exceptions\ValidationFailedException;

trait Validates
{
    /**
     * The validations every file has to pass.
     *
     * @var list<ValidationInterface>
     */
    protected array $validations = [];

    /**
     * Add a validation every file has to pass.
     */
    public function addValidation(ValidationInterface $validation) : static
    {
        $this->validations[] = $validation;

        return $this;
    }

    /**
     * Add validations every file has to pass.
     *
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
     * The validations every file has to pass.
     *
     * @return list<ValidationInterface>
     */
    public function getValidations() : array
    {
        return $this->validations;
    }

    /**
     * Run every validation against the given file and collect what they say.
     *
     * A validation that fails does not stop the ones behind it: a client that is told
     * everything that is wrong at once can fix it in one go.
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

    /**
     * What a file is called in the error messages of a validation.
     */
    protected function describeFile(FileInterface $file) : string
    {
        $name = $file->name;

        return is_string($name) && trim($name) !== '' ? trim($name) : 'The file';
    }
}
