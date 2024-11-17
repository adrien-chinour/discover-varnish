vcl 4.1;

import std;

backend default {
    .host = "blog";
    .port = "80";
    .probe = {
            .url = "/health";
            .timeout = 1s;
            .interval = 5s;
            .window = 10;
            .threshold = 5;
       }
}

sub vcl_recv {
    if (std.healthy(req.backend_hint)) {
        set req.grace = 10s;
    }

    if (req.url == "/") {
        set req.grace = 1h;
    }

    set req.http.Surrogate-Capability = "key=ESI/1.0";
}

sub vcl_backend_response {
    if (beresp.http.Surrogate-Control ~ "ESI/1.0") {
        unset beresp.http.Surrogate-Control;
        set beresp.do_esi = true;
    }

//    set beresp.grace = 1h;
}

sub vcl_deliver {
    # Ajoute un en-tête pour indiquer si la réponse provient du cache
    if (obj.hits > 0) {
        set resp.http.X-Cache = "HIT";
        set resp.http.X-Cache-Hits = obj.hits;
    } else {
        set resp.http.X-Cache = "MISS";
    }

    # Ajoute un en-tête pour indiquer l'état de la réponse dans le cache
    if (obj.ttl < 0s) {
         set resp.http.X-Cache-Period = "stale";
    } else {
        set resp.http.X-Cache-Period = "fresh";
    }

    return (deliver);
}
