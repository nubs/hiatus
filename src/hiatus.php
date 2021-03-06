<?php
namespace Hiatus;

/**
 * Adds the given arguments to the command escaping them as necessary.
 *
 * @param string $command The shell command to execute.  This can contain arguments, but make sure to use PHP's escapeshellarg for any arguments
 *     supplied by the user.
 * @param array $arguments The arguments to pass to the command.  These will be passed through PHP's escapeshellarg function so pass the
 *     arguments unescaped.  If a key in the array is not numeric, then it will be included as well in a KEY=VALUE format.
 * @return string The command with the arguments added and escaped.
 */
function addArguments($command, array $arguments)
{
    foreach ($arguments as $key => $argument) {
        if (is_numeric($key)) {
            $command .= ' ' . escapeshellarg($argument);
        } else {
            $command .= ' ' . escapeshellarg($key) . '=' . escapeshellarg($argument);
        }
    }

    return $command;
}

/**
 * Executes the command with the given arguments.  If a timeout is given (in seconds), the command will be terminated if it takes longer.
 *
 * @param string $command The shell command to execute.  This can contain arguments, but make sure to use PHP's escapeshellarg for any arguments
 *     supplied by the user.
 * @param array $arguments The arguments to pass to the command.  These will be passed through PHP's escapeshellarg function so pass the
 *     arguments unescaped.  If a key in the array is not numeric, then it will be included as well in a KEY=VALUE format.
 * @param float $timeout If given, this will terminate the command if it does not finish before the timeout expires.
 * @param string $stdin A string to pass to the command on stdin.
 * @return array A 3-member array is returned.
 *     * int The exit code of the command.
 *     * string The output of the command.
 *     * string The stderr output of the command.
 */
function exec($command, array $arguments = [], $timeout = null, $stdin = null)
{
    $command = addArguments($command, $arguments);
    $pipes = null;
    $pipeSpec = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    if ($stdin !== null) {
        $pipeSpec[0] = ['pipe', 'r'];
    }

    $process = proc_open($command, $pipeSpec, $pipes);
    if ($process === false) {
        throw new \Exception("Error executing command '{$command}' with proc_open.");
    }

    if ($stdin !== null) {
        fwrite($pipes[0], $stdin);
        fclose($pipes[0]);
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

/**
 * Executes the command just like \Hiatus\exec(), but if the exit code is not 0 then an exception is raised.
 *
 * @param string $command The shell command to execute.  This can contain arguments, but make sure to use PHP's escapeshellarg for any arguments
 *     supplied by the user.
 * @param array $arguments The arguments to pass to the command.  These will be passed through PHP's escapeshellarg function so pass the
 *     arguments unescaped.  If a key in the array is not numeric, then it will be included as well in a KEY=VALUE format.
 * @param float $timeout If given, this will terminate the command if it does not finish before the timeout expires.
 * @param string $stdin A string to pass to the command on stdin.
 * @return array A 2-member array is returned.
 *     * string The output of the command.
 *     * string The stderr output of the command.
 */
function execX($command, array $arguments = [], $timeout = null, $stdin = null)
{
    list($exitCode, $stdout, $stderr) = exec($command, $arguments, $timeout, $stdin);

    if ($exitCode !== 0) {
        throw new \Exception("Failed to execute command '" . addArguments($command, $arguments) . "'. Exited with code {$exitCode}.");
    }

    return [$stdout, $stderr];
}
