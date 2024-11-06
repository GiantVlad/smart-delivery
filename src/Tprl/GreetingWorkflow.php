<?php

/**
 * This file is part of Temporal package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tprl;

use Carbon\CarbonInterval;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\RetryOptions;
use Temporal\Exception\IllegalStateException;
use Temporal\Workflow;


// @@@SNIPSTART php-hello-workflow
class GreetingWorkflow implements GreetingWorkflowInterface
{
    private $greetingActivity;

    public function __construct()
    {
        /**
         * Activity stub implements activity interface and proxies calls to it to Temporal activity
         * invocations. Because activities are reentrant, only a single stub can be used for multiple
         * activity invocations.
         */
        $this->greetingActivity = Workflow::newActivityStub(
            GreetingActivityInterface::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(CarbonInterval::seconds(20))
                ->withRetryOptions(
                    RetryOptions::new()
                        ->withInitialInterval(CarbonInterval::seconds(1))
                        ->withMaximumAttempts(5)
                        ->withNonRetryableExceptions([\InvalidArgumentException::class])
                )
        );

        $this->completionActivity = Workflow::newActivityStub(
            AsyncActivityCompletionInterface::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(CarbonInterval::seconds(2))
        );
    }

    public function greet(string $name): \Generator
    {
        $completion = $this->completionActivity->composeCompletion($name);
        $hello = $this->greetingActivity->composeGreeting('Hello', $name);
        $bye = $this->greetingActivity->composeGreeting('Bye', $name);

        return (yield $completion) . (yield $hello) . "\n" . (yield $bye);
    }
}
// @@@SNIPEND

