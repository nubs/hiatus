<?php
namespace Hiatus;

/**
 * Executes the command with the given arguments.  If a timeout is given (in seconds), the command will be terminated if it takes longer.
 *
 * @param string $command The shell command to execute.  This can contain arguments, but make sure to use PHP's escapeshellarg for any arguments
 *     supplied by the user.
 * @param array $arguments The arguments to pass to the command.  These will be passed through PHP's escapeshellarg function so pass the
 *     arguments unescaped.  If a key in the array is not numeric, then it will be included as well in a KEY=VALUE format.
 * @param float $timeout If given, this will terminate the command if it does not finish before the timeout expires.
 * @return array A 3-member array is returned.
 *     * int The exit code of the command.
 *     * string The output of the command.
 *     * string The stderr output of the command.
 */
function exec($command, array $arguments = [], $timeout = null)
{
    foreach ($arguments as $key => $argument) {
        if (is_numeric($key)) {
            $command .= ' ' . escapeshellarg($argument);
        } else {
            $command .= ' ' . escapeshellarg($key) . '=' . escapeshellarg($argument);
        }
    }

    $pipes = null;
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if ($process === false) {
        throw new \Exception("Error executing command '{$command}' with proc_open.");
    }

    if ($timeout !== null) {
        $timeout *= 1000000;
    }

    stream_set_blocking($pipes[1], 0);
    stream_set_blocking($pipes[2], 0);
    $stdout = '';
    $stderr = '';
    $exitCode = null;
    while ($timeout === null || $timeout > 0) {
        $start = microtime(true);

        $read = [$pipes[1], $pipes[2]];
        $other = [];
        stream_select($read, $other, $other, 0, $timeout);

        $status = proc_get_status($process);

        $stdout .= stream_get_contents($pipes[1]);
        $stderr .= stream_get_contents($pipes[2]);

        if (!$status['running']) {
            $exitCode = $status['exitcode'];
            break;
        }

        if ($timeout !== null) {
            $timeout -= (microtime(true) - $start) * 1000000;
        }
    }

    proc_terminate($process, 9);
    $closeStatus = proc_close($process);
    if ($exitCode === null) {
        $exitCode = $closeStatus;
    }

    return [$exitCode, $stdout, $stderr];
}
