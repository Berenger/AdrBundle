# ADR Bundle

> Action–domain–responder (ADR) is a software architectural pattern that was
> proposed by Paul M. Jones[1] as a refinement of Model–view–controller (MVC)
> that is better suited for web applications.
> ADR was devised to match the request-response flow of HTTP communications
> more closely than MVC, which was originally designed for desktop software applications.
>
> _from [Wikipedia](https://en.wikipedia.org/wiki/Action%E2%80%93domain%E2%80%93responder "https://en.wikipedia.org/wiki/Action%E2%80%93domain%E2%80%93responder")_

This bundle simplifies the setup of the ADR in a Symfony 5.0 project.

## Versions

- [1.x version](https://github.com/Berenger/AdrBundle/tree/1.x)
  (stable version) : Recommended for all projects using Symfony 3.4.
- [2.x version](https://github.com/Berenger/AdrBundle/tree/2.x)
  (stable version) : Recommended for all projects using Symfony 4.4.
- [3.x version](https://github.com/Berenger/AdrBundle/tree/master)
  (stable version) : Recommended for all projects using Symfony 5.0 or newer.

## Prerequisites

- PHP : version 7.2 minimum
- Symfony : version 5.0 minimum

## Installation

### Add the bundle to your project

```bash
  composer require berenger/adr-bundle:^3.0
```


### Add Cor identifier

Use in the reponse's header : Access-Control-Allow-Origin

update the file : config/service.yaml

```yaml
parameters:
  ...
  cors: "https://url.service"
  ...
```

### Add bundle to config

update the file : config/bundles.php

```php
<?php

return [
    ...
    AdrBundle\AdrBundle::class => ['all' => true],
    ...
];
```

### Symfony : Services are private by default

update the file : config/service.yaml

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
  controller: App\Controller\ViewPostAction
  methods: ["GET"]
  defaults:
    responder: App\Responder\ViewPostResponder
```

### Action (Controller)

The action must return an associative array that will be pass to the responder `__invoke` method.
Each key of the array must match an argument of the responder `__invoke` method signature,
otherwise an exception will be thrown. The order of the arguments in the array is not important.

```php
<?php

namespace App\Controller;

use App\Entity\Post;
use Doctrine\ORM\EntityNotFoundException;

class ViewPostAction
{
    public function __invoke(int $id)
    {
        $postRepository = $this->getDoctrine()->getRepository(Post::class);
        $post = $postRepository->findOneById($id);

        if (!$post) {
            throw new EntityNotFoundException('Post not found');
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

namespace App\Responder;


use App\Entity\Post;

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
namespace App\Entity;

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
