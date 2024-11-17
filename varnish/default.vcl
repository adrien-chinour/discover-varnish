vcl 4.1;

import std;

backend default {
    .host = "blog";
    .port = "80";
}

sub vcl_recv {
    set req.http.Surrogate-Capability = "key=ESI/1.0";
}

sub vcl_backend_response {
    if (beresp.http.Surrogate-Control ~ "ESI/1.0") {
        unset beresp.http.Surrogate-Control;
        set beresp.do_esi = true;
    }
}

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
