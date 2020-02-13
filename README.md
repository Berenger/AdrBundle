# ADR Bundle

> Action–domain–responder (ADR) is a software architectural pattern that was
> proposed by Paul M. Jones[1] as a refinement of Model–view–controller (MVC)
> that is better suited for web applications.
> ADR was devised to match the request-response flow of HTTP communications
> more closely than MVC, which was originally designed for desktop software applications.
>
> _from [Wikipedia](https://en.wikipedia.org/wiki/Action%E2%80%93domain%E2%80%93responder "https://en.wikipedia.org/wiki/Action%E2%80%93domain%E2%80%93responder")_

This bundle simplifies the setup of the ADR in a Symfony 3.4 project.

## Versions

- [1.x version](https://github.com/Berenger/AdrBundle/tree/1.x)
  stable version. Recommended for all projects using Symfony 3.4;
- [2.x version](https://github.com/Berenger/AdrBundle/tree/2.x)
  stable version. Recommended for all projects using Symfony 4.4;
- [3.x version](https://github.com/Berenger/AdrBundle/tree/master)
  stable version. Recommended for all projects using Symfony 5.0 or newer;

## Prerequisites

- PHP : version 7.2 minimum
- Symfony : version 3.4 minimum

## Installation

### Add Cor identifier

Use in the reponse's header : Access-Control-Allow-Origin

update the file : app/config/parameters.yml

```yaml
parameters:
  ...
  cors: "https://url.service"
  ...
```

### Add bundle to AppKernel

update the file : app/AppKernel.php

```php
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            ...
            new AdrBundle\AdrBundle(),
            ...
        ];
    }
}
```

### Symfony : Services are private by default

update the file : app/config/services.yml

```yaml
services:
  _defaults:
    autowire: true
    autoconfigure: true
    public: true
```

## Usage

### Routing

```yaml
post:
  path: /posts/{id}
  defaults:
    _controller: AppBundle\Controller\ViewPostAction
    _responder: AppBundle\Responder\ViewPostResponder
  methods: ["GET"]
```

### Action (Controller)

The action must return an associative array that will be pass to the responder `__invoke` method.
Each key of the array must match an argument of the responder `__invoke` method signature,
otherwise an exception will be thrown. The order of the arguments in the array is not important.

```php
<?php

namespace AppBundle\Controller;

use Doctrine\ORM\EntityNotFoundException;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\HttpFoundation\Request;

class ViewPostAction
{
    public function __invoke(
        Request $request,
        RegistryInterface $doctrine
    )
    {
        // Get the ID of the post to be displayed
        $id = $request->attributes->get('id');
        $post = $doctrine->getRepository('AppBundle:Post')->find($id);

        if (!$post) {
            throw new EntityNotFoundException('Entity not found');
        }

        return [
            'post' => $post,
        ];
    }
}
```

### Responder

The responder can either:

- directly return an instance of `Symfony\Component\HttpFoundation\Response` (e.g. when you return a response containing HTML generated with Twig)
- an array of data to be serialized in the response (mostly the case when you're building an API that returns Json or XML). In that case you can specify serialization groups.

```php
<?php

namespace AppBundle\Responder;

use AppBundle\Entity\Post;

class ViewPostResponder
{
    /**
     * @param Post $post
     * @return array
     */
    public function __invoke(Post $post)
    {
        return [
            'data' => [
                'post' => $post,
            ],
            'serialization_groups' => 'view',
        ];
    }
}
```

### Exemple of entity

```php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

class Post
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer", options={"unsigned"=true})
     * @Groups({"always"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"list","view"})
     */
    private $title;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"view"})
     */
    private $content;

    ...
}
```

### API returns either XML or JSON formatted data

#### Header for JSON return (By Default)

```
accept:application/json
```

#### Header for XML return

```
accept:application/xml
```
