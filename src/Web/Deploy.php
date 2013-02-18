<?php

/**
 * Post-Receive Hooks API
 *
 * @author      appleboy
 * @copyright   2012 appleboy
 * @link        https://github.com/appleboy/PHP-Git-Deploy
 * @package     PHP Git Deploy
 */
namespace Web;

class Deploy
{

    public $config = array();

    public function __construct()
    {
        // include config file
        include('config.php');
        $this->config = $config;
    }

    public function set_config($config = array())
    {
        $this->config = $config;
    }

    public function index()
    {
        if (!is_array($this->config) or !isset($this->config['github'])
            or !isset($this->config['git_path']) or !is_array($this->config['github'])) {
            return false;
        }

        if (!isset($_POST['payload']) or empty($_POST['payload'])) {
            return false;
        }

        // check git config
        (!isset($git_config)) and $git_config = $this->config['github'];
        (!isset($git_path)) and $git_path = $this->config['git_path'];

        $payload = json_decode($_POST['payload']);

        foreach ($git_config as $key => $val) {
            // check repository name exist in config
            $repository_name = strtolower($payload->repository->name);
            if ($repository_name != strtolower($key)) {
                continue;
            }

            foreach ($val as $k => $v) {
                // check if match payload ref branch
                $head = 'refs/heads/' . $k;
                if ($payload->ref != $head) {
                    continue;
                }

                // git reset head and pull origin branch
                if (isset($v['base_path']) and !empty($v['base_path'])) {
                    $base_path = realpath($v['base_path']) . '/';

                    $shell = sprintf('%s --git-dir="%s.git" --work-tree="%s" reset --hard HEAD',
                        $git_path, $base_path, $base_path);
                    $output = shell_exec(escapeshellcmd($shell));

                    $shell = sprintf('%s --git-dir="%s.git" --work-tree="%s" clean -f',
                        $git_path, $base_path, $base_path);
                    $output = shell_exec(escapeshellcmd($shell));

                    $shell = sprintf('%s --git-dir="%s.git" --work-tree="%s" pull origin %s',
                        $git_path, $base_path, $base_path, $k);
                    $output = shell_exec(escapeshellcmd($shell));
                }
            }
        }
        return true;
    }
}
