<?php

namespace Spotlab\Sentinel\Controllers;

use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;
use Silex\Provider\TwigServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Spotlab\Sentinel\Services\ConfigProvider;


/**
 * Guardian Provider.
 */
class SentinelControllers implements ServiceProviderInterface, ControllerProviderInterface
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
        $app['sentinel.name'] = 'Sentinel';
        $app['sentinel.data.json'] = __DIR__ . '/../../../../web/data';
        $app['sentinel.twig.path'] = __DIR__ . '/../Ressources/views';
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

        // Get Projects
        $configProvider = new ConfigProvider();
        $app['sentinel.projects'] = $configProvider->getProjects();
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

        // Graphs display
        $controllers->match('/', function (Request $request) use ($app) {

            return $app['twig']->render('index.html.twig', array(
                'title' => $app['sentinel.name'],
                'graphs' => array()
            ));

        });

        // Ping status
        $controllers->match('/json/{serie}/{dashboard}', function (Request $request) use ($app) {

            $tools =

            $serie = $request->get('serie');
            $return = array();

            $response = new JsonResponse($return);
            return $response;
        })
        ->assert('dashboard', 'dashboard')
        ->assert('serie', '[a-z]+')
        ->value('dashboard', FALSE);

        return $controllers;
    }
}
