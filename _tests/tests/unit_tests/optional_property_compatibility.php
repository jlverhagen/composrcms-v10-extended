<?php
/**
 * Optional-library state and inheritance checks without external services.
 *
 * @license http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @package testing_platform
 */
class optional_property_compatibility_test_set extends cms_test_case
{
    public function testPhotobucketState()
    {
        require_once get_file_base() . '/sources_custom/photobucket/PBAPI.php';
        $this->withStrictDiagnostics(function () {
            $client = new class extends PBAPI {
                public $tokens = array();
                public $subdomains = array();
                public function __construct() {}
                public function storeResponse($text) { return $this->_setResponse($text); }
                public function useParser($parser) { $this->response = $parser; }
                public function setOAuthToken($token, $secret, $username = '') {
                    $this->tokens[] = array($token, $secret, $username);
                    return $this;
                }
                public function setSubdomain($subdomain) { $this->subdomains[] = $subdomain; return $this; }
            };
            $client->useParser(new class {
                public function parse($text, $onlycontent) { return array($text, $onlycontent); }
            });
            $this->assertTrue($client->getResponseString() === null, 'Fresh response state remains null');
            $this->assertTrue($client->getParsedResponse() === array('', false), 'Absent response still parses as empty text');
            $this->assertTrue($client->storeResponse("  response\n") === $client, 'Response setter remains fluent');
            $this->assertTrue($client->getResponseString() === "  response\n", 'Raw response retains whitespace');
            $this->assertTrue($client->getParsedResponse(true) === array('response', true), 'Parsed response retains trimming and options');
            $property = new ReflectionProperty(PBAPI::class, 'response_string');
            $this->assertTrue($property->isPublic() && $property->getDeclaringClass()->getName() === PBAPI::class, 'Response property is explicitly declared with existing public access');
            $client->response_string = 'direct';
            $this->assertTrue($client->getResponseString() === 'direct', 'Direct public writes remain supported');
            $client->storeResponse('oauth_token=test&oauth_token_secret=secret&username=alice&subdomain=images');
            $this->assertTrue($client->loadTokenFromResponse() === $client, 'Token loading remains fluent');
            $this->assertTrue($client->tokens === array(array('test', 'secret', 'alice')), 'Token fields retain their values');
            $this->assertTrue($client->subdomains === array('images'), 'Response subdomain is retained');
            $client->storeResponse(null);
            $caught = null;
            try {
                $client->loadTokenFromResponse();
            } catch (PBAPI_Exception $exception) {
                $caught = $exception;
            }
            $this->assertTrue($caught !== null && $caught->getMessage() === 'Token and Token Secret not returned in response', 'Missing tokens retain the existing exception');
            foreach (array('message', 'code') as $name) {
                $inherited = new ReflectionProperty(PBAPI_Exception::class, $name);
                $this->assertTrue($inherited->isProtected() && $inherited->getDeclaringClass()->getName() === Exception::class, 'Exception state is inherited rather than dynamic: ' . $name);
            }
        });
    }

    public function testPrincipalCollectionState()
    {
        $loader = function ($class) {
            if (strpos($class, 'Sabre\\') === 0) {
                $path = get_file_base() . '/sources_custom/sabredav/lib/' . str_replace('\\', '/', $class) . '.php';
                if (is_file($path)) {
                    require_once $path;
                }
            }
        };
        spl_autoload_register($loader);
        try {
            $this->withStrictDiagnostics(function () {
                $backend = new class implements \Sabre\DAVACL\PrincipalBackend\BackendInterface {
                    public $calls = array();
                    public function getPrincipalsByPrefix($prefixPath) { $this->calls[] = $prefixPath; return array(array('uri' => $prefixPath . '/alice')); }
                    public function getPrincipalByPath($path) { $this->calls[] = $path; return array('uri' => $path); }
                    public function updatePrincipal($path, $mutations) {}
                    public function searchPrincipals($prefixPath, array $searchProperties) { $this->calls[] = $prefixPath; return array($prefixPath . '/alice'); }
                    public function getGroupMemberSet($principal) { return array(); }
                    public function getGroupMembership($principal) { return array(); }
                    public function setGroupMemberSet($principal, array $members) {}
                };
                $collection = new \Sabre\DAVACL\PrincipalCollection($backend);
                $this->assertTrue($collection->getName() === 'principals', 'Default prefix is retained');
                $collection = new \Sabre\DAVACL\PrincipalCollection($backend, 'accounts/users');
                $property = new ReflectionProperty($collection, 'principalPrefix');
                $this->assertTrue($property->isPublic() && $property->getDeclaringClass()->getName() === 'Sabre\\DAVACL\\AbstractPrincipalCollection', 'Prefix is declared on the parent with existing public access');
                $this->assertTrue($collection->getName() === 'users', 'Custom prefix name is retained');
                $children = $collection->getChildren();
                $this->assertTrue(count($children) === 1 && $children[0]->getName() === 'alice', 'Principal listing is retained');
                $this->assertTrue($collection->getChild('alice')->getName() === 'alice', 'Principal lookup is retained');
                $this->assertTrue($collection->searchPrincipals(array()) === array('alice'), 'Principal search is retained');
                $this->assertTrue($backend->calls === array('accounts/users', 'accounts/users/alice', 'accounts/users'), 'All backend operations retain the configured prefix');
                $collection->principalPrefix = 'other';
                $this->assertTrue($collection->getName() === 'other', 'Public prefix updates remain supported');
                $collection->disableListing = true;
                $caught = false;
                try {
                    $collection->getChildren();
                } catch (\Sabre\DAV\Exception\MethodNotAllowed $exception) {
                    $caught = true;
                }
                $this->assertTrue($caught, 'Disabled listing retains the existing exception');
                $inherited = new ReflectionProperty(\Sabre\DAV\Exception\LockTokenMatchesRequestUri::class, 'message');
                $this->assertTrue($inherited->isProtected() && $inherited->getDeclaringClass()->getName() === Exception::class, 'Lock exception message is inherited rather than dynamic');
            });
        } finally {
            spl_autoload_unregister($loader);
        }
    }

    public function testLessExceptionDefaults()
    {
        require_once get_file_base() . '/sources_custom/ILess/Exception.php';
        $this->withStrictDiagnostics(function () {
            $this->assertTrue((new ILess_Exception())->getMessage() === '', 'Default exception message remains empty');
            $previous = new Exception('underlying');
            $error = new ILess_Exception(null, null, null, $previous, 7);
            $this->assertTrue($error->getMessage() === ': underlying', 'Null-message chaining retains formatted output');
            $this->assertTrue($error->getPrevious() === $previous && $error->getCode() === 7, 'Exception chain and code are retained');
            $this->assertTrue((new ILess_Exception('explicit'))->getMessage() === 'explicit', 'Explicit messages remain unchanged');
        });
    }

    public function testInheritedDatabaseCaches()
    {
        foreach (array('mysql', 'mysqli', 'mysql_pdo') as $driver) {
            require_code('database/' . $driver);
        }
        $this->withStrictDiagnostics(function () {
            foreach (array('Database_Static_mysql', 'Database_Static_mysqli', 'Database_Static_mysql_pdo') as $class) {
                $object = (new ReflectionClass($class))->newInstanceWithoutConstructor();
                foreach (array('cache_db', 'last_select_db') as $name) {
                    $property = new ReflectionProperty($class, $name);
                    $this->assertTrue($property->isPublic() && $property->getDeclaringClass()->getName() === $class, 'Concrete driver supplies its cache property: ' . $class . ' / ' . $name);
                }
                $object->cache_db = array('placeholder' => true);
                $object->last_select_db = 'placeholder';
                $object->db_close_connections();
                $this->assertTrue($object->cache_db === array() && $object->last_select_db === null, 'Inherited close method resets declared caches without a connection');
            }
        });
    }

    private function withStrictDiagnostics(callable $callback)
    {
        set_error_handler(function ($severity, $message, $file, $line) {
            if (error_reporting() & $severity) {
                throw new ErrorException($message, 0, $severity, $file, $line);
            }
            return true;
        });
        try {
            $callback();
        } finally {
            restore_error_handler();
        }
    }
}
