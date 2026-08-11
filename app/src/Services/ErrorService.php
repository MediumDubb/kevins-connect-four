<?php

namespace MediumDubb\ConnectFour\Services;

class ErrorService
{
    const string SESSION_KEY = "C4SE";
    private SessionService $session;

    private static ?string $error_message = null;
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
        self::$error_message = $errorMsg;
        $this->setSessionError();

        self::$valid = false;
    }

    public function isValid(): bool
    {
        return self::$valid;
    }

    public function getError(): array
    {
        return $this->getSessionError();
    }

    public function clear(): void
    {
        $this->session->remove(self::SESSION_KEY);
    }

    private function setSessionError(): void
    {
        $this->session->set(self::SESSION_KEY, $this::$error_message);
    }

    private function getSessionError(): array
    {
        return $this->session->get(self::SESSION_KEY);
    }
}