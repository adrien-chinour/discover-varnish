# Booster son backend avec Varnish

Varnish est une solution de cache HTTP puissante et économique, capable d’améliorer considérablement les performances de nombreux sites web. Découvrons ensemble son fonctionnement et les étapes pour le mettre en place.

## Découverte de Varnish

Varnish est un service de cache HTTP conçu pour accélérer les sites web en stockant et en servant les réponses directement depuis la mémoire. Il réduit efficacement la charge sur les serveurs principaux tout en offrant une grande flexibilité grâce à son langage de configuration, le VCL.

Fonctionnant de manière autonome, Varnish s'intercale entre le backend et les requêtes des utilisateurs, optimisant ainsi les échanges.

## Mise en place

Varnish est particulièrement adapté pour générer des réponses qui ne varient pas d’un utilisateur à l’autre. Dans cet exemple, nous allons partir d’un blog, mais cette approche peut également s’appliquer à des cas plus complexes, comme un site e-commerce.

Pour cet exemple, j’ai mis en place un blog développé en PHP/Symfony, souffrant d’une optimisation insuffisante. Les appels à la base de données sont trop nombreux, et une augmentation du trafic oblige à augmenter les capacités de nos serveurs backend et base de données. Même avec une faible utilisation, les temps de réponse sont trop élevés, ce qui pourrait affecter la satisfaction des utilisateurs ou nuire au SEO (le TTFB dépasse la limite tolérée par Google).

Nous allons donc mettre en cache l’ensemble des pages pour éviter d’interroger le backend à chaque requête utilisateur.

> Le projet contenant le code source de l’application et la configuration de Varnish :
> [GitHub](https://www.github.com/adrien-chinour/varnish-presentation)

## Installation de Varnish

Dans mon cas, je vais utiliser l'image Docker que je vais intégrer dans mon fichier `compose.yaml` :

```diff
services:
    blog:
        container_name: blog
        build: ./docker
-       ports: [ "80:80" ]
+       ports: [ "80" ]
        volumes:
            - ./blog:/var/www/html
            - ./docker/apache2/default.conf:/etc/apache2/sites-available/000-default.conf

+    varnish:
+        container_name: varnish
+        image: varnish
+        command: -n varnish
+        depends_on: [ blog ]
+        ports: [ "80:80" ]
+        volumes:
+            - ./varnish:/etc/varnish
```

Notre backend ne va plus directement être exposé sur le port 80, ce sera maintenant Varnish qui répondra sur ce port. On peut à présent configurer dans varnish notre blog comme backend par défault.

> Pour savoir comment installer Varnish : https://www.varnish-software.com/developers/tutorials/#installations

Pour ça il va falloir ajouter le fichier default.vcl qui défini la configuration de notre Varnish :

```vcl
vcl 4.1;

import std;

backend default {
    .host = "blog";
    .port = "80";
}
```

Et voilà, Varnish est maintenant configuré pour agir comme middleware entre nos requêtes utilisateurs et le backend.

## Configuration de Varnish et règles de cache

Dans son état actuel, Varnish ne fera pas grand-chose, car nous n’avons encore défini aucune règle de cache.

Avant de plonger dans des extraits de code complexes, il est essentiel de comprendre le fonctionnement de Varnish. Ce dernier utilise le Varnish Configuration Language (VCL) pour configurer le serveur de cache. Bien que ce langage soit à la fois puissant et efficace, il peut paraître déroutant lorsque l’on débute.

Ressources essentielles à connaître :
- [Varnish Configuration Language (VCL)](https://www.varnish-software.com/developers/tutorials/varnish-configuration-language-vcl/) : C’est la première documentation à mettre en favori. Pas besoin de mémoriser chaque détail, mais il est crucial de comprendre les bases pour éviter des erreurs coûteuses.
- [Built-in VCL](https://www.varnish-software.com/developers/tutorials/varnish-builtin-vcl/) : Cette documentation explique le comportement par défaut de Varnish. La maîtriser n’est pas obligatoire, mais elle vous permettra de mieux anticiper et adapter son fonctionnement à vos besoins.

En prenant le temps de se familiariser avec ces deux ressources, vous serez bien mieux préparé pour exploiter tout le potentiel de Varnish et éviter les écueils courants.

Commençons par un exemple simple pour vérifier le fonctionnement de Varnish. Nous allons ajouter un en-tête HTTP qui indiquera si la réponse provient du cache ou non :

```vcl
sub vcl_deliver {
    # Ajoute un en-tête pour indiquer si la réponse provient du cache
    if (obj.hits > 0) {
        set resp.http.X-Cache = "HIT";
        set resp.http.X-Cache-Hits = obj.hits;
    } else {
        set resp.http.X-Cache = "MISS";
    }
    
    return (deliver);
}
```

> À chaque modification de la configuration, il faut faire un reload du service pour recompiler le VCL et mettre à jour le service. Il suffit de lancer la commande : `varnishreload`.

Une requête backend est par default en MISS avec Symfony à cause de l'en-tête de [Cache-Control](https://developer.mozilla.org/fr/docs/Web/HTTP/Headers/Cache-Control).

Symfony, si aucune règle n'est défini va créer l'en-tête de cette manière : `Cache-Control: no-cache, private`. Pour modifier ça on peut rajouter un attribut sur notre route :

```php
#[Cache(maxage: 30, smaxage: 120, public: true)] // Set Header "Cache-Control: max-age=30, public, s-maxage=120"
#[Route('/', name: 'home')]
final class HomeController extends AbstractController
{
    // ...
}
```

Maintenant notre page va profiter d'un cache de deux minutes sur notre serveur Varnish. On peut vérifier ça avec l'en-tête `X-Cache`. Il y a aussi l'en-tête `Age` qui permet de savoir depuis combien de temps l'objet est en cache.

Il y a cependant, un élément important à prendre compte dans l'[implémentation par défault](https://www.varnish-software.com/developers/tutorials/varnish-builtin-vcl/#authorization-headers-and-cookies-are-not-cacheable) de `vcl_recv` :

```vcl
sub vcl_recv {
    // ...

    if (req.http.Authorization || req.http.Cookie) {
        /* Not cacheable by default */
        return (pass);
    }
    
    return (hash);
}
```

S'il y a un en-tête `Cookie` ou `Authorization` alors la requête est **toujours transmise au backend**, peu importe la valeur de `Cache-Control`. C'est une règle logique, mais qui peut être source d'incompréhension ou de bug dans le retour de Varnish.

> Pour configurer Varnish correctement, il y a dans la documentation une liste des règles fréquemment mise en
> place : [Exemple VCL template](https://www.varnish-software.com/developers/tutorials/example-vcl-template).

## Mode ESI avec Varnish

Le mode ESI (Edge Side Includes) dans Varnish permet de diviser une page web en fragments et de mettre en cache chaque fragment séparément. Cela optimise les performances en servant des parties fréquemment utilisées depuis le cache tout en récupérant dynamiquement les éléments personnalisés depuis le backend.

Reprenons notre exemple, sur notre blog la page article va comporter deux fragments en plus de notre article, les commentaires et les suggestions.

Pour traiter les 2 fragments, on va extraire le rendu dans une route dédiée puis on peut les appeler dans le rendu de l'article de cette manière :

```twig
{{ render_esi(url('article_comments', { 'id': article.id })) }}
{{ render_esi(url('article_recommendations', { 'id': article.id })) }}
```

Si j'affiche à nouveau un article le rendu comporte bien les deux fragments, cependant Varnish n'a absolument rien fait pour ça, c'est Symfony qui le fait lui-même comme décris dans [la documentation Symfony](https://symfony.com/doc/current/http_cache/esi.html).

Pour déléguer cela, il va falloir indiquer à Symfony que Varnish est capable de traiter les blocs ESI :

```vcl
sub vcl_recv {
    set req.http.Surrogate-Capability = "key=ESI/1.0";
}

sub vcl_backend_response {
    if (beresp.http.Surrogate-Control ~ "ESI/1.0") {
        unset beresp.http.Surrogate-Control;
        set beresp.do_esi = true;
    }
}
```

> Le code est tiré de la documentation Varnish : [ESI Support](https://www.varnish-software.com/developers/tutorials/example-vcl-template/#14-esi-support).

En regardant le profiler, on peut constater que Symfony ne fait plus de sous-requêtes pour les fragments ESI. Avec la commande `varnishlog`, on peut aussi tracer les requêtes backend qui sont faites.

> Dans la configuration de notre service docker il faut exécuter la commande en précisant l'argument suivant `-n /var/lib/varnish` sinon il affichera une erreur. C'est valable pour l'ensemble des commandes varnish.

À noter que la [version Entreprise permet de traiter les fragments ESI en parallèle](https://docs.varnish-software.com/varnish-enterprise/features/pesi/) alors que la **version classique le fait de manière séquentielle**. Cela peut faire une grosse différence dans le temps de traitement suivant le nombre de fragments ESI présentent dans la page.

## Monitoring de Varnish

Très rapidement un des besoins qui arrive lorsque l'on utilise Varnish est de pouvoir visualiser l'état du service ainsi que son fonctionnement. En debug, on peut encore se contenter de `varnishlog` mais en production ça ne suffit pas.

Pour ça, il va falloir mettre en place une stack de monitoring.

Dans la version entreprise rien de plus simple, il suffit d'utiliser le module fourni permettant d'exposer simplement les metrics au format Prometheus : [vmod stat](https://docs.varnish-software.com/varnish-enterprise/vmods/stat/). Ou alors le module exposant en json ou html les données en temps réel : [vmod rtstatus](https://docs.varnish-software.com/varnish-enterprise/vmods/rtstatus/).

Dans la version classique, on peut utiliser la stack proposée dans la documentation : [Monitoring with Prometheus, Loki & Grafana](https://www.varnish-software.com/developers/tutorials/monitoring-varnish-prometheus-loki-grafana/).

> Il y a aussi d'[autres exemples](https://www.varnish-software.com/developers/tutorials/#ops) pour ceux qui utilise New Relic, Datadog ou encore Dynatrace.

Je ne rentre pas dans le détail de l'implémentation, mais pour la suite cela permettra de mieux visualiser le comportement de notre Varnish.

## Cycle de vie des objets Varnish et gestion des erreurs


