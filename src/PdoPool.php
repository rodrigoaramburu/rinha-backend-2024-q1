<?php 
declare(strict_types=1);

namespace Rinha;

class PdoPool extends \PDO{

    private bool $busy = false;

    public function isBusy(): bool
    {
        return $this->busy;
    }

    public function attach(): void
    {
        $this->busy = true;
    }
    public function detach(): void
    {
        $this->busy = false;
    }


}