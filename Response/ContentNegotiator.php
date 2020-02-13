<?php

namespace AdrBundle\Response;

use AdrBundle\Serializer\Serializer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class ContentNegociator
 */
class ContentNegotiator
{
    const JSON = 'json';
    const XML = 'xml';

    /** @var null|\Symfony\Component\HttpFoundation\Request */
    private $request;

    /** @var Serializer  */
    private $serializer;

    /** @var null|\Symfony\Component\DependencyInjection\ContainerInterface */
    private $container; // <- Add this

    /**
     * ContentNegotiator constructor.
     * @param RequestStack $requestStack
     * @param Serializer $serializer
     */
    public function __construct(RequestStack $requestStack, Serializer $serializer, ContainerInterface $container)
    {
        $this->request = $requestStack->getMasterRequest();
        $this->container = $container;
        $this->serializer = $serializer;
    }

    /**
     * @param array $data
     * @param int $status
     * @return Response
     * @throws \Exception
     */
    public function negotiate(array $data, int $status = Response::HTTP_OK): Response
    {
        $data = $this->resolve($data);

        $accept = $this->request->getAcceptableContentTypes();

        $response = null;
        $headers = [];
        $content = [];

        if (empty($data['data']['code'])) {
            $code = 200;
        } else {
            $code = $data['data']['code'];
            unset($data['data']['code']);
        }

        $responseArray = [
            'data' => $data['data'],
            'code' => $code,
        ];

        if (count(array_intersect(['application/json', 'application/x-json', '*/*'], $accept)) > 0) {

            $content = $this->serializer->normalize(
                $responseArray,
                self::JSON,
                $this->getSerializationGroups($data)
            );
            $response = new JsonResponse($content, $status, $headers);
        }

        if (count(array_intersect(['application/xml'], $accept)) > 0) {
            $response = new Response(
                $this->serializer->serialize($responseArray, self::XML, $this->getSerializationGroups($data)),
                $status
            );
        }

        if (!$response) {
            $response = new Response(serialize($data), $status);
        }

        $cros = $this->container->getParameter('cors');
        $headers['Access-Control-Allow-Origin'] = $cros;
        $headers['Access-Control-Allow-Methods'] = 'POST, GET, OPTIONS, DELETE';
        $headers['Access-Control-Max-Age'] = 86400;
        $response->headers->add($headers);
        return $response;
    }

    /**
     * @param array $data
     * @return array
     * @throws \Exception
     */
    private function getSerializationGroups(array $data): array
    {
        if (!isset($data['serialization_groups'])) {
            return [];
        }

        $serializationGroups = $data['serialization_groups'];
        if (!is_array($serializationGroups)) {
            if (!is_string($serializationGroups)) {
                throw new \Exception('Value of key serialization_groups should be a string or an array of strings');
            }
            $serializationGroups = [$serializationGroups];
        }

        $serializationGroups[] = 'always';

        return ['groups' => $serializationGroups];
    }

    /**
     * @param array $data
     * @return array
     */
    protected function resolve(array $data): array
    {
        return (new OptionsResolver())
            ->setRequired(['data'])
            ->setDefined(['serialization_groups'])
            ->setAllowedTypes('data', 'array')
            ->setAllowedTypes('serialization_groups', ['string', 'array'])
            ->resolve($data);
    }
}
