<?php

declare(strict_types=1);

namespace App\Controller\Fragment;

use App\Entity\Article;
use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;

#[Cache(maxage: 30, smaxage: 360, public: true)]
#[Route('/article/{id}/recommendations', name: 'article_recommendations', requirements: ['id' => '\d+'])]
final class ArticleRecommendationsController extends AbstractController
{
    public function __construct(
        private readonly ArticleRepository $articleRepository,
    ) {}

    public function __invoke(Article $article): Response
    {
        return $this->render('fragments/article_recommendations.html.twig', [
            'recommendations' => $this->articleRepository->findBy(
                criteria: ['category' => $article->category()],
                orderBy: ['publishedAt' => 'DESC'],
                limit: 2,
            ),
        ]);
    }
}
