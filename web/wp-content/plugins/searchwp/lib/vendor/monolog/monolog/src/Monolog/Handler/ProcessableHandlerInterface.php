<?php

declare (strict_types=1);
/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SearchWP\Dependencies\Monolog\Handler;

use SearchWP\Dependencies\Monolog\Processor\ProcessorInterface;
/**
 * Interface to describe loggers that have processors
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
interface ProcessableHandlerInterface
{
    /**
     * Adds a processor in the stack.
     *
     * @psalm-param ProcessorInterface|callable(array): array $callback
     *
     * @param  ProcessorInterface|callable $callback
     * @return HandlerInterface            self
     */
    public function pushProcessor(callable $callback) : \SearchWP\Dependencies\Monolog\Handler\HandlerInterface;
    /**
     * Removes the processor on top of the stack and returns it.
     *
     * @psalm-return callable(array): array
     *
     * @throws \LogicException In case the processor stack is empty
     * @return callable
     */
    public function popProcessor() : callable;
}
