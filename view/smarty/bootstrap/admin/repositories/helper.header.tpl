<div>
    <div class="btn-group">
        <a class="btn btn-default" href="{url id="repositories.edit" parameters=["repository" => $repository->getSlug()]}?referer={$app.url.request|urlencode}">
            <span class="glyphicon glyphicon-pencil"></span>
            {translate key="button.repository.edit"}
        </a>
        <a class="btn btn-default" href="{url id="repositories.delete" parameters=["repository" => $repository->getSlug()]}?referer={$app.url.request|urlencode}">
            <span class="glyphicon glyphicon-trash"></span>
            {translate key="button.repository.delete"}
        </a>
    </div>
    <a class="btn" href="{url id="repositories"}">
        {translate key="button.back.overview"}
    </a>
</div>

<p>&nbsp;</p>

<div>
    <span class="pull-right text-muted hidden-xs">
        {$repository->getUrl()}
    </span>
    <div class="btn-group pull-left" style="margin-right: 12px">
        <a class="btn btn-default dropdown-toggle" data-toggle="dropdown" href="#">
            <span class="text-muted">branch:</span> <strong>{$branch}</strong>
            <span class="caret"></span>
        </a>
        <ul class="dropdown-menu">
            {foreach $branches as $b}
            <li><a href="{url id=$url parameters=['repository' => $repository->getSlug()]}/{$b}">{$b}</a></li>
            {/foreach}
        </ul>
    </div>
    <ul class="nav nav-tabs" role="tablist">
        <li role="presentation"{if $url == "repositories.browse"} class="active"{/if}>
            <a href="{url id="repositories.browse" parameters=['repository' => $repository->getSlug()]}/{$branch}" aria-controls="{translate key="title.browse"}" role="tab">
                {translate key="title.browse"}
            </a>
        </li>
        <li role="presentation"{if $url == "repositories.integration"} class="active"{/if}>
            <a href="{url id="repositories.integration" parameters=['repository' => $repository->getSlug()]}/{$branch}" aria-controls="{translate key="title.integration"}" role="tab">
                {translate key="title.integration"}
            </a>
        </li>
        <li role="presentation"{if $url == "repositories.deployment"} class="active"{/if}>
            <a href="{url id="repositories.deployment" parameters=['repository' => $repository->getSlug()]}/{$branch}" aria-controls="{translate key="title.deployments"}" role="tab">
                {translate key="title.deployments"}
            </a>
        </li>
        <li role="presentation"{if $url == "repositories.messages"} class="active"{/if}>
            <a href="{url id="repositories.messages" parameters=['repository' => $repository->getSlug()]}/{$branch}" aria-controls="{translate key="title.messages"}" role="tab">
                {translate key="title.messages"}
            </a>
        </li>
    </ul>
<div>
