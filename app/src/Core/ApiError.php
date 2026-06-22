<?php

namespace MediumDubb\ConnectFour\Core;

class ApiError
{
    private static array $errors = [];
    private static bool $valid = true;

    private static ApiError $error;


    private function __construct()
    {
    }

    /**
     * Get the singleton instance of the ApiErrors class.
     *
     * @return ApiError The singleton instance of the ApiErrors class.
     */
    public static function get(): ApiError
    {
        if (!isset(self::$error))
        {

            self::$error = new ApiError();
        }

        return self::$error;
    }

    public function setError(string $errorMsg): void
    {
        self::$errors[] = $errorMsg;
        self::$valid = false;
    }

    public function isValid(): bool
    {
        return self::$valid;
    }

    public function getErrorsList(): array
    {
        return self::$errors;
    }

    public function getSerializedErrors(): string
    {
        return json_encode($this::$errors);
    }
}