<?php

namespace Spotlab\Sentinel\Services;

use Symfony\Component\Yaml\Yaml;

class ConfigProvider
{
    protected $config;
    protected $projects;

    public function __construct()
    {
        $config_file = __DIR__ . '/../../../../config/index.yml';

        // Analysing config file
        if (file_exists($config_file)) {
            $this->config = Yaml::parse($config_file);
            $this->projects = $this->findProjects($this->config);
        } else {
            throw new \Exception('Config file does not exists', 0);
        }
    }



    /**
     * @return array $return
     */
    public function getProjects()
    {
        if(!empty($this->projects)) {
            return $this->projects;
        } else {
            throw new \Exception('No projects find on config file', 0);
        }
    }

    /**
     * @return array $return
     */
    private function findProjects($config, $parent = '')
    {
        $return = array();
        $parent = (empty($parent)) ? $parent : $parent . '_';

        foreach ($config as $key => $value) {
            if ($key == 'projects') {
                foreach ($value as $name => $project) {
                    if (!isset($project['projects'])) {
                        if(file_exists($project['requests'])) {
                            $return[$parent.$name]['title'] = $project['title'];
                            $return[$parent.$name] += Yaml::parse($project['requests']);
                        } else {
                            throw new \Exception('Requests file "'. $project['requests'] .'" does not exists', 0);
                        }
                    } else {
                        $return += $this->findProjects($project, $name);
                    }
                }
            }
        }

        return $return;
    }
}
