<?php
namespace OpenCFP\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use OpenCFP\Form\TalkForm;
use OpenCFP\Model\Talk;

class TalkController
{
    public function editAction(Request $req, Application $app)
    {
        if (!$app['sentry']->check()) {
            return $app->redirect($app['url'] . '/login');
        }

        $id = $req->get('id');
        $user = $app['sentry']->getUser();
        $talk_id= filter_var($id, FILTER_VALIDATE_INT);

        if (empty($talk_id)) {
            return $app->redirect($app['url'] . '/dashboard');
        }

        $talk = new Talk($app['db']);
        $talk_info = $talk->findById($talk_id);

        if ($talk_info['user_id'] !== $user->getId()) {
            return $app->redirect($app['url'] . '/dashboard');
        }

        $template_name = 'edit_talk.twig';
        $template = $app['twig']->loadTemplate($template_name);
        $data = array(
            'formAction' => '/talk/update',
            'id' => $talk_id,
            'title' => $talk_info['title'],
            'description' => $talk_info['description'],
            'type' => $talk_info['type'],
            'buttonInfo' => 'Update my talk!',
            'user' => $user
        );

        return $template->render($data);
    }

    public function createAction(Request $req, Application $app)
    {
        if (!$app['sentry']->check()) {
            return $app->redirect($app['url'] . '/login');
        }

        $user = $app['sentry']->getUser();

        $template_name = 'create_talk.twig';
        $template = $app['twig']->loadTemplate($template_name);
        $data = array(
            'formAction' => '/talk/create',
            'title' => $req->get('title'),
            'description' => $req->get('description'),
            'type' => $req->get('type'),
            'buttonInfo' => 'Submit my talk!',
            'user' => $user
        );

        return $template->render($data);
    }

    public function processCreateAction(Request $req, Application $app)
    {
        if (!$app['sentry']->check()) {
            return $app->redirect($app['url'] . '/login');
        }

        $user = $app['sentry']->getUser();
        $request_data = array(
            'title' => $req->get('title'),
            'description' => $req->get('description'),
            'type' => $req->get('type'),
            'user_id' => $req->get('user_id')
        );

        $form = new TalkForm($request_data, $app['purifier']);
        $form->sanitize();

        if (!$form->validateAll()) {
            $template = $app['twig']->loadTemplate('create_talk.twig');
            $data = array(
                'formAction' => '/talk/create',
                'title' => $req->get('title'),
                'description' => $req->get('description'),
                'type' => $req->get('type'),
                'buttonInfo' => 'Submit my talk!',
                'user' => $user,
                'error_message' => implode('<br>', $form->getErrorMessages())
            );

            return $template->render($data);
        }

        $sanitized_data = $form->getCleanData();
        $data = array(
            'title' => $sanitized_data['title'],
            'description' => $sanitized_data['description'],
            'type' => $sanitized_data['type'],
            'user_id' => (int)$user->getId(),
            'user' => $user
        );
        $talk = new Talk($app['db']);

        if (!$talk->create($data)) {
            $template_name = 'create_talk.twig';
            $template = $app['twig']->loadTemplate('create_talk.twig');
            $data['formAction'] = '/talk/create';
            $data['buttonInfo'] = 'Submit my talk!';
            $data['error_message'] = "Unable to create a new record in our talks database, please try again";

            return $template->render($data);
        }

        $app['session']->set('flash', array(
            'type' => 'success',
            'short' => '',
            'ext' => "Succesfully created a talk"
        ));

        return $app->redirect($app['url'] . '/dashboard');
    }

    public function updateAction(Request $req, Application $app)
    {
        if (!$app['sentry']->check()) {
            return $app->redirect($app['url'] . '/login');
        }

        $user = $app['sentry']->getUser();

        $request_data = array(
            'id' => $req->get('id'),
            'title' => $req->get('title'),
            'description' => $req->get('description'),
            'type' => $req->get('type'),
            'user_id' => $req->get('user_id')
        );

        $form = new TalkForm($request_data, $app['purifier']);
        $form->sanitize();
        $valid = $form->validateAll();

        if ($valid) {
            $sanitized_data = $form->getCleanData();
            $data = array(
                'id' => (int)$sanitized_data['id'],
                'title' => $sanitized_data['title'],
                'description' => $sanitized_data['description'],
                'type' => $sanitized_data['type'],
                'user_id' => (int)$user->getId()
            );
            $talk = new Talk($app['db']);
            $talk->update($data);
            $app['session']->set('flash', array(
                'type' => 'success',
                'short' => 'Updated talk!'
            ));

            return $app->redirect($app['url'] . '/dashboard');
        }

        if (!$valid) {
            $template_name = 'edit_talk.twig';
            $template = $app['twig']->loadTemplate($template_name);
            $data = array(
                'formAction' => '/talk/update',
                'id' => $req->get('id'),
                'title' => $req->get('title'),
                'description' => $req->get('description'),
                'type' => $req->get('type'),
                'buttonInfo' => 'Update my talk!',
                'user' => $user,
                'error_message' => implode("<br>", $form->getErrorMessages())
            );

            return $template->render($data);
        }

        return $app->redirect($app['url'] . '/talk/edit/' . $req->get('id'));
    }

    public function deleteAction(Request $req, Application $app)
    {
        if (!$app['sentry']->check()) {
            return $app->json(array('delete' => 'no-user'));
        }

        $user = $app['sentry']->getUser();
        $talk = new Talk($app['db']);

        if ($talk->delete($req->get('tid'), $req->get('user_id')) === true) {
            return $app->json(array('delete' => 'ok'));
        }

        return $app->json(array('delete' => 'no'));
    }

    public function gridAction(Request $req, Application $app)
    {
        $app['twig']->addFilter('var_dump',      new \Twig_Filter_Function('var_dump'));
        $template = $app['twig']->loadTemplate('talk_grid.twig');
        $talk = new Talk($app['db']);
        $data = $talk->getGrid();
        return $template->render($data);
    }
}
