<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ORM\Table(name: 'categories')]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\OneToMany(mappedBy: 'category', targetEntity: Article::class)]
    private Collection $articles;

    private function __construct()
    {
        $this->articles = new ArrayCollection();
    }

    public static function create(string $name, string $slug): static
    {
        $category = new static();
        $category->name = $name;
        $category->slug = $slug;

        return $category;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function slug(): ?string
    {
        return $this->slug;
    }

    /**
     * @return Collection<int, Article>
     */
    public function articles(): Collection
    {
        return $this->articles;
    }
}
