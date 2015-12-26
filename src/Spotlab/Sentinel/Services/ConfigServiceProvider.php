<?php

namespace Spotlab\Sentinel\Services;

use Symfony\Component\Yaml\Yaml;

class ConfigServiceProvider
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
            throw new \Exception('No projects found on config file', 0);
        }
    }

    /**
     * @return array $return
     */
    public function getSeries()
    {
        $series = array();
        $projects = $this->getProjects();
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
    private function findProjects($config, $parent = '')
    {
        $return = array();
        $parent = (empty($parent)) ? $parent : $parent . '_';

        foreach ($config as $key => $value) {
            if ($key == 'projects') {
                foreach ($value as $name => $project) {
                    if (!isset($project['projects'])) {
                        if(file_exists($project['series'])) {
                            $return[$parent.$name]['title'] = $project['title'];
                            $return[$parent.$name] += Yaml::parse($project['series']);
                        } else {
                            throw new \Exception('Series file "'. $project['series'] .'" does not exists', 0);
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
