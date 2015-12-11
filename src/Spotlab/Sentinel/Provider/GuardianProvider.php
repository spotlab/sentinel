<?php

namespace Spotlab\Sentinel\Provider;

use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;
use Silex\Provider\TwigServiceProvider;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guardian Provider.
 */
class GuardianProvider implements ServiceProviderInterface, ControllerProviderInterface
{
    private $client;

    /**
     * Registers services on the given app.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Application $app An Application instance
     */
    public function register(Application $app)
    {
        $app['sentinel.data.json'] = __DIR__ . '/../../../../web/data';
        $app['sentinel.twig.path'] = __DIR__ . '/../../../../views';
    }

    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registered
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     */
    public function boot(Application $app)
    {
        $app->register(new TwigServiceProvider(), array('twig.path' => $app['sentinel.twig.path']));
        $app->mount('/', $this->connect($app));
    }

    /**
     * Returns routes to connect to the given application.
     *
     * @param Application $app An Application instance
     *
     * @return ControllerCollection A ControllerCollection instance
     */
    public function connect(Application $app)
    {
        /** @var $controllers \Silex\ControllerCollection */
        $controllers = $app['controllers_factory'];
        $controllers->match('/', function (Request $request) use ($app) {

            $finder = new Finder();
            $finder
                ->files()
                ->name('*.json')
                ->sortByName()
                ->in($app['sentinel.data.json']);

            $graphs = array();
            foreach ($finder as $file) {
                $graphs[] = array(
                    'name' => str_replace('.json', '', $file->getRelativePathname()),
                    'file' => 'data/' . $file->getRelativePathname(),
                );
            }

            return $app['twig']->render('index.html.twig', array(
                'title' => 'Sentinel',
                'graphs' => $graphs
            ));

        });

        return $controllers;
    }
}
