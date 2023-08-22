<?php
class ZIO
{

    private $effect;
    // Конструктор принимает функцию, которая выполняет эффектную операцию
    public function __construct(callable $effect) {
        $this->effect = $effect;
    }

    // Функция, которая запускает эффектную операцию
    public function run() {
        try {
            return call_user_func($this->effect);
        } catch (Exception $e) {
            throw new ZIOError($e);
        }
    }

    // Функция, которая позволяет комбинировать эффекты вместе
    public function flatMap(callable $f): ZIO {
        return new ZIO(function () use ($f) {
            $result = $this->run();
            if ($result instanceof ZIO) {
                return $result->flatMap($f)->run();
            } else {
                return $f($result)->run();
            }
        });
    }

    // Функция, которая позволяет применять эффект к результату
    public function map(callable $f): ZIO {
        return new ZIO(function () use ($f) {
            return $f($this->run());
        });
    }

    // Функция, которая позволяет обрабатывать ошибки
    public function catch(callable $handler): ZIO {
        return new ZIO(function () use ($handler) {
            try {
                return $this->run();
            } catch (ZIOError $e) {
                return $handler($e->getOriginalException())->run();
            }
        });
    }
}

// Класс для обработки ошибок
class ZIOError extends Exception {
    public function __construct(Exception $e) {
        parent::__construct("ZIO error occurred", 0, $e);
    }

    public function getOriginalException(): Throwable
    {
        return $this->getPrevious();
    }
}
