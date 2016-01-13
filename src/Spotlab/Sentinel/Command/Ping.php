<?php

namespace Spotlab\Sentinel\Command;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Spotlab\Sentinel\Services\ConfigServiceProvider;
use Spotlab\Sentinel\Services\MongoDatabase;

/**
 * Class Backup
 * @package Spotlab\Sentinel\Command
 */
class Ping extends Command
{
    /**
     * Set us up the command!
     */
    public function configure()
    {
        $this->setName('ping')
             ->setDescription('Ping all websites');
    }

    /**
     * Parses the clover XML file and spits out coverage results to the console.
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        // Create Guzzle CLient
        $client = new Client();

        // Get Projects
        $config = new ConfigServiceProvider();
        $projects = $config->getProjects($flat = true);
        $parameters = $config->getParameters();
        $output->writeln(sprintf('FIND : <comment>%s projects</comment>', count($projects)));

        // Create Database
        $this->db = new MongoDatabase($parameters['mongo']);

        // Register time
        $now = time();

        foreach ($projects as $project_name => $project) {
            foreach ($project['series'] as $serie_name => $serie) {
                // Get Options
                $options = array();
                $options['future'] = true;
                $options['allow_redirects'] = true;
                $options['timeout'] = 30;

                // Get Request params
                if(!empty($serie['headers'])) $options['headers'] = $serie['headers'];
                if(!empty($serie['body'])) $options['body'] = $serie['body'];

                // Init Ping Object
                $ping = array();
                $ping['project'] = $project_name;
                $ping['serie'] = $serie_name;
                $ping['ping_date'] = $now;

                // Start Guzzle Requests
                $time_start = microtime(true);
                $req = $client->createRequest($serie['method'], $serie['url'], $options);
                $client->send($req)->then(
                    function ($response) use ($output, $ping, $time_start) {
                        $time_end = microtime(true);
                        $time = $time_end - $time_start;

                        // Update Ping Data
                        $ping['ping_time'] = $time;
                        $ping['http_status'] = $response->getStatusCode();
                        $ping['error'] = false;

                        // Insert Ping on Database
                        if($this->db->insert($ping)){
                            $output->writeln(
                                sprintf('SUCCESS %s > %s <info>%s</info>',
                                $ping['project'],
                                $ping['serie'],
                                $time)
                            );
                        } else {
                            throw new \Exception('Database insert failed', 0);
                        }
                    },
                    function ($error) use ($output, $ping, $time_start)  {
                        $time_end = microtime(true);
                        $time = $time_end - $time_start;

                        // Update Ping Data
                        $ping['ping_time'] = $time;
                        $ping['error'] = true;
                        $ping['error_log'] = $error->getMessage();

                        if($error->getCode() !== 0) {
                            $ping['http_status'] = $error->getCode();
                        } else {
                            $ping['http_status'] = 504;
                        }

                        // Insert Ping on Database
                        if($this->db->insert($ping)){
                            $output->writeln(
                                sprintf('FAILED %s > %s <error>%s</error>',
                                $ping['project'],
                                $ping['serie'],
                                $error->getMessage())
                            );
                        } else {
                            throw new \Exception('Database insert failed', 0);
                        }
                        throw $error;
                    }
                );
            }
        }
    }
}
