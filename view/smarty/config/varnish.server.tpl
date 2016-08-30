{*
    Template to generate a vcl for Varnish instance redirection

    Available variables:
    $projects         array
*}
{foreach $projects as $project}
backend server{$project->getId()} {
    .host = "localhost";
    .port = "{10000 + $project->getId()}";
}

{/foreach}
sub vcl_recv {
{$first = true}
{foreach $projects as $project}{foreach $project->getEnvironments() as $environment}
{$port = $project->getVarnishServer()->getVarnishPort()}
{if $port != 80}{$port = ":`$port`"}{else}{$port = ''}{/if}
    {if $first}if{$first = false}{else}} elseif{/if} (req.http.host == "{$environment->getDomain()}{$port}"{foreach $environment->getAliases() as $alias} || req.http.host == "{$alias}{$port}"{/foreach}) {
        set req.backend_hint = server{$project->getId()};
{/foreach}{if $project@last}    }
{/if}{/foreach}
    if (req.backend_hint != default) {
        return (pipe);
    }
}
