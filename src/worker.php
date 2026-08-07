<?php

/**
 * This file is part of Temporal package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

// Include Composer's autoloader
require __DIR__.'/vendor/autoload.php';

use App\Temporal\DeclarationLocator;
use Laravel\Octane\ApplicationFactory;
use Temporal\Interceptor\SimplePipelineProvider;
use Temporal\WorkerFactory;

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');
ini_set('error_log', 'php://stderr');

// Log startup
error_log('Starting Temporal worker...');

try {
    // finds all available workflows, activity types and commands in a given directory
    $declarations = new DeclarationLocator(__DIR__.'/app/Temporal/');

    // Convert generators to arrays to avoid multiple iterations
    $workflowTypes = iterator_to_array($declarations->getWorkflowTypes());
    $activityTypes = iterator_to_array($declarations->getActivityTypes());

    error_log('Found '.count($workflowTypes).' workflow types');
    error_log('Found '.count($activityTypes).' activity types');

    // factory initiates and runs task queue specific activity and workflow workers
    $factory = WorkerFactory::create();
    $worker = $factory->newWorker(interceptorProvider: new SimplePipelineProvider([]));

    // Register workflows
    $registeredWorkflows = [];
    foreach ($workflowTypes as $workflowType) {
        if (in_array($workflowType, $registeredWorkflows)) {
            error_log("[WARN] Duplicate workflow found: $workflowType");

            continue;
        }

        error_log("[INFO] Registering workflow: $workflowType");
        $registeredWorkflows[] = $workflowType;
        $worker->registerWorkflowTypes($workflowType);
    }

    // Initialize Laravel application
    error_log('Initializing Laravel application...');
    $basePath = require '/app/vendor/laravel/octane/bin/bootstrap.php';
    $appFactory = new ApplicationFactory($basePath);
    $app = $appFactory->createApplication();

    // Register activities
    $registeredActivities = [];
    foreach ($activityTypes as $activityType) {
        if (in_array($activityType, $registeredActivities)) {
            error_log("[WARN] Duplicate activity found: $activityType");

            continue;
        }
        error_log("[INFO] Registering activity: $activityType");
        $registeredActivities[] = $activityType;
        $worker->registerActivity($activityType);
    }

    error_log('Starting worker...');
    $factory->run();
    error_log('Worker started successfully');
} catch (\Throwable $e) {
    error_log('FATAL ERROR: '.$e->getMessage());
    error_log('Stack trace: '.$e->getTraceAsString());
    throw $e;
}
