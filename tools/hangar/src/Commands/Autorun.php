<?php
declare(strict_types=1);
namespace Airship\Hangar\Commands;

use Airship\Hangar\SessionCommand;
use ParagonIE\ConstantTime\Base64;

class autoRun extends SessionCommand
{
    public $essential = false;
    public $display = 4;
    public $name = 'Add autoRun Script';
    public $description = 'Add scripts to run after the update has complete.';

    /**
     * Fire the add command to add files and directories to this update pack.
     *
     * @param array $args
     * @return bool
     */
    public function fire(array $args = []): bool
    {
        try {
            $this->getSession();
            $dir = $this->session['dir'] . $this->findRelativeDir();
        } catch (\Error $e) {
            echo $e->getMessage(), "\n";
            return false;
        }

        if (\count($args) === 0) {
            echo 'No file passed.', "\n";
            return false;
        }
        if (!\array_key_exists('autoRun', $this->session)) {
            // echo 'Creating session data', "\n";
            $this->session['autoRun'] = [];
        }

        $added = 0;
        foreach ($args as $file) {
            $l = \strlen($file) - 1;
            if ($file[$l] === DIRECTORY_SEPARATOR) {
                $file = substr($file, 0, -1);
            }
            $added += $this->addautoRun($file, $dir);
        }
        echo $added, ' autoRun script', ($added === 1 ? '' : 's'), ' registered.', "\n";
        return true;
    }

    /**
     * Add a file or directory to the
     *
     * @param string $filename
     * @param string $dir
     * @return int
     */
    protected function addautoRun(string $filename, string $dir): int
    {
        if (!empty($dir)) {
            if ($dir[\strlen($dir) - 1] !== DIRECTORY_SEPARATOR) {
                $dir .= DIRECTORY_SEPARATOR;
            }
        }
        if (!\file_exists($filename)) {
            echo $this->c['red'], 'File not found: ', $this->c[''], $filename, "\n";
            return 0;
        }

        try {
            $path = $this->getRealPath(\realpath($filename));
        } catch (\Error $e) {
            echo $this->c['red'], $e->getMessage(), $this->c[''], "\n";
            return 0;
        }

        if (\array_key_exists($path, $this->session['autoRun'])) {
            echo $this->c['yellow'], 'autoRun script already registered: ', $this->c[''], $path, "\n";
            return 0;
        }

        // Recursive adding
        if (\is_dir($path)) {
            echo $this->c['red'], 'You cannot add a directory to an autoRun script: ', $this->c[''], $path, "\n";
            return 0;
        }
        $this->session['autoRun'][$path] = [
            'type' => $this->getType($path),
            'data' => Base64::encode(\file_get_contents($path))
        ];
        echo $this->c['green'], 'autoRun script registered: ', $this->c[''], $path, "\n";
        return 1;
    }

    /**
     * Get information about a script
     *
     * @param string $path
     * @return string
     * @throws \Error
     */
    protected function getType(string $path): string
    {
        if (\preg_match('#/(.+?)\.([^\\.]+)$#', $path, $matches)) {
            $t = \strtolower($matches[2]);
            switch ($t) {
                case 'php':
                case 'php3':
                case 'phtml':
                case 'inc':
                    return 'php';
                case 'mysql':
                    return 'mysql';
                case 'pgsql':
                    return 'pgsql';
                case 'sh':
                    return 'sh';
                default:
                    throw new \Error('Unknown script type: '.$t);
            }
        }
        throw new \Error('Unknown script type');
    }
}
