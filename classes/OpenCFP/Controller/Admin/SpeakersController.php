<?php
namespace OpenCFP\Controller\Admin;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use OpenCFP\Model\Speaker;
use Pagerfanta\View\TwitterBootstrap3View;

class SpeakersController
{
    
    public function getFlash(Application $app)
    {
        $flasg = $app['session']->get('flash');
        $this->clearFlash($app);

        return $flash;
    }

    public function clearFlash(Application $app)
    {
        $app['session']->set('flash', null);
    }
    
    protected function userHasAccess($app)
    {
        if (!$app['sentry']->check()) {
            return false;
        }

        $user = $app['sentry']->getUser();

        if (!$user->hasPermission('admin')) {
            return false;
        }

        return true;
    }

    public function indexAction(Request $req, Application $app)
    {
        // Check if user is an logged in and an Admin
        if (!$this->userHasAccess($app)) {
            return $app->redirect($app['url'] . '/dashboard');
        }

        $speakerModel = new Speaker($app['db']);
        $rawSpeakers = $speakerModel->getAll();

        // Set up our page stuff
        $adapter = new \Pagerfanta\Adapter\ArrayAdapter($rawSpeakers);
        $pagerfanta = new \Pagerfanta\Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage(20);
        $pagerfanta->getNbResults();

        if ($req->get('page') !== null) {
            $pagerfanta->setCurrentPage($req->get('page'));
        }

        // Create our default view for the navigation options
        $routeGenerator = function($page) {
            return '/admin/speakers?page=' . $page;
        };
        $view = new TwitterBootstrap3View();
        $pagination = $view->render(
            $pagerfanta,
            $routeGenerator,
            array('proximity' => 3)
        );

        $template = $app['twig']->loadTemplate('admin/speaker/index.twig');
        $templateData = array(
            'airport' => $app['confAirport'],
            'arrival' => $app['arrival'],
            'departure' => $app['departure'],
            'pagination' => $pagination,
            'speakers' => $pagerfanta,
            'page' => $pagerfanta->getCurrentPage()
        );

        return $template->render($templateData);
    }

    public function viewAction(Request $req, Application $app)
    {
        // Check if user is an logged in and an Admin
        if (!$this->userHasAccess($app)) {
            return $app->redirect($app['url'] . '/dashboard');
        }

        // Get info about the talks
        $userId = $req->get('id');
        $speakerModel = new Speaker($app['db']);
        $speaker = $speakerModel->getDetailsByUserId($userId);

        // Build and render the template
        $template = $app['twig']->loadTemplate('admin/speaker/view.twig');
        $templateData = array(
            'speaker' => $speaker,
            'photo_path' => $app['uploadPath'],
            'page' => $req->get('page'),
            'airport' => $this->findAirport($speaker['airport']),
        );
        return $template->render($templateData);
    }

    public function deleteAction(Request $req, Application $app)
    {
        // Check if user is an logged in and an Admin
        if (!$this->userHasAccess($app)) {
            return $app->redirect($app['url'] . '/dashboard');
        }

        $userId = $req->get('id');
        $speakerModel = new Speaker($app['db']);
        $response = $speakerModel->delete($userId);

        $ext = "Succesfully deleted the requested user";
        $type = 'success';
        $short = 'Success';

        if ($response === false) {
            $ext = "Unable to delete the requested user";
            $type = 'error';
            $short = 'Error';
        }

        // Set flash message
        $app['session']->set('flash', array(
            'type' => $type,
            'short' => $short,
            'ext' => $ext
        ));

        return $app->redirect($all['url'] . '/admin/speakers');
    }

    private function findAirport($airport)
    {
        $fileName = APP_DIR . "/config/airports.dat";
        if (!file_exists($fileName)) {
            return false;
        }
        $fd = fopen($fileName, 'r');
        while (($data = fgetcsv($fd)) !== false) {
            if ($data[4] == $airport) {
                fclose($fd);
                return $data[2] . ' / ' . $data[3];
            }
        }
        fclose($fd);
        return "Unknown Airport";
    }
}