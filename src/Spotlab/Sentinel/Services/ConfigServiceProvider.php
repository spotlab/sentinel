<?php

namespace Spotlab\Sentinel\Services;

use Symfony\Component\Yaml\Yaml;

class ConfigServiceProvider
{
    protected $config;
    protected $projects;

    public function __construct()
    {
        $config_dir = __DIR__ . '/../../../../config/';
        $config_file = $config_dir . 'index.yml';

        // Analysing config file
        if (file_exists($config_file)) {
            $this->config = Yaml::parse($config_file);
            $this->projects = $this->extractProjectsRecursive($this->config, $config_dir);
        } else {
            throw new \Exception('Config file does not exists', 0);
        }
    }

    /**
     * @return array $return
     */
    public function getConfig()
    {
        if(!empty($this->config)) {
            return $this->config;
        } else {
            throw new \Exception('No config found', 0);
        }
    }

    /**
     * @return array $return
     */
    public function getParameters()
    {
        if(!empty($this->config['parameters'])) {
            return $this->config['parameters'];
        } else {
            throw new \Exception('No parameters found on config file', 0);
        }
    }

    /**
     * @return array $return
     */
    public function getProjects($flat = false)
    {
        if(!empty($this->config['projects']) && !$flat) {
            return $this->config['projects'];
        } else if(!empty($this->projects) && $flat) {
            return $this->projects;
        } else {
            throw new \Exception('No projects found on config file', 0);
        }
    }

    /**
     * @return array $return
     */
    public function getSeries()
    {
        $series = array();
        $projects = $this->projects;
        foreach ($projects as $project_name => $project) {
            foreach ($project['series'] as $serie_name => $serie) {
                $series[] = $project_name . '_' . $serie_name;
            }
        }

        if(empty($series)) {
            throw new \Exception('No series found on config file', 0);
        }

        return $series;
    }

    /**
     * @return array $return
     */
    private function extractProjectsRecursive($config, $config_dir, $parent = '')
    {
        $return = array();
        $parent = (empty($parent)) ? $parent : $parent . '_';

        foreach ($config as $key => $value) {
            if ($key == 'projects') {
                foreach ($value as $name => $project) {
                    if (!isset($project['projects'])) {
                        $serie_file = $config_dir . $project['series'];
                        if(file_exists($serie_file)) {
                            $return[$parent.$name]['title'] = $project['title'];
                            $return[$parent.$name] += Yaml::parse($serie_file);
                        } else {
                            throw new \Exception('Series file "'. $serie_file .'" does not exists', 0);
                        }
                    } else {
                        $return += $this->extractProjectsRecursive($project, $config_dir, $name);
                    }
                }
            }
        }

        return $return;
    }
}
