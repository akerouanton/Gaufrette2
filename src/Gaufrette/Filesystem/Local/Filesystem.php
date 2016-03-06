<?php

declare(strict_types=1);

namespace Gaufrette\Filesystem\Local;

use Gaufrette\Exception\CouldNotDelete;
use Gaufrette\Exception\CouldNotOpen;;
use Gaufrette\Exception\CouldNotRead;
use Gaufrette\Exception\CouldNotWrite;
use Gaufrette\File;

final class Filesystem implements \Gaufrette\Filesystem
{
    /** @var Client */
    private $client;

    /** @var string */
    private $basePath;

    /** @var int */
    private $chunkSize;

    /**
     * @param string      $basePath
     * @param Client|null $client
     * @param int         $chunkSize
     */
    public function __construct(string $basePath, Client $client = null, $chunkSize = 1024)
    {
        $this->basePath = $basePath;
        $this->client = $client ?: new Client;
        $this->chunkSize = $chunkSize;
    }

    /**
     * {@inheritdoc}
     */
    public function read(string $path): File
    {
        return new File($path, $this->iterate($path));
    }

    /**
     * {@inheritdoc}
     */
    public function write(File $file)
    {
        if (!$pointer = $this->client->fopen($this->absolutify($file->getPath()), 'w+')) {
            throw CouldNotOpen::create($this, $file->getPath());
        }

        try {
            if (!$this->client->mkdir($this->absolutify(dirname($file->getPath())))) {
                throw CouldNotWrite::create($this, dirname($file->getPath()));
            }
            foreach ($file as $chunk) {
                if (false === $this->client->fwrite($pointer, $chunk)) {
                    throw CouldNotWrite::create($this, $file->getPath());
                }
            }
        }
        finally {
            $this->client->fclose($pointer);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete(File $file)
    {
        if (!$this->client->unlink($this->absolutify($file->getPath()))) {
            throw CouldNotDelete::create($this, $file->getPath());
        }
    }

    /**
     * @param string $path
     *
     * @return callable
     */
    private function iterate($path): callable
    {
        return function() use($path) {
            if (!$pointer = $this->client->fopen($this->absolutify($path), 'r')) {
                throw CouldNotOpen::create($this, $path);
            }

            try {
                while ($chunk = $this->client->fread($pointer, $this->chunkSize)) {
                    yield $chunk;
                }
                if (false === $chunk) {
                    throw CouldNotRead::create($this, $path);
                }
            }
            finally {
                $this->client->fclose($pointer);
            }
        };
    }

    /**
     * @param string $path
     *
     * @return string
     */
    private function absolutify(string $path)
    {
        return sprintf('%s/%s', rtrim($this->basePath, '/'), ltrim($path, '/'));
    }
}
