<?php
/**
 * Focused assertion-handler checks without client headers or global registration.
 *
 * @license http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @package testing_platform
 */
class firephp_assertion_compatibility_test_set extends cms_test_case
{
    public function testAssertionCallback()
    {
        require_code('firephp');
        $logger = new FirePHPAssertionCompatibilityLogger();
        set_error_handler(function ($severity, $message, $file, $line) {
            if (error_reporting() & $severity) {
                throw new ErrorException($message, 0, $severity, $file, $line);
            }
            return true;
        });
        try {
            $logger->configureAssertions(true, false);
            $logger->assertionHandler('/test/assertion.php', 123, 'expected condition');
            $error = $logger->messages[0][0];
            $this->assertTrue($error instanceof ErrorException, 'Converted assertions still log ErrorException');
            $this->assertTrue($error->getSeverity() === 0, 'Preserve the former null-to-zero severity');
            $this->assertTrue($error->getMessage() === 'Assertion Failed - Code[ expected condition ]', 'Preserve the assertion message');
            $this->assertTrue($error->getFile() === '/test/assertion.php' && $error->getLine() === 123, 'Preserve assertion source location');

            $logger->configureAssertions(true, true);
            $thrown = null;
            try {
                $logger->assertionHandler('/test/thrown.php', 456, null);
            } catch (ErrorException $exception) {
                $thrown = $exception;
            }
            $this->assertTrue($thrown !== null && $thrown->getSeverity() === 0, 'Throw mode must still throw the converted exception');
            $this->assertTrue($thrown->getFile() === '/test/thrown.php' && $thrown->getLine() === 456, 'Throw mode preserves source location');
            $this->assertTrue(count($logger->messages) === 1, 'Throw mode must not also log');

            $logger->configureAssertions(false, false);
            $logger->assertionHandler('/test/raw.php', 789, 'raw condition');
            $this->assertTrue($logger->messages[1] === array('raw condition', 'Assertion Failed', FirePHP::ERROR, array('File' => '/test/raw.php', 'Line' => 789)), 'Raw assertion logging remains unchanged');
        } finally {
            restore_error_handler();
        }
    }
}

// No browser extension, headers, session or database is needed for these checks.
require_once dirname(__DIR__, 3) . '/sources/firephp.php';
class FirePHPAssertionCompatibilityLogger extends FirePHP
{
    public $messages = array();

    public function configureAssertions($convert, $throw)
    {
        $this->convertAssertionErrorsToExceptions = $convert;
        $this->throwAssertionExceptions = $throw;
    }

    public function fb($object)
    {
        $this->messages[] = func_get_args();
        return true;
    }
}
