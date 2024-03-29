<?php

namespace Gaufrette\Exception;

use Gaufrette\Exception;
use Gaufrette\Filesystem;

final class CouldNotDelete extends \Exception implements Exception
{
    public static function create(Filesystem $filesystem, string $path, \Throwable $previous = null): CouldNotDelete
    {
        return new self(sprintf('Filesystem "%s" could not delete "%s".', get_class($filesystem), $path), 0, $previous);
    }
}
