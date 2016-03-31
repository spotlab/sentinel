<?php

namespace Spotlab\Sentinel\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Spotlab\Sentinel\Services\ConfigServiceProvider;
use Spotlab\Sentinel\Services\MongoDatabase;

/**
 * Class Backup
 * @package Spotlab\Sentinel\Command
 */
class Average extends Command
{
    private $db;

    /**
     * Set us up the command!
     */
    public function configure()
    {
        $this->setName('average')
             ->setDescription('Calculate average in time');
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
        // Get Projects
        $config = new ConfigServiceProvider();
        $projects = $config->getProjects($flat = true);
        $parameters = $config->getParameters();
        $output->writeln(sprintf('FIND : <comment>%s projects</comment>', count($projects)));

        // Create Database
        $this->db = new MongoDatabase($parameters['mongo']);

        // Register time
        $now = new \MongoDate();

        foreach ($projects as $project_name => $project) {
            //$average = $this->db->getProjectAverage($project);

            //print_r($average);

            $series = $this->db->getProjectSeries($project_name);

            print_r($series);
        }
    }
}
