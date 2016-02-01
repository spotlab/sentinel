<?php

namespace Spotlab\Sentinel\Command;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Spotlab\Sentinel\Services\ConfigServiceProvider;
use Spotlab\Sentinel\Services\MongoDatabase;

/**
 * Class Backup
 * @package Spotlab\Sentinel\Command
 */
class Watch extends Command
{
    /**
     * Set us up the command!
     */
    public function configure()
    {
        $this->setName('watch')
             ->setDescription('Watching sentinel');
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
        while(true){

            $process = new Process('php bin/sentinel ping');
            $process->setTimeout(60);
            $process->run();

            // executes after the command finishes
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $output->writeln($process->getOutput());
            $output->writeln('---------------------');
            sleep(30);
        }
    }
}
