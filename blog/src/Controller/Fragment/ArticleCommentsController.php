<?php

declare(strict_types=1);

namespace App\Controller\Fragment;

use App\Entity\Article;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;

#[Cache(maxage: 30, smaxage: 360, public: true)]
#[Route('/article/{id}/comments', name: 'article_comments', requirements: ['id' => '\d+'])]
final class ArticleCommentsController extends AbstractController
{
    public function __invoke(Article $article): Response
    {
        return $this->render('fragments/article_comments.html.twig', [
            'comments' => $article->comments(),
        ]);
    }
}
