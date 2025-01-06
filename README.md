# LoggerHelper
## Introduction

`LoggerHelper` is a versatile logging utility designed for quick and efficient message logging in PHP applications. It implements the **Singleton** and **Strategy** design patterns, allowing seamless integration with various logging strategies such as **Monolog**, **Laravel's Logger**, or a **custom file-based logger**. This flexibility makes it suitable for migrating legacy systems to modern PHP versions while maintaining consistent logging behavior.

> [!IMPORTANT]
> The `LoggerHelper` code provided in this repository is **not part of any vendor package** and should **not be considered production-ready**. It was originally used in a legacy system that has been migrated to the latest PHP version. Therefore, its usage is **specific to certain scenarios** and may require further customization to fit into modern applications. Use this code at your own discretion and ensure thorough testing before integrating it into critical projects.


## Features

- **Singleton Pattern**: Ensures a single instance of the logger throughout the application lifecycle.
- **Strategy Pattern**: Easily switch between different logging strategies (Monolog, Laravel, Custom File Logger).
- **Flexible Logging Types**: Supports multiple log formatting types (`PRINT`, `DUMP`, `EXPORT`).
- **Thread-Safe File Operations**: Implements file locking to prevent concurrent write issues.
- **Dynamic Strategy Switching**: Change logging strategies at runtime based on application needs.
- **Comprehensive Error Handling**: Robust exception handling with fallback mechanisms.

## Installation

1. **Clone the Repository**:
    ```bash
    git clone https://github.com/your-repo/loggerhelper.git
    ```

2. **Navigate to the Project Directory**:
    ```bash
    cd loggerhelper
    ```

3. **Install Dependencies**:
    Ensure you have [Composer](https://getcomposer.org/) installed, then run:
    ```bash
    composer install
    ```

## Usage

### 1. Using `FileLoggerStrategy` (Default)

```php
<?php
require 'vendor/autoload.php';

use Helpers\Logging\LoggerHelper;
use Helpers\Logging\Strategies\FileLoggerStrategy;
use LogType;

// Initialize the LoggerHelper singleton
$logger = LoggerHelper::getInstance(topic: 'file_app', module: 'FileModule');

// Set the logging strategy to FileLoggerStrategy
$logFile = __DIR__ . '/logs/custom_file_app.log';
$fileLoggerStrategy = new FileLoggerStrategy($logFile, 'FileModule', LogType::EXPORT->value);
$logger->setStrategy($fileLoggerStrategy);

// Set the log type (optional)
$logger->setLogType(LogType::EXPORT);

// Log a message without clearing the log file
$data = ['order' => ['id' => 123, 'status' => 'processed']];
$logger->log($data);

// Log a message and clear the log file before writing
$logger->log(['new_order' => ['id' => 124, 'status' => 'pending']], clearBefore: true);
```

### 2. Using `MonologStrategy`

```php
<?php
require 'vendor/autoload.php';

use Helpers\Logging\LoggerHelper;
use Helpers\Logging\Strategies\MonologStrategy;
use LogType;
use Monolog\Logger;

// Initialize the LoggerHelper singleton
$logger = LoggerHelper::getInstance(topic: 'application', module: 'UserModule');

// Set the logging strategy to Monolog
$logFile = __DIR__ . '/logs/monolog_app.log';
$monologStrategy = new MonologStrategy($logFile, 'my_app_channel', Logger::INFO);
$logger->setStrategy($monologStrategy);

// Set the log type (optional)
$logger->setLogType(LogType::PRINT);

// Log a message without clearing the log file
$logger->log("This is a message logged using Monolog.");

// Log a message and clear the log file before writing
$logger->log("This log entry will clear the existing log file.", clearBefore: true);
```

### 3. Using `LaravelStrategy`

```php
<?php

namespace App\Http\Controllers;

use Helpers\Logging\LoggerHelper;
use Helpers\Logging\Strategies\LaravelStrategy;
use LogType;
use Illuminate\Http\Request;

class ExampleController extends Controller
{
    public function index()
    {
        // Initialize the LoggerHelper singleton
        $logger = LoggerHelper::getInstance(topic: 'laravel_app', module: 'ExampleController');

        // Set the logging strategy to Laravel's logger
        $laravelStrategy = new LaravelStrategy();
        $logger->setStrategy($laravelStrategy);

        // Set the log type (optional)
        $logger->setLogType(LogType::DUMP);

        // Log a message without clearing logs (clearBefore is ignored for LaravelStrategy)
        $data = ['user' => 'John Doe', 'action' => 'login'];
        $logger->log("User action logged.", $data);

        // Attempt to log a message with clearBefore (has no effect for LaravelStrategy)
        $logger->log("This log entry attempts to clear logs.", ['topic' => 'laravel_app'], true);

        return view('welcome');
    }
}
```

## Additional Information

### Changing Logging Strategies at Runtime

You can dynamically switch between different logging strategies based on your application's needs:

```php
<?php
use Helpers\Logging\LoggerHelper;
use Helpers\Logging\Strategies\MonologStrategy;
use Helpers\Logging\Strategies\LaravelStrategy;
use Helpers\Logging\Strategies\FileLoggerStrategy;
use LogType;

// Initialize the LoggerHelper singleton
$logger = LoggerHelper::getInstance(topic: 'dynamic_app', module: 'DynamicModule');

// Set to MonologStrategy
$monologStrategy = new MonologStrategy(__DIR__ . '/logs/dynamic_monolog.log', 'dynamic_channel', Logger::DEBUG);
$logger->setStrategy($monologStrategy);
$logger->log("Logging with Monolog.");

// Switch to LaravelStrategy
$laravelStrategy = new LaravelStrategy();
$logger->setStrategy($laravelStrategy);
$logger->log("Logging with Laravel.");

// Switch to FileLoggerStrategy
$fileLoggerStrategy = new FileLoggerStrategy(__DIR__ . '/logs/dynamic_file.log', 'DynamicModule', LogType::DUMP->value);
$logger->setStrategy($fileLoggerStrategy);
$logger->log(['dynamic' => 'file logger'], clearBefore: true);
```

### Extending with New Logging Strategies

To add a new logging strategy, follow these steps:

1. **Create a New Strategy Class**:
    ```php
    <?php
    // File: src/Logging/Strategies/NewStrategy.php

    namespace Helpers\Logging\Strategies;

    use Exception;

    class NewStrategy implements LoggerStrategyInterface
    {
        public function log(string $message, array $context = [], bool $clearBefore = false): bool
        {
            try {
                // Implement your logging logic here
                // For example, sending logs to an external API
                return true;
            } catch (Exception $e) {
                error_log("NewStrategy Error: " . $e->getMessage());
                return false;
            }
        }
    }
    ```

2. **Use the New Strategy**:
    ```php
    <?php
    use Helpers\Logging\LoggerHelper;
    use Helpers\Logging\Strategies\NewStrategy;
    use LogType;

    $logger = LoggerHelper::getInstance(topic: 'new_strategy_app', module: 'NewModule');
    $newStrategy = new NewStrategy();
    $logger->setStrategy($newStrategy);
    $logger->setLogType(LogType::PRINT);
    $logger->log("Logging with the new strategy.");
    ```

## License

This project is licensed under the [MIT License](LICENSE).
