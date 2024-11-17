<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;

#[Cache(maxage: 30, smaxage: 240, public: true)]
#[Route('/article/{slug}.html', name: 'article', requirements: ['slug' => '[a-z0-9-]+'])]
final class ArticleController extends AbstractController
{
    public function __construct(
        private readonly ArticleRepository $articleRepository,
    ) {}

    public function __invoke(string $slug): Response
    {
        if (null === ($article = $this->articleRepository->findOneBy(criteria: ['slug' => $slug]))) {
            throw $this->createNotFoundException();
        }

        return $this->render('pages/article.html.twig', [
            'article' => $article,
        ]);
    }
}
