{*
    Template to generate the config for a Nginx virtual host

    Available variables:
    $domain             string
    $port               integer
    $certificateFile    string
    $certificateKeyFile string
*}
server {
    listen 443 ssl;

    server_name {$domain};
    ssl_certificate {$certificateFile};
    ssl_certificate_key {$certificateKeyFile};

    location / {
        proxy_pass http://127.0.0.1:{$port};
        proxy_set_header X-Real-IP  $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto https;
        proxy_set_header X-Forwarded-Port 443;
        proxy_set_header Host $host;
    }
}
