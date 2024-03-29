<?php

namespace features\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Gaufrette\Filesystem;
use Gaufrette\File;

final class Infrastructure implements Context, SnippetAcceptingContext
{
    private $readInitializer;
    private $writeExpectation;
    private $fs;
    private $path;
    private $file;
    private $deleteExpectation;
    private $treeInitializer;
    private $directory;
    private $directoryExpectation;
    private $findExpectation;
    private $findResult;

    public function __construct(
        Filesystem $fs,
        callable $readInitializer,
        callable $writeExpectation,
        callable $deleteExpectation,
        callable $treeInitializer,
        callable $directoryExpectation,
        callable $findExpectation
    ) {
        $this->fs = $fs;
        $this->readInitializer      = $readInitializer;
        $this->writeExpectation     = $writeExpectation;
        $this->deleteExpectation    = $deleteExpectation;
        $this->treeInitializer      = $treeInitializer;
        $this->directoryExpectation = $directoryExpectation;
        $this->findExpectation      = $findExpectation;
    }

    /**
     * @Given a file stored at ":path"
     */
    public function aFileStoredAt($path)
    {
        $this->path = $path;
        ($this->readInitializer)($path);
    }

    /**
     * @When I ask for this file
     */
    public function iAskForThisFile()
    {
        $this->file = $this->fs->read($this->path);
    }

    /**
     * @Then I should get the corresponding file object
     */
    public function iShouldGetTheCorrespondingFileObject()
    {
        $file = $this->fs->read($this->path);
        expect(implode(iterator_to_array($file)))->toBe('some content');
    }

    /**
     * @Given a file object for ":path"
     */
    public function aFileObjectFor($path)
    {
        $this->path = $path;
        $this->file = new File($path, function(){
            for($i = 0; $i < 3; $i++) {
                yield implode(array_fill(0, 1024, 'a'));
            }
        });
    }

    /**
     * @When I write it
     */
    public function iWriteIt()
    {
        $this->fs->write($this->file);
    }

    /**
     * @Then it should be stored
     */
    public function itShouldBeStored()
    {
        ($this->writeExpectation)($this->path);
    }

    /**
     * @When I delete it
     */
    public function iDeleteIt()
    {
        $this->fs->delete($this->file);
    }

    /**
     * @Then it should be deleted
     */
    public function itShouldBeDeleted()
    {
        ($this->deleteExpectation)($this->path);
    }

    /**
     * @Given a complex tree structure
     */
    public function aComplexTreeStructure()
    {
        ($this->treeInitializer)();
    }

    /**
     * @When I read directory :directory content
     */
    public function iListDirectoryContent($directory)
    {
        $this->directory = $this->fs->readDirectory($directory);
    }

    /**
     * @Then I should see the directory content
     */
    public function iShouldSeeTheDirectoryContent()
    {
        ($this->directoryExpectation)($this->directory);
    }

    /**
     * @When I search in directory :directory
     * @When I search for pattern :pattern in directory :directory
     */
    public function iSearchForPatternInDirectory($directory, $pattern = '')
    {
        $this->findResult = $this->fs->find($directory, $pattern);
    }

    /**
     * @Then I should see the complete tree
     */
    public function iShouldSeeTheCompleteTree()
    {
        ($this->findExpectation)($this->findResult);
    }
}
