<?php
// src/Controller/FixityController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class FixityController
{
    public function read(Request $request, ParameterBagInterface $params, KernelInterface $kernel)
    {
        $resource_id = $request->headers->get('Resource-ID');
        // Dummy data.
        $data = array(
            'fixity event 1 for resource ' . $resource_id,
            'fixity event 2 for resource ' . $resource_id,
            'fixity event 3 for resource ' . $resource_id
        );

        // Testing calling plugin from controller. If you run 'curl -v http://localhost:8000/api/resource/10'
        // your request will return the last entry in the database for resource 10. Some might argue that
        // reusable code like the persit code should be a service, not a command, but using a command
        // lets us enable plugins via congfiguration only. If we implemented plugins as services, we couldn't
        // do that, since calling class would need to "use" the service. Is there a way around this? See
        // https://symfony.com/doc/current/service_container/configurators.html.
        $this->params = $params;
        $this->http_method = $this->params->get('app.fixity.method');
        $this->fixity_algorithm = $this->params->get('app.fixity.algorithm');

        // Set in the parameters section of config/services.yaml.
        $this->fetchPlugins = $this->params->get('app.plugins.fetch');
        $this->persistPlugins = $this->params->get('app.plugins.persist');

        $get_last_digest_plugin_command = new Application($kernel);
        $get_last_digest_plugin_command->setAutoExit(false);

        $now_iso8601 = date('c');
        $resource_id = 'http://localhost:8000/examplerepository/rest/' . $id;

        $get_last_digest_plugin_input = new ArrayInput(array(
           'command' => 'app:riprap:plugin:persist:to:database',
            '--resource_id' => $resource_id,
            '--timestamp' => $now_iso8601,
            '--digest_algorithm' => $this->fixity_algorithm,
            '--event_uuid' => '',
            '--digest_value' => '',
            '--outcome' => '',
            '--operation' => 'get_last_digest',
        ));
        $get_last_digest_plugin_output = new BufferedOutput();
        $get_last_digest_plugin_return_code = $get_last_digest_plugin_command->run($get_last_digest_plugin_input, $get_last_digest_plugin_output);
        $last_digest_for_resource = $get_last_digest_plugin_output->fetch();

        $data = array($last_digest_for_resource);

        $response = new JsonResponse($data);
        return $response;
    }

    public function add(Request $request)
    {
        $resource_id = $request->headers->get('Resource-ID');
        // Dummy data.
        $data = array(
            'new fixity event for resource ' . $resource_id,
        );
        $response = new JsonResponse($data);
        return $response;
    }

    public function update(Request $request)
    {
        $resource_id = $request->headers->get('Resource-ID');
        // Dummy data.
        $data = array(
            'updated fixity event for resource ' . $resource_id,
        );
        $response = new JsonResponse($data);
        return $response;
    }
}
