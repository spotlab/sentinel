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
use Spotlab\Sentinel\Services\ConfigServiceProvider;
use Spotlab\Sentinel\Services\SQLiteDatabase;


/**
 * Guardian Provider.
 */
class SentinelControllers implements ServiceProviderInterface, ControllerProviderInterface
{
    private $db;

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
        $config = new ConfigServiceProvider();
        $app['sentinel.config'] = $config->getConfig();
        $app['sentinel.projects'] = $config->getProjects();
        $app['sentinel.series'] = $config->getSeries();

        // Create Database
        $this->db = new SQLiteDatabase();
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
        $controllers->match('/{project}/{serie}', function (Request $request) use ($app) {

            return $app['twig']->render('index.html.twig', array(
                'uri' => $_SERVER['REQUEST_URI'],
                'title' => $app['sentinel.name'],
                'parameters' => $app['sentinel.config']['parameters'],
                'projects' => $app['sentinel.config']['projects']
            ));

        })
        ->value('project', FALSE)
        ->value('serie', FALSE);

        // Series Data
        $controllers->match('/api/content/{project}/{serie}', function (Request $request) use ($app) {

            // Get data from Database with GET Parameter
            if($request->get('serie')) {
                $response = new JsonResponse($this->db->findSerie(
                    $request->get('project'), $request->get('serie')
                ));
            } else {
                $response = new JsonResponse($this->db->findProjectSeries(
                    $request->get('project')
                ));
            }

            return $response;
        })
        ->value('serie', FALSE);

        // Average Data
        $controllers->match('/api/average/{project}', function (Request $request) use ($app) {

            // Get data from Database with GET Parameter
            $response = new JsonResponse($this->db->findProjectAverage(
                $request->get('project')
            ));

            return $response;
        });

        return $controllers;
    }
}
