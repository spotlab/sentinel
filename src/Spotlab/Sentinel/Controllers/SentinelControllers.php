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
use Spotlab\Sentinel\Services\MongoDatabase;


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
        $app['sentinel.flatprojects'] = $config->getProjects(true);
        $app['sentinel.series'] = $config->getSeries();

        // Create Database
        $this->db = new MongoDatabase($app['sentinel.config']['parameters']['mongo']);
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

        // API Series Data
        $controllers->match('/api/content/{project}/{serie}', function (Request $request) use ($app) {

            // Get data from Database with GET Parameter
            if($request->get('serie')) {
                $response = new JsonResponse(
                    $this->db->getSerie($request->get('project'), $request->get('serie')),
                    200, array('Cache-Control' => 'max-age=20, private, must-revalidate')
                );
            } else {
                $response = new JsonResponse(
                    $this->db->getProjectSeries($request->get('project')),
                    200, array('Cache-Control' => 'max-age=20, private, must-revalidate')
                );
            }

            return $response;
        })
        ->method('get')
        ->value('project', TRUE)
        ->value('serie', FALSE);

        // API Average Data
        $controllers->match('/api/templates/{template}', function (Request $request) use ($app) {

            // Get data from Database with GET Parameter
            return $app['twig']->render('partial/' . $request->get('template') . '.html.twig');
        })
        ->method('get')
        ->value('template', 'badge|average|sound');

        // API Average Data
        $controllers->match('/api/average/{project}', function (Request $request) use ($app) {

            // Get data from Database with GET Parameter
            return new JsonResponse(
                $this->db->getProjectAverage($request->get('project')),
                200, array('Cache-Control' => 'max-age=20, private, must-revalidate')
            );
        })
        ->method('get')
        ->value('project', TRUE);

        // API Status Data
        $controllers->match('/api/status', function (Request $request) use ($app) {

            // Get data from Database with GET Parameter
            return new JsonResponse(
                $this->db->getStatus($app['sentinel.flatprojects']),
                200, array('Cache-Control' => 'max-age=20, private, must-revalidate')
            );
        })
        ->method('get');

        // HTML Pages
        $controllers->match('/{project}/{subproject}', function (Request $request) use ($app) {

            if($request->get('project') && $request->get('subproject')) {
                $p = $request->get('project');
                $s = $request->get('subproject');

                if(empty($app['sentinel.config']['projects'][$p]['projects'][$s])) {
                    $app->abort(404, "Post $p/$s does not exist.");
                }

                $project = $app['sentinel.config']['projects'][$p];
                $subproject = $app['sentinel.config']['projects'][$p]['projects'][$s];

                return $app['twig']->render('project.html.twig', array(
                    'uri' => $_SERVER['REQUEST_URI'],
                    'title' => $app['sentinel.name'],
                    'header' => $subproject['title'] . ' | ' . $project['title'],
                    'parameters' => $app['sentinel.config']['parameters'],
                    'projects' => $app['sentinel.config']['projects'],
                    'project' => $p,
                    'subproject' => $s
                ));
            } else {
                return $app['twig']->render('index.html.twig', array(
                    'uri' => $_SERVER['REQUEST_URI'],
                    'title' => $app['sentinel.name'],
                    'header' => 'Tableau de bord',
                    'parameters' => $app['sentinel.config']['parameters'],
                    'projects' => $app['sentinel.config']['projects']
                ));
            }

        })
        ->method('get')
        ->value('project', FALSE)
        ->value('subproject', FALSE);

        return $controllers;
    }
}
