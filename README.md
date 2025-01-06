## Logger for fast message logging

## Usage

```php
use Backend\Helpers\LoggerHelper;
use Backend\Helpers\LogType;

// Simple log with PRINT type
$logger = new LoggerHelper('application');
$logger->writeToLog('This is a simple message.');

// Log with DUMP type using a static method
LoggerHelper::addToLog(['key' => 'value'], 'debug');

// Logging an array with EXPORT type using a static method
$data = ['user' => ['name' => 'Ivan', 'age' => 30]];
LoggerHelper::arrayToLog($data, 'users');
```
