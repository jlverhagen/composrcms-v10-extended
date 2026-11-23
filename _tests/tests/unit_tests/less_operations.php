<?php

/**
 * Check LESS operations that use different operand node types.
 */
class less_operations_test_set extends cms_test_case
{
    public function testArithmeticOperands()
    {
        require_once get_file_base() . '/sources_custom/ILess/Autoloader.php';
        ILess_Autoloader::register();

        $diagnostics = array();
        set_error_handler(function ($severity, $message) use (&$diagnostics) {
            if ((error_reporting() & $severity) !== 0) {
                $diagnostics[] = $message;
            }
            return true;
        }, E_DEPRECATED | E_WARNING);

        try {
            $parser = new ILess_Parser();
            $parser->parseString('@x: 4px; .a { width: (3px + 2px); top: (-@x); color: (#101010 + #020202); background: lighten(#336699, 10%); } @media screen { .a:extend(.b) {} .b { color: blue; } } @supports (display: grid) { .c { color: red; } }');
            $css = $parser->getCSS();
            $color = new ILess_Color(array('64.25', '191.75', '45.125'), '0.5');
            $argb = $color->toARGB();
        } finally {
            restore_error_handler();
        }

        $this->assertTrue(strpos($css, 'width: 5px') !== false, 'LESS dimension addition');
        $this->assertTrue(strpos($css, 'top: -4px') !== false, 'LESS negative variable');
        $this->assertTrue(strpos($css, 'color: #121212') !== false, 'LESS color addition');
        $this->assertTrue(strpos($css, '@media screen') !== false && strpos($css, '.b,') !== false, 'LESS media and selector extension');
        $this->assertTrue(strpos($css, '@supports (display: grid)') !== false, 'LESS directive');
        $this->assertTrue($argb === '#8040c02d', 'LESS ARGB conversion');
        $this->assertTrue($diagnostics === array(), 'LESS emitted warnings or deprecations: ' . implode('; ', $diagnostics));
    }
}
