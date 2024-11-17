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
#[Route('/', name: 'home')]
final class HomeController extends AbstractController
{
    public function __construct(
        private readonly ArticleRepository $articleRepository,
        private readonly CategoryRepository $categoryRepository,
    ) {}

    public function __invoke(): Response
    {
        return $this->render('pages/home.html.twig', [
            'articles' => $this->articleRepository->findBy([], ['publishedAt' => 'DESC']),
            'categories' => $this->categoryRepository->findAll(),
        ]);
    }
}
