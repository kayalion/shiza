<div class="btn-group pull-right">
    <a href="{url id="repositories.download" parameters=['repository' => $repository->getSlug()]}/{$branch}{$path}" class="btn btn-default">
        <span class="glyphicon glyphicon-download"></span>
        <span class="hidden-xs">{translate key="button.download"}</span>
    </a>
    <a href="{url id="repositories.commits" parameters=['repository' => $repository->getSlug()]}/{$branch}{$path}" class="btn btn-default">
        <span class="glyphicon glyphicon-time"></span>
        <span class="hidden-xs">{translate key="button.history"}</span>
    </a>
</div>

<ul class="breadcrumb">
{foreach $breadcrumbs as $url => $label}
    <li><a href="{$url}">{$label}</a></li>
{/foreach}
</ul>
