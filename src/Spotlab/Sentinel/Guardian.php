<?php

namespace Spotlab\Sentinel;

use Symfony\Component\Yaml\Yaml;

class Guardian
{
    protected $config;

    public function __construct($config_path)
    {
        if (file_exists($config_path)) {
            $this->config = Yaml::parse($config_path);
        } else {
            throw new \Exception('Config file does not exists', 0);
        }
    }

    /**
     * @return array $return
     */
    public function getWebsites()
    {
        // Return array
        $return = $this->config['websites'];

        // Exception if not defined
        if (empty($return)) {
            throw new \Exception('No "project" to backup find on config file', 0);
        }

        return $return;
    }
}
