<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ArticleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArticleRepository::class, readOnly: true)]
#[ORM\Table(name: 'articles')]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $slug;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(type: 'string', length: 255)]
    private string $image;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(type: 'string', length: 255)]
    private string $author;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $publishedAt;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'articles')]
    private Category $category;

    #[ORM\OneToMany(mappedBy: 'article', targetEntity: Comment::class)]
    #[ORM\OrderBy(['publishedAt' => 'DESC'])]
    private Collection $comments;

    private function __construct()
    {
        $this->comments = new ArrayCollection();
    }

    public static function create(string $title, string $slug, string $description, string $image, string $content, string $author, \DateTimeImmutable $publishedAt): static
    {
        $self = new static();
        $self->title = $title;
        $self->slug = $slug;
        $self->description = $description;
        $self->image = $image;
        $self->content = $content;
        $self->author = $author;
        $self->publishedAt = $publishedAt;

        return $self;
    }

    public function setCategory(Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function id(): int
    {
        return $this->id;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function image(): string
    {
        return $this->image;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function author(): string
    {
        return $this->author;
    }

    public function publishedAt(): \DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function category(): Category
    {
        return $this->category;
    }

    public function comments(): Collection
    {
        return $this->comments;
    }
}
