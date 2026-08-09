<?php

namespace MediumDubb\ConnectFour\Services;

class ErrorService
{
    const string SESSION_KEY = "Errors";
    private SessionService $session;

    private static array $errors = [];
    private static bool $valid = true;
    private static ErrorService $error;


    private function __construct()
    {
        $this->session = new SessionService();
    }

    /**
     * Get the singleton instance of the ApiErrors class.
     *
     * @return ErrorService The singleton instance of the ApiErrors class.
     */
    public static function get(): ErrorService
    {
        if (!isset(self::$error))
        {

            self::$error = new ErrorService();
        }

        return self::$error;
    }

    public function setError(string $errorMsg): void
    {
        self::$errors[] = $errorMsg;
        $this->setSessionErrors();

        self::$valid = false;
    }

    public function isValid(): bool
    {
        return self::$valid;
    }

    public function getErrorsList(): array
    {
        return $this->getSessionErrors();
    }

    public function clearErrors(): void
    {
        $this->session->remove(self::SESSION_KEY);
    }

    private function toJSON(): string
    {
        return json_encode($this::$errors);
    }

    private function fromJSON(string $serialized): array
    {
        return json_decode($serialized);
    }

    private function setSessionErrors(): void
    {
        $serialized = $this->toJSON();
        $this->session->set(self::SESSION_KEY, $serialized);
    }

    private function getSessionErrors(): array
    {
        $errors = $this->session->get(self::SESSION_KEY);
        return $this->fromJSON($errors);
    }
}