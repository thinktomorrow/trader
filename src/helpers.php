<?php

if (! function_exists('dd')) {
    function dd()
    {
        var_dump(...func_get_args());
        die();
    }
}

/**
 * Wrapper around the dd helper. This function provides the file from where the
 * dd function has been called, so you won't be in the dark when finding it again.
 */
if (! function_exists('trap')) {
    function trap($var, ...$moreVars): void
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);

        if (php_sapi_name() == 'cli') {
            print_r("\e[1;30m dumped at: " . $trace[0]['file']. ", line: " . $trace[0]['line'] . "\e[40m\n");
        } else {
            print_r("[dumped at: " . $trace[0]['file']. ", line: " . $trace[0]['line'] . "]\n");
        }

        dd($var, ...$moreVars);
    }
}
