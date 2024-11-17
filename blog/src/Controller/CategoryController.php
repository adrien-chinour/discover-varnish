<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;

#[Cache(maxage: 30, smaxage: 120, public: true)]
#[Route('/category/{slug}/', name: 'category', requirements: ['slug' => '[a-z0-9-]+'])]
final class CategoryController extends AbstractController
{
    public function __construct(
        private readonly ArticleRepository $articleRepository,
        private readonly CategoryRepository $categoryRepository,
    ) {}

    public function __invoke(string $slug): Response
    {
        return $this->render('pages/category.html.twig', [
            'category' => $category = $this->categoryRepository->findOneBy(['slug' => $slug]),
            'headings' => $this->articleRepository->findBy(
                criteria: ['category' => $category],
                orderBy: ['publishedAt' => 'DESC'],
                limit: 2,
            ),
            'articles' => $this->articleRepository->findBy(
                criteria: ['category' => $category],
                orderBy: ['publishedAt' => 'DESC'],
                offset: 2
            ),
        ]);
    }
}
