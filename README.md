# Poc Varnish

## Mode ESI

On va d'abord ajouter dans la routine `vcl_recv` (à la reception d'une requête) un en-tête permettant d'indiquer au
backend que nous sommes en capacité de traiter des inclusions ESI.

```
sub vcl_recv {
    set req.http.Surrogate-Capability = "ESI/1.0";
}
```

> Symfony propose une méthode render_esi disponible depuis Symfony 2 et permettant d'avoir un fallback automatique sur
> un appel interne dans le cas ou l'en-tête `Surrogate-Capability` n'est pas défini.

Maintenant que nous avons indiqué au backend que nous sommes en mesure de traiter les balises ESI, il va falloir les
interpréter dans `vcl_backend_response`. Pour ça, il nous suffit d'ajouter uniquement dans le cas ou l'en-tête
`Surrogate-Control` est présent l'instruction `do_esi`.

```
sub vcl_backend_response {
    if (beresp.http.Surrogate-Control ~ "ESI/1.0") {
        unset beresp.http.Surrogate-Control;
        set beresp.do_esi = true;
    }
    
    return (deliver);
}
```

## Mastering Varnish commands

### varnishlog

Lire les logs en filtrant sur certains champs

```
varnishlog -i "Begin,BereqURL,BerespStatus,VCL_call"
```

### varnishncsa

Requêtes client

```
varnishncsa -F '{"Timestamp": "%t", "Cache": "%{Varnish:hitmiss}x","Age": %{age}o, "Request": "%r", "Status": "%s", "Time-To-Serve": %D}}'
varnishncsa -F '{"Timestamp": "%t", "Varnish-Side": "%{Varnish:side}x", "Handling": "%{Varnish:handling}x", "Cache": "%{Varnish:hitmiss}x","Age": %{age}o, "Request": "%r", "Status": "%s", "Time-To-Serve": %D}}'
```

Requêtes backend

```
varnishncsa -b -F '{"Timestamp": "%t", "Request": "%r", "Status": "%s", "Time-To-Serve": %D}'
varnishncsa -b -F '{"Timestamp": "%t", "Varnish-Side": "%{Varnish:side}x", "Handling": "%{Varnish:handling}x", "Request": "%r", "Status": "%s", "Time-To-Serve": %D}'
```

### varnishstat


Usage mémoire
```
varnishstat -1 -f SMA.s0.g_bytes -f SMA.s0.g_space
```