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
use Symfony\Component\HttpFoundation\JsonResponse;

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
        $app['sentinel.name'] = 'Sentinel';
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
        $app->mount('/ping', $this->connect($app));
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

            $graphs = $this->getGraphs($app);

            return $app['twig']->render('index.html.twig', array(
                'title' => $app['sentinel.name'],
                'graphs' => $graphs
            ));

        });

        // Ping status
        $controllers->match('/ping', function (Request $request) use ($app) {

            $graphs = $this->getGraphs($app);
            $status = 200;
            $return = array();

            foreach ($graphs as $key => $val) {
                $data = file_get_contents($val['file']);
                $data = json_decode($data, true);
                $return[$key]['name'] = $val['name'];
                $return[$key]['status'] = $data['status'];
                $return[$key]['average'] = $data['average'];

                if($data['status'] >= 400) {
                    $status = $data['status'];
                }
            }

            $response = new JsonResponse($return);
            $response->setStatusCode($status);
            return $response;
        });

        return $controllers;
    }

    private function getGraphs(Application $app)
    {
        $finder = new Finder();
        $finder
            ->files()
            ->name('*.json')
            ->sortByName()
            ->in($app['sentinel.data.json']);

        $graphs = array();
        foreach ($finder as $file) {
            $name = $file->getRelativePathname();
            $name = str_replace('.json', '', $name);
            $name = str_replace('_', ' ', $name);

            $graphs[] = array(
                'name' => $name,
                'file' => 'data/' . $file->getRelativePathname(),
            );
        }

        return $graphs;
    }
}
