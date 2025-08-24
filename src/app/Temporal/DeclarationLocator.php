<?php

declare(strict_types=1);

namespace App\Temporal;

use Spiral\Tokenizer\ClassesInterface;
use Spiral\Tokenizer\ClassLocator;
use Symfony\Component\Finder\Finder;
use Utils\Command;

class DeclarationLocator
{
    private ClassesInterface $classLocator;

    public function __construct(string $directory)
    {
        $this->classLocator = new ClassLocator(
            (new Finder())->in($directory)->name('*.php')
        );
    }

    /**
     * @return  \Generator
     */
    public function getCommands(): \Generator
    {
        foreach ($this->classLocator->getClasses(Command::class) as $class) {
            if (!$class->isAbstract()) {
                yield $class->getName();
            }
        }
    }

    /**
     * Finds all activity declarations using Activity suffix.
     *
     * @return  \Generator
     */
    public function getActivityTypes(): \Generator
    {
        $registered = [];
        foreach ($this->getAvailableDeclarations() as $class) {
            $className = $class->getName();
            if ($this->endsWith($className, 'Activity') && !in_array($className, $registered)) {
                $registered[] = $className;
                yield $className;
            }
        }
    }

    /**
     * Finds all workflow declarations using Workflow suffix.
     *
     * @return  \Generator
     */
    public function getWorkflowTypes(): \Generator
    {
        $registered = [];
        foreach ($this->getAvailableDeclarations() as $class) {
            $className = $class->getName();
            if ($this->endsWith($className, 'Workflow') && !in_array($className, $registered)) {
                $registered[] = $className;
                yield $className;
            }
        }
    }

    /**
     * @return \Generator|\ReflectionClass[]
     */
    private function getAvailableDeclarations(): \Generator
    {
        foreach ($this->classLocator->getClasses() as $class) {
            if ($class->isAbstract() || $class->isInterface()) {
                continue;
            }

            yield $class;
        }
    }

    /**
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    private function endsWith(string $haystack, string $needle): bool
    {
        $length = strlen($needle);
        if (!$length) {
            return true;
        }
        return substr($haystack, -$length) === $needle;
    }

    /**
     * @param string $dir
     * @return $this
     */
    public static function create(string $dir): self
    {
        return new self($dir);
    }
}
