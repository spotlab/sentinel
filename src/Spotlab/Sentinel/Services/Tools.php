<?php

namespace Spotlab\Sentinel\Services;

use Symfony\Component\Yaml\Yaml;
use Spotlab\Sentinel\Database\SQLiteDatabase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Console\Output\OutputInterface;

class Tools
{
    protected $db;
    protected $config;
    protected $output;
    protected $projects;

    public function __construct($config, OutputInterface $output)
    {
        // Make OutputInterface globale
        $this->output = $output;

        // Create Database
        $this->db = new SQLiteDatabase();
        $this->db->init();
        $output->writeln('INIT : <comment>SQLiteDatabase "sentinel"</comment>');

        // Analysing config file
        if ($config && file_exists($config)) {
            $this->config = Yaml::parse($config);
            $output->writeln(sprintf('ANALYSING : <comment>%s</comment>', $config));

            $this->projects = $this->findProjects($this->config);
            $output->writeln(sprintf('FIND : <comment>%s projects</comment>', count($this->projects)));
        } else {
            throw new \Exception('Config file does not exists', 0);
        }
    }

    /**
     * @return array $return
     */
    public function execute()
    {
        // Create Guzzle CLient
        $client = new Client();

        // Actions for every projects in config
        $projects = $this->getProjects();

        // Register time
        $now = time();

        foreach ($projects as $project_name => $project) {
            //$this->output->writeln(sprintf('> Start project <info>%s</info>', $project_name));
            foreach ($project['requests'] as $request_name => $request) {
                $serie_name = $project_name . '_' . $request_name;
                //$this->output->writeln(sprintf('> Start serie <info>%s</info>', $serie_name));

                // Get Options
                $options = array();
                $options['future'] = true;
                $options['allow_redirects'] = true;
                $options['timeout'] = 30;

                // Get Request params
                if(!empty($request['headers'])) $options['headers'] = $request['headers'];
                if(!empty($request['body'])) $options['body'] = $request['body'];

                // Init Ping Object
                $ping = array();
                $ping['project'] = $project_name;
                $ping['serie'] = $request_name;
                $ping['ping_date'] = $now;
                
                // Start Guzzle Requests
                $time_start = microtime(true);
                $req = $client->createRequest($request['method'], $request['url'], $options);
                $client->send($req)->then(
                    function ($response) use ($ping, $serie_name, $time_start) {
                        $time_end = microtime(true);
                        $time = $time_end - $time_start;

                        // Update Ping Data
                        $ping['ping_time'] = $time;
                        $ping['http_status'] = $response->getStatusCode();
                        $ping['error'] = false;

                        // Insert Ping on Database
                        $this->db->insert($ping);
                        $this->output->writeln(sprintf('SUCCESS %s <info>%s</info>', $serie_name, $time));
                    },
                    function ($error) use ($ping, $serie_name, $time_start)  {
                        $time_end = microtime(true);
                        $time = $time_end - $time_start;

                        // Update Ping Data
                        $ping['ping_time'] = $time;
                        $ping['error'] = true;
                        $ping['error_log'] = $error->getMessage();

                        // Insert Ping on Database
                        $this->db->insert($ping);
                        $this->output->writeln(sprintf('FAILED %s <error>%s</error>', $serie_name, $error->getMessage()));
                        throw $error;
                    }
                );
            }
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

    /**
     * @return array $return
     */
    public function getData()
    {
        // Return array
        $return = array();

        if (file_exists($this->data_file)) {
            $return = json_decode(file_get_contents($this->data_file), true);
            $return = $return['config'];
        }

        return $return;
    }

    /**
     * @return array $return
     */
    public function setData($data = array())
    {
        // If ping every minute
        // I want to save 1 week
        // So 60 * 24 * 7 = 10080
        $clean_data = array();
        $alarm_data = array();
        foreach ($data as $website => $val) {
            $clean_data[$website] = array_slice($val, -10080, 10080, true);
            $alarm_data[$website] = array_slice($val, -3, 3);
        }

        // Average calcul
        $calc = array();
        foreach ($alarm_data as $website => $entry) {
            foreach ($entry as $key => $val) {
                $calc[] = $val['total_time'];
                $status[] = $val['http_code'];
            }
        }
        $average = round(array_sum($calc) / count($calc) * 100) / 100;
        $status = max($status);

        // Formated date
        $format_data = array();
        $format_data['status'] = $status;
        $format_data['average'] = $average;
        $format_data['config'] = $clean_data;

        file_put_contents($this->data_file, json_encode($format_data));
    }
}
