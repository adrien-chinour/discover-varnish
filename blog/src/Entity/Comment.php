<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CommentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
#[ORM\Table(name: 'comments')]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 255)]
    private string $author;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $publishedAt;

    #[ORM\ManyToOne(targetEntity: Article::class, inversedBy: 'comments')]
    private Article $article;

    public static function create(string $author, string $content, \DateTimeImmutable $publishedAt, Article $article): static
    {
        $self = new static();
        $self->author = $author;
        $self->content = $content;
        $self->publishedAt = $publishedAt;
        $self->article = $article;

        return $self;
    }

    public function id(): int
    {
        return $this->id;
    }

    public function author(): string
    {
        return $this->author;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function publishedAt(): \DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function article(): Article
    {
        return $this->article;
    }
}
