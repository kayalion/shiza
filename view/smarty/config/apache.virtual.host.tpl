    {*
    Template to generate the config for an Apache virtual host

    Available variables:
    $username               string
    $domain                 string
    $reversedDomain         string
    $aliases                array|null
    $sslDomain              string
    $sslCertificateFile     string
    $sslCertificateKeyFile  string
    $configDirectory        string
    $logDirectory           string
    $publicDirectory        string
    $phpVersion             string
    $phpBinary              string
    $phpWrapper             string
*}
<VirtualHost *:80>
    ServerName      {$domain|@idn_to_ascii}
    {if $aliases}ServerAlias    {foreach $aliases as $alias} {$alias|@idn_to_ascii}{/foreach}{/if}


    DocumentRoot    {$publicDirectory}
    ErrorLog        {$logDirectory}/{$reversedDomain}-error.log
    CustomLog       {$logDirectory}/{$reversedDomain}-access.log combined
    LogLevel        warn

    DirectoryIndex  index.php index.htm index.html

    <IfModule mod_fcgid.c>
        SuexecUserGroup {$username} {$username}
        <Directory {$publicDirectory}>
            Options -Includes +MultiViews +ExecCGI -Indexes +FollowSymLinks
            AllowOverride All

            Order allow,deny
            Allow from all
            Require all granted

            AddHandler fcgid-script .php
            FCGIWrapper {$phpWrapper} .php
        </Directory>
    </IfModule>
{if $isSsl && !$isVarnish}
    <IfModule mod_rewrite.c>
        RewriteEngine on
        RewriteCond %{ldelim}SERVER_NAME} ={$sslDomain}
        RewriteRule ^ https://%{ldelim}SERVER_NAME}%{ldelim}REQUEST_URI} [END,QSA,R=permanent]
    </IfModule>
{/if}
</VirtualHost>

{if $isSsl}
<IfModule mod_ssl.c>
    <VirtualHost *:443>
        ServerName      {$sslDomain|@idn_to_ascii}

        DocumentRoot    {$publicDirectory}

        DirectoryIndex  index.php index.htm index.html

        SSLCertificateFile      {$sslCertificateFile}
        SSLCertificateKeyFile   {$sslCertificateKeyFile}
        SSLEngine on

        SSLProtocol             all -SSLv2 -SSLv3
        SSLCipherSuite          ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES256-GCM-SHA384:DHE-RSA-AES128-GCM-SHA256:DHE-DSS-AES128-GCM-SHA256:kEDH+AESGCM:ECDHE-RSA-AES128-SHA256:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA:ECDHE-ECDSA-AES128-SHA:ECDHE-RSA-AES256-SHA384:ECDHE-ECDSA-AES256-SHA384:ECDHE-RSA-AES256-SHA:ECDHE-ECDSA-AES256-SHA:DHE-RSA-AES128-SHA256:DHE-RSA-AES128-SHA:DHE-DSS-AES128-SHA256:DHE-RSA-AES256-SHA256:DHE-DSS-AES256-SHA:DHE-RSA-AES256-SHA:AES128-GCM-SHA256:AES256-GCM-SHA384:AES128-SHA256:AES256-SHA256:AES128-SHA:AES256-SHA:AES:CAMELLIA:DES-CBC3-SHA:!aNULL:!eNULL:!EXPORT:!DES:!RC4:!MD5:!PSK:!aECDH:!EDH-DSS-DES-CBC3-SHA:!EDH-RSA-DES-CBC3-SHA:!KRB5-DES-CBC3-SHA
        SSLHonorCipherOrder     on
        SSLCompression          off

        SSLOptions +StrictRequire

        LogFormat "%h %l %u %t \"%r\" %>s %b \"%{ldelim}Referer}i\" \"%{ldelim}User-agent}i\"" vhost_combined
        LogFormat "%v %h %l %u %t \"%r\" %>s %b" vhost_common

        ErrorLog        {$logDirectory}/{$reversedDomain}-error.log
        CustomLog       {$logDirectory}/{$reversedDomain}-access.log combined
        LogLevel        warn

        <IfModule mod_fcgid.c>
            SuexecUserGroup {$username} {$username}
            <Directory {$publicDirectory}>
                Options -Includes +MultiViews +ExecCGI -Indexes +FollowSymLinks
                AllowOverride All

                Order allow,deny
                Allow from all
                Require all granted

                AddHandler fcgid-script .php
                FCGIWrapper {$phpWrapper} .php
            </Directory>
        </IfModule>
    </VirtualHost>
</IfModule>
{/if}
