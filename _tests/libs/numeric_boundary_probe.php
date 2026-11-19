<?php
/** CLI-only LESS numeric regressions. Usage: php -n numeric_boundary_probe.php SOURCE [--baseline] */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$source = isset($argv[1]) ? realpath($argv[1]) : false;
$baseline = ($argv[2] ?? '') === '--baseline';
if (!$source || $argc < 2 || $argc > 3 || ($argc === 3 && !$baseline)) { exit(2); }
$diagnostics = array();
$observations = array();
$checks = 0;
set_error_handler(function ($severity, $message, $file, $line) use ($baseline, &$diagnostics) {
    if (!(error_reporting() & $severity)) { return true; }
    if ($baseline && $severity === E_DEPRECATED) {
        $diagnostics[$message] = ($diagnostics[$message] ?? 0) + 1;
        return true;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});
spl_autoload_register(function ($class) use ($source) {
    if (strpos($class, 'ILess_') === 0) {
        require_once $source . '/sources_custom/' . str_replace('_', '/', $class) . '.php';
    }
});
function numeric_result($call)
{
    try { return array('result' => $call()); }
    catch (ArithmeticError $e) { return array('exception' => get_class($e), 'message' => $e->getMessage()); }
}
function numeric_expect($condition, $message)
{
    $GLOBALS['checks']++;
    if (!$condition) { throw new RuntimeException($message); }
}
try {
    numeric_expect(!function_exists('bcpow') && !function_exists('bcmod'), 'Run with -n to exercise float fallback');
    $bases = array('0', '-0', '0.0', '-0.0', '1', '-1', '2', '-2', '0.5', '-0.5', '1e-300', '-1e-300', '1e300', '-1e300');
    $exponents = array('-INF', '-1e300', '-101', '-3', '-2.5', '-2', '-1', '-0.5', '-0', '0', '0.5', '1', '2', '3', '101', '1e300');
    // floatval of the literal string -INF is zero; include true nonfinite values separately.
    $bases = array_merge($bases, array(INF, -INF, NAN));
    $exponents = array_merge($exponents, array(INF, -INF, NAN));
    foreach ($bases as $base) {
        foreach ($exponents as $exponent) {
            $result = ILess_Math::power($base, $exponent);
            numeric_expect(is_string($result), 'Power retains string result');
            $observations['power'][] = $result;
        }
    }
    numeric_expect(ILess_Math::power('-0', '-3') === '-INF', 'Odd negative power retains negative infinity');
    numeric_expect(ILess_Math::power('-0', '-2') === 'INF', 'Even negative power retains positive infinity');
    numeric_expect(ILess_Math::power('0', '-0.5') === 'INF', 'Fractional negative power retains positive infinity');
    $registry = (new ReflectionClass(ILess_FunctionRegistry::class))->newInstanceWithoutConstructor();
    $operands = array('-9.75', '-5', '-2.5', '-1', '-0.5', '-0', '0', '0.5', '1', '2.5', '5', '9.75', '1e30', '-1e30');
    foreach ($operands as $left) {
        foreach ($operands as $right) {
            $result = numeric_result(function () use ($left, $right) { return ILess_Math::modulus($left, $right); });
            numeric_expect(isset($result['exception']) || $result['result'] === null || is_string($result['result']), 'Fallback modulus result shape');
            $observations['modulus'][] = $result;
            $result = numeric_result(function () use ($registry, $left, $right) {
                $dimension = $registry->mod(new ILess_Node_Dimension($left, 'px'), new ILess_Node_Dimension($right));
                return array('value' => $dimension->value, 'unit' => (string)$dimension->unit);
            });
            numeric_expect(isset($result['exception']) || $result['result']['unit'] === 'px', 'Registry modulus retains units or error');
            $observations['registry_modulus'][] = $result;
        }
    }
    numeric_expect(ILess_Math::modulus('4', '2') === null, 'Legacy zero remainder remains null');
    numeric_expect(ILess_Math::modulus('5.75', '2.5') === '1', 'Legacy fractional operands truncate toward zero');
} finally { restore_error_handler(); }
echo json_encode(array('php' => PHP_VERSION, 'checks' => $checks, 'observations' => $observations, 'diagnostics' => $diagnostics), JSON_THROW_ON_ERROR), "\n";
