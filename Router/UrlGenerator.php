<?php

namespace AdrBundle\Router;

use Symfony\Component\Routing\RouterInterface;

class UrlGenerator
{
    /** @var RouterInterface */
    protected $router;

    /**
     * AbstractResponder constructor.
     *
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * Generate absolute urls
     *
     * @param string $name
     * @param array $parameters
     * @return string
     */
    public function absoluteUrl(string $name, array $parameters = [])
    {
        return $this->generateUrl($name, $parameters, RouterInterface::ABSOLUTE_URL);
    }

    /**
     * @param string $name
     * @param array $parameters
     * @param int $referenceType
     * @return string
     */
    public function generateUrl(string $name, array $parameters = [], int $referenceType = RouterInterface::ABSOLUTE_URL)
    {
        return $this->router->generate($name, $parameters, $referenceType);
    }}
